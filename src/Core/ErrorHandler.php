<?php

final class ErrorHandler
{
    public function register(): void
    {
        set_error_handler(function (int $severity, string $message, ?string $file = null, ?int $line = null): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            Logger::error($message, ['severity' => $severity, 'file' => $file, 'line' => $line]);
            return false;
        });

        set_exception_handler(function (Throwable $e): void {
            Logger::exception($e);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, '[ERROR] ' . $e->getMessage() . PHP_EOL);
            } else {
                http_response_code(500);
                echo 'Internal server error';
            }
        });
    }

    public function render(Throwable $e, ?Request $request = null): Response
    {
        Logger::exception($e);
        $message = $e->getMessage();
        $status = $this->statusFromException($e);

        if ($request && $request->wantsJson()) {
            return Response::json(['ok' => false, 'error' => $message], $status);
        }

        return Response::make('Internal server error', $status);
    }

    private function statusFromException(Throwable $e): int
    {
        $code = (int)$e->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }
        return 500;
    }
}
