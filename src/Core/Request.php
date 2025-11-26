<?php

final class Request
{
    private string $method;
    private string $path;
    private string $route;
    private array $query;
    private array $body;
    private array $headers;
    private array $cookies;
    private array $files;
    private array $server;
    private ?array $jsonInput;

    private function __construct(
        string $method,
        string $path,
        string $route,
        array $query,
        array $body,
        array $headers,
        array $cookies,
        array $files,
        array $server,
        ?array $jsonInput
    ) {
        $this->method = $method;
        $this->path = $path;
        $this->route = $route;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->jsonInput = $jsonInput;
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $path = trim($path, '/');

        $base = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = ltrim(substr($path, strlen($base)), '/');
        }

        $route = $_GET['r'] ?? '';
        $route = trim((string)$route, '/');
        if ($route === '') {
            $route = $path;
        }

        $headers = self::normalizeHeaders($_SERVER ?? []);
        $rawBody = file_get_contents('php://input');
        $jsonInput = null;
        if (is_string($rawBody) && $rawBody !== '' && str_contains(strtolower($headers['content-type'] ?? ''), 'application/json')) {
            $decoded = json_decode($rawBody, true);
            $jsonInput = is_array($decoded) ? $decoded : null;
        }

        return new self(
            $method,
            $path,
            $route,
            $_GET ?? [],
            $_POST ?? [],
            $headers,
            $_COOKIE ?? [],
            $_FILES ?? [],
            $_SERVER ?? [],
            $jsonInput
        );
    }

    private static function normalizeHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($key, 5))));
                $headers[$name] = $value;
            } elseif ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = $value;
            } elseif ($key === 'CONTENT_LENGTH') {
                $headers['content-length'] = $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function route(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): void
    {
        $this->route = trim($route, '/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }
        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }
        if ($this->jsonInput !== null && array_key_exists($key, $this->jsonInput)) {
            return $this->jsonInput[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->jsonInput ?? []);
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }

    public function wantsJson(): bool
    {
        $accept = strtolower($this->header('accept', ''));
        $requested = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        return str_contains($accept, 'application/json') || $requested;
    }

    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function json(): ?array
    {
        return $this->jsonInput;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }
}
