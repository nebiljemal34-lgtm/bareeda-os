<?php
/**
 * Bareeda Injera — file-based sliding-window rate limiter.
 *
 * No external dependencies (shared hosting): one JSON counter file per
 * bucket under storage/ratelimit/, guarded by an exclusive flock so
 * concurrent requests cannot race the increment. Windows are fixed-length
 * (simple, predictable); stale files are pruned lazily.
 *
 * Usage:
 *   if (!RateLimit::check('verify:' . $ip, 60, 60)) { http_response_code(429); ... }
 *   RateLimit::hit('ingest-fail:' . $ip) — count a failure without allowing.
 */

class RateLimit
{
    private static function dir(): string
    {
        $dir = defined('BASE_PATH') ? BASE_PATH . 'storage/ratelimit' : __DIR__ . '/../storage/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function path(string $bucket): string
    {
        return self::dir() . '/' . hash('sha256', $bucket) . '.json';
    }

    /**
     * Record one hit and report whether the bucket is under its limit.
     * @return bool true = allowed, false = limit exceeded.
     */
    public static function check(string $bucket, int $limit, int $windowSeconds): bool
    {
        $path = self::path($bucket);
        $now  = time();

        $fh = fopen($path, 'c+');
        if (!$fh) {
            // Storage failure must not take the site down — fail open.
            return true;
        }
        try {
            flock($fh, LOCK_EX);
            $raw   = stream_get_contents($fh);
            $state = $raw !== false && $raw !== '' ? json_decode($raw, true) : null;

            $windowStart = is_array($state) ? (int) ($state['w'] ?? 0) : 0;
            $count       = is_array($state) ? (int) ($state['c'] ?? 0) : 0;

            if ($now - $windowStart >= $windowSeconds) {
                $windowStart = $now;
                $count       = 0;
            }
            $count += 1;

            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode(['w' => $windowStart, 'c' => $count]));
            fflush($fh);

            return $count <= $limit;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
            // 1% of calls prune stale buckets (cheap, self-cleaning).
            if (random_int(1, 100) === 1) {
                self::prune($windowSeconds * 4);
            }
        }
    }

    /**
     * How many seconds remain until the bucket's window resets (for Retry-After).
     */
    public static function retryAfter(string $bucket, int $windowSeconds): int
    {
        $path = self::path($bucket);
        $state = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $windowStart = is_array($state) ? (int) ($state['w'] ?? 0) : 0;
        $remaining = $windowSeconds - (time() - $windowStart);
        return max(1, min($windowSeconds, $remaining));
    }

    private static function prune(int $olderThanSeconds): void
    {
        $files = glob(self::dir() . '/*.json') ?: [];
        foreach ($files as $file) {
            if (@filemtime($file) !== false && (time() - (int) filemtime($file)) > $olderThanSeconds) {
                @unlink($file);
            }
        }
    }

    /** Best-effort client IP for bucketing. */
    public static function clientIp(): string
    {
        $forwarded = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? '';
        if ($forwarded !== '') {
            $first = trim(explode(',', $forwarded)[0]);
            if ($first !== '') {
                return $first;
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
