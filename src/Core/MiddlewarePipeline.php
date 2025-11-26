<?php

final class MiddlewarePipeline
{
    /**
        * @var callable[]
        */
    private array $stack = [];

    public function __construct(array $stack = [])
    {
        $this->stack = $stack;
    }

    public function add(callable $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function all(): array
    {
        return $this->stack;
    }

    public function process(Request $request, callable $destination): Response
    {
        $runner = array_reduce(
            array_reverse($this->stack),
            function (callable $next, callable $middleware) {
                return function (Request $request) use ($middleware, $next) {
                    return $middleware($request, $next);
                };
            },
            function (Request $request) use ($destination) {
                return $destination($request);
            }
        );

        $result = $runner($request);
        return $result instanceof Response ? $result : Response::make((string)$result);
    }
}
