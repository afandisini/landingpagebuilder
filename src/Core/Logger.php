<?php

class Logger
{
    private const LOG_FILE = __DIR__ . '/../../log/log_error.txt';
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, ?string $file = null, ?int $line = null): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            self::log('ERROR', $message, [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
            ]);
            return false;
        });

        set_exception_handler(function (Throwable $e): void {
            self::exception($e);
            http_response_code(500);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, 'Unhandled exception: ' . $e->getMessage() . PHP_EOL);
            } else {
                echo 'Terjadi kesalahan pada server.';
            }
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
                self::log('FATAL', $error['message'], [
                    'type' => $error['type'],
                    'file' => $error['file'] ?? null,
                    'line' => $error['line'] ?? null,
                ]);
            }
        });
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function exception(Throwable $e, array $context = []): void
    {
        $context = array_merge($context, [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        self::log('EXCEPTION', $e->getMessage(), $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $payload = [
            'time' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => array_merge(self::requestContext(), $context),
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = '{"time":"' . date('Y-m-d H:i:s') . '","level":"' . strtoupper($level) . '","message":"' . $message . '","context":"[unserializable]"}';
        }

        self::writeLog($encoded . PHP_EOL);
    }

    private static function requestContext(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];
    }

    private static function writeLog(string $line): void
    {
        $dir = dirname(self::LOG_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
}
