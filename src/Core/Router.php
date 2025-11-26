<?php

final class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];

    public function add(string $method, string $pattern, callable|array|string $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $pattern = trim($pattern, '/');
        $regex = '#^' . preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function middleware(callable $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function dispatch(Request $request, Container $container, array $prependMiddleware = []): Response
    {
        $match = $this->match($request->method(), $request->route());
        if ($match === null) {
            return Response::redirect('?r=login');
        }

        ob_start();
        try {
            $middlewareStack = array_merge($prependMiddleware, $this->globalMiddleware, $match['middleware']);
            $pipeline = new MiddlewarePipeline($middlewareStack);
            $handler = function (Request $req) use ($match, $container) {
                $callable = $this->resolveHandler($match['handler'], $container);
                $result = $container->call($callable, $match['params']);
                return $this->normalizeResponse($result);
            };

            $response = $pipeline->process($request, $handler);
        } finally {
            $content = ob_get_clean();
            if (isset($response) && $content !== '') {
                $response->append($content);
            }
        }

        return $response ?? Response::make();
    }

    private function match(string $method, string $route): ?array
    {
        $method = strtoupper($method);
        $route = trim($route, '/');
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $definition) {
            if ($definition['pattern'] === $route) {
                return [
                    'handler' => $definition['handler'],
                    'params' => [],
                    'middleware' => $definition['middleware'],
                ];
            }
            if (preg_match($definition['regex'], $route, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }
                return [
                    'handler' => $definition['handler'],
                    'params' => $params,
                    'middleware' => $definition['middleware'],
                ];
            }
        }

        return null;
    }

    private function resolveHandler(callable|array|string $handler, Container $container): callable
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            return [$container->get($class), $method];
        }
        if (is_array($handler) && is_string($handler[0])) {
            return [$container->get($handler[0]), $handler[1]];
        }
        if (is_callable($handler)) {
            return $handler;
        }
        throw new InvalidArgumentException('Invalid route handler');
    }

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        if ($result === null) {
            return Response::make();
        }
        return Response::make((string)$result);
    }
}
