<?php

class Analytics
{
    private const COOKIE_NAME = 'bareeda_visitor';

    public static function trackPageView(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !self::enabled()) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            return;
        }
        $excludedPrefixes = [
            '/admin', '/analytics', '/api', '/payment', '/order', '/orders',
            '/account', '/login', '/register', '/logout', '/forgot-password',
            '/cron', '/links',
        ];
        foreach ($excludedPrefixes as $prefix) {
            if ($path === $prefix || strpos($path, $prefix . '/') === 0) {
                return;
            }
        }

        self::record('page_view', $path);
    }

    public static function trackQrScan(): void
    {
        if (self::enabled()) {
            self::record('qr_scan', '/links');
        }
    }

    public static function summary(int $days = 30): array
    {
        $since = self::since($days);
        $db = Database::getInstance();
        $traffic = $db->fetchOne(
            "SELECT
                COUNT(DISTINCT visitor_token) AS visitors,
                COALESCE(SUM(event_type = 'page_view'), 0) AS page_views,
                COALESCE(SUM(event_type = 'qr_scan'), 0) AS qr_scans,
                COUNT(DISTINCT CASE WHEN event_type = 'qr_scan' THEN visitor_token END) AS qr_visitors
             FROM analytics_events
             WHERE created_at >= ?",
            [$since]
        );
        $orders = $db->fetchOne(
            "SELECT COUNT(*) AS orders, COUNT(DISTINCT user_id) AS ordering_customers,
                COALESCE(SUM(total), 0) AS revenue
             FROM orders
             WHERE created_at >= ? AND status <> 'cancelled'",
            [$since]
        );

        $visitors = (int) ($traffic['visitors'] ?? 0);

        return [
            'days' => $days,
            'since' => $since,
            'visitors' => $visitors,
            'page_views' => (int) ($traffic['page_views'] ?? 0),
            'qr_scans' => (int) ($traffic['qr_scans'] ?? 0),
            'qr_visitors' => (int) ($traffic['qr_visitors'] ?? 0),
            'orders' => (int) ($orders['orders'] ?? 0),
            'ordering_customers' => (int) ($orders['ordering_customers'] ?? 0),
            'revenue' => (float) ($orders['revenue'] ?? 0),
        ];
    }

    public static function daily(int $days = 30): array
    {
        $since = self::since($days);
        $db = Database::getInstance();
        $trafficRows = $db->query(
            "SELECT DATE(created_at) AS day,
                COALESCE(SUM(event_type = 'page_view'), 0) AS page_views,
                COUNT(DISTINCT visitor_token) AS visitors,
                COALESCE(SUM(event_type = 'qr_scan'), 0) AS qr_scans
             FROM analytics_events
             WHERE created_at >= ?
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$since]
        );
        $orderRows = $db->query(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders
             FROM orders
             WHERE created_at >= ? AND status <> 'cancelled'
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$since]
        );

        $traffic = [];
        foreach ($trafficRows as $row) {
            $traffic[$row['day']] = $row;
        }
        $orders = [];
        foreach ($orderRows as $row) {
            $orders[$row['day']] = (int) $row['orders'];
        }

        $result = [];
        $start = new DateTimeImmutable(substr($since, 0, 10));
        for ($i = 0; $i < $days; $i++) {
            $day = $start->modify('+' . $i . ' days')->format('Y-m-d');
            $row = $traffic[$day] ?? [];
            $result[] = [
                'day' => $day,
                'visitors' => (int) ($row['visitors'] ?? 0),
                'page_views' => (int) ($row['page_views'] ?? 0),
                'qr_scans' => (int) ($row['qr_scans'] ?? 0),
                'orders' => $orders[$day] ?? 0,
            ];
        }
        return $result;
    }

    public static function topPages(int $days = 30, int $limit = 8): array
    {
        return Database::getInstance()->query(
            "SELECT path, COUNT(*) AS views, COUNT(DISTINCT visitor_token) AS visitors
             FROM analytics_events
             WHERE event_type = 'page_view' AND created_at >= ?
             GROUP BY path
             ORDER BY views DESC, path ASC
             LIMIT " . max(1, min(20, $limit)),
            [self::since($days)]
        );
    }

    private static function record(string $eventType, string $path): void
    {
        try {
            $token = self::visitorToken();
            if ($token === null) {
                return;
            }
            Database::getInstance()->insert('analytics_events', [
                'event_type' => $eventType,
                'visitor_token' => $token,
                'path' => substr($path, 0, 255),
            ]);
        } catch (Throwable $e) {
            error_log('Analytics recording failed: ' . $e->getMessage());
        }
    }

    private static function visitorToken(): ?string
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (is_string($token) && preg_match('/^[a-f0-9]{32}$/', $token)) {
            return $token;
        }
        if (headers_sent()) {
            return null;
        }

        $token = bin2hex(random_bytes(16));
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => SESSION_SECURE_COOKIE,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE_NAME] = $token;
        return $token;
    }

    private static function enabled(): bool
    {
        return ($_SERVER['HTTP_DNT'] ?? '') !== '1';
    }

    private static function since(int $days): string
    {
        $days = max(1, min(365, $days));
        return (new DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    }
}
