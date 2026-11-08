<?php
/**
 * php/env.php
 * -----------
 * Minimal, dependency-free .env loader.
 *
 * Why not a library? This project has no Composer/vendor directory, and
 * the OTP feature must stay "free-tier friendly" with zero new packages.
 * This parser covers what we need: KEY=VALUE lines, # comments, optional
 * quotes. Values are exposed via env('KEY', $default).
 *
 * The .env file lives at the project root and is git-ignored.
 */
declare(strict_types=1);

function loadEnv(?string $path = null): void
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $path = $path ?? (__DIR__ . '/../.env');
    if (!is_file($path) || !is_readable($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        // Strip matching surrounding quotes.
        if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && $val[-1] === $val[0]) {
            $val = substr($val, 1, -1);
        }
        if ($key === '') continue;
        // Do not clobber real environment variables if already set.
        if (getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

/** Read an env value with an optional default. */
function env(string $key, ?string $default = null): ?string
{
    loadEnv();
    $val = getenv($key);
    if ($val === false || $val === '') {
        return $_ENV[$key] ?? $default;
    }
    return $val;
}

/** True only when the value looks like a real, non-empty setting. */
function envFilled(string $key): bool
{
    $val = env($key);
    return $val !== null && trim($val) !== '';
}
