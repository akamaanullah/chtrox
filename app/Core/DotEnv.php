<?php

namespace App\Core;

class DotEnv
{
    protected string $path;

    public function __construct(string $path)
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }

        $this->path = $path;
    }

    public function load(): void
    {
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        foreach (file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (preg_match('/^([\'"])(.*)\1\s*(?:#.*)?$/', $value, $matches)) {
                $value = $matches[2];
            } elseif (str_contains($value, '#')) {
                [$value] = explode('#', $value, 2);
                $value = trim($value);
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
