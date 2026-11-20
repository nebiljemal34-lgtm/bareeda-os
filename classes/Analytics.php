<?php

class Analytics
{
    private const COOKIE_NAME = 'bareeda_visitor';

    /**
     * User agents that are never a human browsing the shop. Shared by the
     * admin dashboard, the Activity Sentinel digest and the daily digest so
     * the three never disagree.
     */
    public const BOT_UA_REGEX = 'bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|python-requests|python-urllib|curl/|wget|go-http|java/|okhttp|scrapy|axios|node-fetch';

    /**
     * Reverse-DNS suffixes that mean "this is a server, not a customer".
     * Scanners often send a normal browser user agent, so the user-agent
     * check alone still counts them as visitors; the PTR lookup catches
     * cloud/VPS hosts (Tencent, Ace Data Centers, AWS, Hetzner…).
     */
    private const HOSTING_PTR_SUFFIXES = [
        'acedatacenter.com', 'amazonaws.com', 'compute.amazonaws.com',
        'azure.com', 'cloudapp.net', 'digitalocean.com', 'contabo.net',
        'contabo.com', 'hetzner.com', 'your-server.de', 'linode.com',
        'linodeusercontent.com', 'ovh.net', 'vultr.com', 'vultrusercontent.com',
        'tencentcloud', 'aliyun.com', 'alibabacloud.com', 'googleusercontent.com',
        'googlecloud', 'oraclecloud.com', 'choopa.com', 'colocrossing.com',
        'hostinger.com', 'ionos.com', 'leaseweb.com', 'psychz.net',
        'scaleway.com', 'upcloud.com', 'servarica.com', 'serverhub.com',
        'quadranet.com', 'sharktech.net', 'hostwinds.com', 'racknerd.com',
        'wholesaleinternet.net', 'greencloudvps.com',
    ];

    private const PTR_CACHE_TTL    = 2592000; // 30 days
    private const PTR_CACHE_MAX    = 1000;
    private const PTR_LOOKUP_BUDGET = 40;     // per request / digest run

    /** @var array{file:string,entries:array}|null */
    private static $ptrCache = null;
    private static $ptrLookups = 0;
    private static $ptrDeadline = null;

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
                // Activity Sentinel: per-event context (behind Cloudflare the
                // real client IP arrives in CF-Connecting-IP, and CF-IPCountry
                // gives the visitor's country for free).
                'ip' => self::clientIp(),
                'country' => substr((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''), 0, 2),
                'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'referrer' => substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255),
                'user_id' => currentUserId() ?: null,
            ]);
        } catch (Throwable $e) {
            error_log('Analytics recording failed: ' . $e->getMessage());
        }
    }

    /** Real client IP (Cloudflare-aware). */
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
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    /** Public hook for business events (cart adds, payments, logins…). */
    public static function track(string $eventType, string $path = ''): void
    {
        self::record($eventType, $path !== '' ? $path : ($_SERVER['REQUEST_URI'] ?? ''));
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

    // ─── Bot / scanner classification ──────────────────────────

    public static function isBotUserAgent(string $userAgent): bool
    {
        return $userAgent !== ''
            && preg_match('#' . self::BOT_UA_REGEX . '#i', $userAgent) === 1;
    }

    /** True when the IP reverse-resolves to a known hosting/VPS provider. */
    public static function isHostingIp(string $ip): bool
    {
        $host = self::hostOf($ip);
        if ($host === '') {
            return false;
        }
        foreach (self::HOSTING_PTR_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Page views split into real / bot / unverified-foreign buckets.
     *
     * - bot:          bot user agent OR reverse DNS says hosting provider.
     * - unverified:   non-Ethiopian IP with a single page view in the window
     *                 (the classic "one hit on / then gone" scanner pattern).
     *                 Kept separate instead of silently deleted, so the
     *                 digest stays honest.
     *
     * @param string      $sinceUtc inclusive, MySQL UTC datetime
     * @param string|null $untilUtc exclusive, MySQL UTC datetime
     */
    public static function pageViewBreakdown(string $sinceUtc, ?string $untilUtc = null, int $topLimit = 5, string $granularity = 'hour'): array
    {
        $sql = "SELECT path, ip, country, user_agent, visitor_token, referrer, created_at
                FROM analytics_events
                WHERE event_type = 'page_view' AND created_at >= ?";
        $params = [$sinceUtc];
        if ($untilUtc !== null) {
            $sql .= ' AND created_at < ?';
            $params[] = $untilUtc;
        }
        $sql .= ' ORDER BY created_at ASC LIMIT 50000';

        $rows = Database::getInstance()->query($sql, $params);

        $viewsPerIp = [];
        foreach ($rows as $row) {
            $ip = (string) $row['ip'];
            if ($ip !== '') {
                $viewsPerIp[$ip] = ($viewsPerIp[$ip] ?? 0) + 1;
            }
        }

        $botHits = 0;
        $unverified = 0;
        $confirmedTokens = [];
        $confirmedIps = [];
        $botIps = [];
        $unverifiedIps = [];
        $paths = [];
        $pathTokens = [];
        $series = [];
        $sources = [];
        $devices = ['mobile' => 0, 'tablet' => 0, 'desktop' => 0];
        $flags = [];

        foreach ($rows as $row) {
            $ip      = (string) $row['ip'];
            $country = strtoupper((string) ($row['country'] ?? ''));
            $createdUtc = (string) ($row['created_at'] ?? '');
            $isBot   = self::isBotUserAgent((string) ($row['user_agent'] ?? ''))
                || ($ip !== '' && self::isHostingIp($ip));

            if ($isBot) {
                $botHits++;
                if ($ip !== '') {
                    $botIps[$ip] = true;
                    $flags[$ip] = 'bot';
                }
                self::bumpSeries($series, $createdUtc, $granularity, 'bots');
                continue;
            }

            // No IP, or a non-Ethiopian IP that appeared exactly once: keep it
            // out of the headline numbers but report it separately.
            $isUnverified = $country !== 'ET'
                && ($ip === '' || ($viewsPerIp[$ip] ?? 0) <= 1);
            if ($isUnverified) {
                $unverified++;
                if ($ip !== '') {
                    $unverifiedIps[$ip] = true;
                    $flags[$ip] = 'unverified';
                }
                self::bumpSeries($series, $createdUtc, $granularity, 'unverified');
                continue;
            }

            $confirmedTokens[(string) $row['visitor_token']] = true;
            if ($ip !== '') {
                $confirmedIps[$ip] = true;
            }
            $path = (string) $row['path'];
            $paths[$path] = ($paths[$path] ?? 0) + 1;
            $pathTokens[$path][(string) $row['visitor_token']] = true;

            self::bumpSeries($series, $createdUtc, $granularity, 'views');
            $referrer = (string) ($row['referrer'] ?? '');
            $sources[$referrer] = ($sources[$referrer] ?? 0) + 1;
            $devices[self::deviceClass((string) ($row['user_agent'] ?? ''))]++;
        }

        arsort($paths);
        $topPages = [];
        foreach ($paths as $path => $views) {
            $topPages[] = [
                'path'     => $path,
                'views'    => $views,
                'visitors' => count($pathTokens[$path] ?? []),
            ];
            if (count($topPages) >= max(1, $topLimit)) {
                break;
            }
        }

        ksort($series);
        $seriesOut = array_values($series);
        usort($seriesOut, fn($a, $b) => ((int) ($a['ts'] ?? 0)) <=> ((int) ($b['ts'] ?? 0)));
        arsort($sources);

        return [
            'totals' => [
                'page_views'    => count($rows) - $botHits - $unverified,
                'visitors'      => count($confirmedTokens),
                'unique_ips'    => count($confirmedIps),
                'bot_hits'      => $botHits,
                'bot_ips'       => count($botIps),
                'unverified'    => $unverified,
                'unverified_ips' => count($unverifiedIps),
            ],
            'top_pages' => $topPages,
            'series'    => $seriesOut,
            'sources'   => $sources,
            'devices'   => $devices,
            'ip_flags'  => $flags,
        ];
    }

    /** Coarse device class from a user agent (dashboard-level). */
    public static function deviceClass(string $userAgent): string
    {
        if (preg_match('/ipad|tablet|kindle|silk/i', $userAgent)) {
            return 'tablet';
        }
        if (preg_match('/mobi|android|iphone|ipod/i', $userAgent)) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Time bucket for the dashboard chart. Bucket boundaries use Addis wall
     * time (the DB is UTC, PHP runs on Addis time).
     *
     * @return array{0:string,1:string,2:int} [key, label, unix timestamp]
     */
    private static function bucketFor(string $createdUtc, string $granularity): array
    {
        $ts = strtotime($createdUtc . ' UTC');
        if ($ts === false) {
            return ['', '', 0];
        }
        if ($granularity === 'day') {
            return [date('Y-m-d', $ts), date('M j', $ts), $ts];
        }
        $step = $granularity === '5min' ? 300 : 3600;
        $ts -= $ts % $step;
        return [date('Y-m-d H:i', $ts), date('H:i', $ts), $ts];
    }

    private static function bumpSeries(array &$series, string $createdUtc, string $granularity, string $field): void
    {
        if ($createdUtc === '' || !in_array($field, ['views', 'bots', 'unverified'], true)) {
            return;
        }
        [$key, $label, $ts] = self::bucketFor($createdUtc, $granularity);
        if ($key === '') {
            return;
        }
        if (!isset($series[$key])) {
            $series[$key] = [
                'bucket'     => $key,
                'label'      => $label,
                'ts'         => $ts,
                'views'      => 0,
                'bots'       => 0,
                'unverified' => 0,
            ];
        }
        $series[$key][$field]++;
    }

    /** Reverse DNS with an on-disk cache (digest runs are hourly, not hot). */
    private static function hostOf(string $ip): string
    {
        if ($ip === ''
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        $cache = self::ptrCache();
        if (array_key_exists($ip, $cache['entries'])) {
            return (string) ($cache['entries'][$ip]['host'] ?? '');
        }
        if (self::$ptrLookups >= self::PTR_LOOKUP_BUDGET) {
            return '';
        }
        // A slow resolver must never stall a digest or an admin page.
        if (self::$ptrDeadline === null) {
            self::$ptrDeadline = microtime(true) + 3.0;
        }
        if (microtime(true) >= self::$ptrDeadline) {
            return '';
        }
        self::$ptrLookups++;

        $host = @gethostbyaddr($ip);
        $host = is_string($host) && $host !== $ip ? strtolower($host) : '';
        self::ptrRemember($ip, $host);
        return $host;
    }

    private static function ptrCache(): array
    {
        if (self::$ptrCache !== null) {
            return self::$ptrCache;
        }
        $file = BASE_PATH . 'storage/ip-ptr-cache.json';
        $raw = is_file($file) ? @file_get_contents($file) : '';
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        $entries = [];
        if (is_array($data)) {
            $cutoff = time() - self::PTR_CACHE_TTL;
            foreach ($data as $ip => $entry) {
                if (is_array($entry) && (int) ($entry['at'] ?? 0) >= $cutoff) {
                    $entries[(string) $ip] = $entry;
                }
            }
        }

        self::$ptrCache = ['file' => $file, 'entries' => $entries];
        return self::$ptrCache;
    }

    private static function ptrRemember(string $ip, string $host): void
    {
        $cache = self::ptrCache();
        $cache['entries'][$ip] = ['host' => $host, 'at' => time()];
        if (count($cache['entries']) > self::PTR_CACHE_MAX) {
            uasort($cache['entries'], fn($a, $b) => ((int) ($b['at'] ?? 0)) <=> ((int) ($a['at'] ?? 0)));
            $cache['entries'] = array_slice($cache['entries'], 0, self::PTR_CACHE_MAX, true);
        }
        self::$ptrCache = $cache;

        $dir = dirname($cache['file']);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $cache['file'],
            json_encode($cache['entries'], JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
