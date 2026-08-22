<?php

namespace Atom\Config;

class Config
{
    private static array $envData = [];

    /**
     * Loads the .env file from the root directory.
     */
    public static function load(string $rootPath): void
    {
        $envFile = rtrim($rootPath, '/') . '/.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Split by first equals sign
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Strip quotes if present
                if (preg_match('/^["\'](.*)["\']$/', $val, $matches)) {
                    $val = $matches[1];
                }

                self::$envData[$key] = $val;
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }

    /**
     * Retrieves an environment setting.
     */
    public static function get(string $key, $default = null)
    {
        return self::$envData[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
