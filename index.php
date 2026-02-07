<?php

error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use Core\BlackOutNotify;
use Core\GetAccessToken;
use Core\Telegram;

loadEnvFile(__DIR__ . '/.env');

try {
    $blackOutNotify = new BlackOutNotify(null); // Временное значение для инициализации зависимостей

    $telegram = new Telegram($blackOutNotify);

    $blackOutNotify = new BlackOutNotify($telegram);

    GetAccessToken::run($blackOutNotify);
    BlackOutNotify::run($telegram);
} catch (Throwable $exception) {
    error_log(sprintf(
        '[%s] %s in %s:%d',
        date('c'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
}

function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
