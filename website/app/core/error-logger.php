<?php

function app_register_error_handlers(): void
{
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
        $fatal = [E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (in_array($errno, $fatal, true)) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        app_write_error_log('PHP Warning/Notice', $errstr, $errfile, $errline);
        return true;
    });

    set_exception_handler(function (\Throwable $e): void {
        app_write_error_log('Exception', $e->getMessage(), $e->getFile(), $e->getLine(), app_format_throwable_trace($e));
        http_response_code(500);
        echo 'Internal Server Error';
        exit;
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            app_write_error_log('Fatal Error', $error['message'], $error['file'], $error['line']);
            http_response_code(500);
            echo 'Internal Server Error';
        }
    });
}

function app_write_error_log(string $type, string $message, string $file, int $line, string $trace = ''): void
{
    $logDir = defined('ROOT') ? ROOT . 'storage/logs' : __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/errors-' . date('Y-m-d') . '.log';
    $entry = sprintf(
        "[%s] [%s] %s %s\n  File: %s:%d\n  Method: %s %s\n%s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        '',
        $file,
        $line,
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_SERVER['REQUEST_URI'] ?? '',
        $trace ? "  Trace:\n  " . str_replace("\n", "\n  ", $trace) . "\n" : ''
    );
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

function app_format_throwable_trace(\Throwable $exception): string
{
    $traceLines = [];

    foreach ($exception->getTrace() as $index => $frame) {
        $location = '[internal function]';
        if (isset($frame['file'], $frame['line'])) {
            $location = $frame['file'] . '(' . $frame['line'] . ')';
        }

        $call = app_format_trace_call($frame);
        $traceLines[] = '#' . $index . ' ' . $location . ': ' . $call;
    }

    $traceLines[] = '#' . count($traceLines) . ' {main}';

    return implode("\n", $traceLines);
}

function app_format_trace_call(array $frame): string
{
    if (!isset($frame['function'])) {
        return '';
    }

    $call = '';
    if (isset($frame['class'], $frame['type'])) {
        $call .= $frame['class'] . $frame['type'];
    }

    $arguments = array_map('app_format_trace_argument', $frame['args'] ?? []);

    return $call . $frame['function'] . '(' . implode(', ', $arguments) . ')';
}

function app_format_trace_argument(mixed $argument): string
{
    if (is_string($argument)) {
        return "'" . addcslashes($argument, "\\'\n\r\t") . "'";
    }

    if (is_int($argument) || is_float($argument)) {
        return (string)$argument;
    }

    if (is_bool($argument)) {
        return $argument ? 'true' : 'false';
    }

    if ($argument === null) {
        return 'null';
    }

    if (is_array($argument)) {
        return str_replace("\n", ' ', var_export($argument, true));
    }

    if (is_object($argument)) {
        return 'Object(' . get_class($argument) . ')';
    }

    if (is_resource($argument)) {
        return 'Resource(' . get_resource_type($argument) . ')';
    }

    return gettype($argument);
}
