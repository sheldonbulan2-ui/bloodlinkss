<?php
/**
 * php/ratelimit.php
 * -----------------
 * Lightweight, dependency-free rate limiting to blunt brute-force logins
 * and crude application-layer flooding (DDoS/DoS) attempts.
 *
 * Storage: small JSON files under data/ratelimit/ (one per bucket-hash).
 * This needs no extra PHP extension and no database table, so it will not
 * interfere with the existing workflow or schema.
 *
 * Two helpers:
 *   rateLimitHit($key, $max, $windowSeconds)  -> [allowed(bool), retryAfter(int)]
 *   clientIp()                                -> best-effort client IP string
 */
declare(strict_types=1);

/** Best-effort client IP (trusts the direct connection by default). */
function clientIp(): string
{
    // NOTE: only trust proxy headers if you actually run behind a known proxy.
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/** Directory where rate-limit counters live (created on demand). */
function rateLimitDir(): string
{
    $dir = __DIR__ . '/../data/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

/**
 * Fixed-window counter. Returns [allowed, retryAfterSeconds].
 * $key    logical bucket, e.g. "login:1.2.3.4" or "api:1.2.3.4"
 * $max    max hits allowed within the window
 * $window window length in seconds
 */
function rateLimitHit(string $key, int $max, int $window): array
{
    $file = rateLimitDir() . '/' . hash('sha256', $key) . '.json';
    $now = time();

    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        // If storage is unavailable, fail OPEN so real users are never locked out.
        return [true, 0];
    }
    try {
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $data = json_decode((string) $raw, true);
        $start = is_array($data) ? (int) ($data['start'] ?? 0) : 0;
        $count = is_array($data) ? (int) ($data['count'] ?? 0) : 0;

        if ($now - $start >= $window) {
            $start = $now;
            $count = 0;
        }
        $count++;

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode(['start' => $start, 'count' => $count]));
        fflush($fh);

        if ($count > $max) {
            return [false, max(1, $window - ($now - $start))];
        }
        return [true, 0];
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/**
 * Reset a bucket (call after a SUCCESSFUL login so honest users who
 * mistyped a few times are not penalised once they get in).
 */
function rateLimitClear(string $key): void
{
    $file = rateLimitDir() . '/' . hash('sha256', $key) . '.json';
    if (is_file($file)) @unlink($file);
}

/** Occasionally sweep stale counter files so the folder cannot grow forever. */
function rateLimitGc(int $maxAgeSeconds = 3600): void
{
    if (random_int(1, 50) !== 1) return; // ~2% of calls
    $dir = rateLimitDir();
    foreach (glob($dir . '/*.json') ?: [] as $path) {
        if (@filemtime($path) < time() - $maxAgeSeconds) @unlink($path);
    }
}
