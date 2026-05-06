<?php

final class Env
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $rootPath = dirname(__DIR__);
        $autoloadPath = $rootPath . '/vendor/autoload.php';

        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }

        if (class_exists(Dotenv\Dotenv::class) && is_file($rootPath . '/.env')) {
            Dotenv\Dotenv::createImmutable($rootPath)->safeLoad();
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();

        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        $value = getenv($key);

        if ($value !== false && $value !== '') {
            return $value;
        }

        return $default;
    }

    public static function required(string $key): string
    {
        $value = self::get($key);

        if ($value === null) {
            throw new RuntimeException(sprintf('Missing required environment variable: %s', $key));
        }

        return $value;
    }
}
