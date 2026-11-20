<?php
/**
 * Bareeda Injera — read-only analytics API for the ERP Marketing Hub.
 *
 * The ERP proxy calls this server-side with the shared analytics secret; the
 * browser never sees the key. Aggregates only — no raw dumps — and bot
 * traffic is classified separately so the dashboard never inflates numbers.
 *
 *   analytics-api.php?key=…&view=summary&range=24h
 *   analytics-api.php?key=…&view=feed&range=24h
 *   analytics-api.php?key=…&view=scans&range=7d
 *
 * Times are returned in Addis local time; MySQL stores UTC.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/Analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$expected = defined('ANALYTICS_API_SECRET') && ANALYTICS_API_SECRET !== ''
    ? (string) ANALYTICS_API_SECRET
    : (string) CRON_SECRET_KEY;

$given = (string) ($_GET['key'] ?? '');
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$view  = (string) ($_GET['view'] ?? 'summary');
$range = (string) ($_GET['range'] ?? '24h');

$seconds = match ($range) {
    '1h'  => 3600,
    '24h' => 86400,
    '7d'  => 7 * 86400,
    default => 86400,
};

// UTC boundary — MySQL created_at is UTC while PHP runs on Addis time.
$since = gmdate('Y-m-d H:i:s', time() - $seconds);

/** IP -> page views in the window, for the single-hit scanner heuristic. */
function views_per_ip($db, string $since): array
{
    $map = [];
    foreach ($db->query(
        "SELECT ip, COUNT(*) AS n FROM analytics_events
         WHERE event_type = 'page_view' AND created_at >= ?
         GROUP BY ip",
        [$since]
    ) as $row) {
        $map[(string) $row['ip']] = (int) $row['n'];
    }
    return $map;
}

function mask_ip(string $ip): string
{
    if ($ip === '') {
        return '';
    }
    if (str_contains($ip, ':')) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 3)) . '::';
    }
    $octets = explode('.', $ip);
    if (count($octets) === 4) {
        return $octets[0] . '.' . $octets[1] . '.' . $octets[2] . '.•';
    }
    return $ip;
}

function referrer_host(string $referrer): string
{
    if ($referrer === '') {
        return 'Direct';
    }
    $host = parse_url($referrer, PHP_URL_HOST);
    if (!$host) {
        return 'Direct';
    }
    $host = preg_replace('/^www\./i', '', (string) $host);
    return str_contains($host, 'bareeda-injera.com') ? 'Internal' : $host;
}

function addis_time(string $utc): string
{
    return date('Y-m-d H:i', strtotime($utc . ' UTC'));
}

try {
    $db = Database::getInstance();

    if ($view === 'feed') {
        $rows = $db->query(
            "SELECT event_type, path, ip, country, user_agent, referrer, created_at
             FROM analytics_events
             WHERE created_at >= ?
             ORDER BY id DESC LIMIT 40",
            [$since]
        );
        $events = [];
        $perIp = views_per_ip($db, $since);
        foreach ($rows as $row) {
            $ua      = (string) $row['user_agent'];
            $ip      = (string) $row['ip'];
            $country = strtoupper((string) $row['country']);
            $isBot   = Analytics::isBotUserAgent($ua)
                || ($ip !== '' && Analytics::isHostingIp($ip));
            $isUnverified = !$isBot
                && $row['event_type'] === 'page_view'
                && $country !== 'ET'
                && ($ip === '' || ($perIp[$ip] ?? 0) <= 1);
            $events[] = [
                'type'     => $row['event_type'],
                'path'     => (string) $row['path'],
                'ip'       => mask_ip($ip),
                'country'  => (string) $row['country'],
                'device'   => Analytics::deviceClass($ua),
                'bot'      => $isBot || $isUnverified,
                'unverified' => $isUnverified,
                'referrer' => referrer_host((string) $row['referrer']),
                'time'     => addis_time((string) $row['created_at']),
            ];
        }
        echo json_encode(['ok' => true, 'view' => 'feed', 'range' => $range, 'events' => $events]);
        exit;
    }

    if ($view === 'scans') {
        $qr = $db->query(
            "SELECT path, COUNT(*) AS scans, COUNT(DISTINCT visitor_token) AS visitors
             FROM analytics_events
             WHERE event_type = 'qr_scan' AND created_at >= ?
             GROUP BY path ORDER BY scans DESC LIMIT 20",
            [$since]
        );
        $verification = $db->fetchOne(
            "SELECT COUNT(*) AS scans, COUNT(DISTINCT ip) AS ips, COUNT(DISTINCT path) AS letters
             FROM analytics_events
             WHERE event_type = 'page_view' AND path LIKE '/verify/l/%' AND created_at >= ?",
            [$since]
        );
        $last = $db->query(
            "SELECT path, ip, country, created_at
             FROM analytics_events
             WHERE event_type = 'page_view' AND path LIKE '/verify/l/%' AND created_at >= ?
             ORDER BY id DESC LIMIT 10",
            [$since]
        );
        $recent = [];
        foreach ($last as $row) {
            $recent[] = [
                'path'    => (string) $row['path'],
                'ip'      => mask_ip((string) $row['ip']),
                'country' => (string) $row['country'],
                'time'    => addis_time((string) $row['created_at']),
            ];
        }
        echo json_encode([
            'ok' => true,
            'view' => 'scans',
            'range' => $range,
            'qr' => array_map(fn($r) => [
                'path' => (string) $r['path'],
                'scans' => (int) $r['scans'],
                'visitors' => (int) $r['visitors'],
            ], $qr),
            'verification' => [
                'scans'   => (int) ($verification['scans'] ?? 0),
                'ips'     => (int) ($verification['ips'] ?? 0),
                'letters' => (int) ($verification['letters'] ?? 0),
            ],
            'recent' => $recent,
        ]);
        exit;
    }

    // ── summary ────────────────────────────────────────────────
    // Same classifier as the Telegram digest: bot user agent, hosting-provider
    // reverse DNS and single-hit foreign IPs never reach the headline numbers.
    $breakdown = Analytics::pageViewBreakdown($since, null, 8);
    $totals    = $breakdown['totals'];
    $topPages  = $breakdown['top_pages'];

    $sourceTotals = [];
    foreach ($breakdown['sources'] as $referrer => $hits) {
        $host = referrer_host((string) $referrer);
        $sourceTotals[$host] = ($sourceTotals[$host] ?? 0) + (int) $hits;
    }
    arsort($sourceTotals);
    $topSources = [];
    foreach (array_slice($sourceTotals, 0, 8, true) as $host => $hits) {
        $topSources[] = ['source' => $host, 'hits' => $hits];
    }

    $devices = $breakdown['devices'];

    $funnel = $db->fetchOne(
        "SELECT
            COALESCE(SUM(event_type = 'page_view' AND path LIKE '/products%'), 0) AS product_views,
            COALESCE(SUM(event_type = 'page_view' AND path LIKE '/checkout%'), 0) AS checkouts,
            COALESCE(SUM(event_type = 'cart_add'), 0) AS cart_adds,
            COALESCE(SUM(event_type = 'registration'), 0) AS registrations,
            COALESCE(SUM(event_type = 'payment'), 0) AS payments,
            COALESCE(SUM(event_type = 'failed_login'), 0) AS failed_logins
         FROM analytics_events WHERE created_at >= ?",
        [$since]
    );

    $orders = $db->fetchOne(
        "SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS revenue
         FROM orders WHERE created_at >= ? AND status <> 'cancelled'",
        [$since]
    );

    // Hourly series (Addis hours), confirmed traffic only.
    $series = [];
    foreach ($breakdown['series'] as $bucket) {
        $series[] = [
            'hour'  => substr((string) $bucket['hour'], 11, 5),
            'views' => (int) $bucket['views'],
        ];
    }

    echo json_encode([
        'ok' => true,
        'view' => 'summary',
        'range' => $range,
        'generated_at' => addis_time(gmdate('Y-m-d H:i:s')),
        'totals' => [
            'page_views' => (int) ($totals['page_views'] ?? 0),
            'visitors'   => (int) ($totals['visitors'] ?? 0),
            'unique_ips' => (int) ($totals['unique_ips'] ?? 0),
            // Everything filtered out of page_views: page_views + bot_hits = raw.
            'bot_hits'           => (int) ($totals['bot_hits'] ?? 0) + (int) ($totals['unverified'] ?? 0),
            'bot_hits_ua'        => (int) ($totals['bot_hits'] ?? 0),
            'unverified_foreign' => (int) ($totals['unverified'] ?? 0),
        ],
        'orders' => [
            'count'   => (int) ($orders['n'] ?? 0),
            'revenue' => (float) ($orders['revenue'] ?? 0),
        ],
        'funnel' => [
            'product_views'  => (int) ($funnel['product_views'] ?? 0),
            'cart_adds'      => (int) ($funnel['cart_adds'] ?? 0),
            'checkout_views' => (int) ($funnel['checkouts'] ?? 0),
            'orders'         => (int) ($orders['n'] ?? 0),
            'payments'       => (int) ($funnel['payments'] ?? 0),
            'registrations'  => (int) ($funnel['registrations'] ?? 0),
            'failed_logins'  => (int) ($funnel['failed_logins'] ?? 0),
        ],
        'top_pages' => array_map(fn($r) => [
            'path' => (string) $r['path'],
            'views' => (int) $r['views'],
            'visitors' => (int) $r['visitors'],
        ], $topPages),
        'sources' => $topSources,
        'devices' => $devices,
        'series' => $series,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Analytics unavailable: ' . $error->getMessage()]);
}
