<?php

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];

    public function set(string $id, callable|object|string $concrete, bool $singleton = true): void
    {
        if (is_object($concrete) && !$concrete instanceof Closure) {
            $this->instances[$id] = $concrete;
            return;
        }
        $this->bindings[$id] = [
            'concrete' => $concrete,
            'singleton' => $singleton,
        ];
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->bindings[$id]) && class_exists($id)) {
            $object = $this->build($id);
            $this->instances[$id] = $object;
            return $object;
        }

        if (!isset($this->bindings[$id])) {
            throw new class("Service {$id} not found") extends Exception implements NotFoundExceptionInterface {
            };
        }

        $binding = $this->bindings[$id];
        $concrete = $binding['concrete'];
        $object = is_callable($concrete) ? $concrete($this) : $concrete;

        if ($binding['singleton']) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflection = new ReflectionFunction($callable);
        }

        $deps = [];
        $positional = array_values($parameters);
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $deps[] = $this->get($type->getName());
                continue;
            }

            $name = $parameter->getName();
            if (array_key_exists($name, $parameters)) {
                $deps[] = $parameters[$name];
                continue;
            }

            if (!empty($positional)) {
                $deps[] = array_shift($positional);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $deps[] = $parameter->getDefaultValue();
                continue;
            }

            $deps[] = null;
        }

        return $callable(...$deps);
    }

    private function build(string $class)
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $deps = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $deps[] = $this->get($type->getName());
                continue;
            }

            $name = $parameter->getName();
            if ($parameter->isDefaultValueAvailable()) {
                $deps[] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException("Unresolvable dependency \${$name} for {$class}");
        }

        return $reflection->newInstanceArgs($deps);
    }
}
