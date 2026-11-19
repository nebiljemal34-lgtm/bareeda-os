<?php
/**
 * Bareeda Injera — hourly Activity Sentinel digest (CLI or secret-keyed web).
 *
 * One tidy message per hour: page views, unique visitors, top pages,
 * cart adds, orders, registrations, failed logins, and the new IP addresses
 * first seen in that hour.
 *
 * cPanel cron (hourly):  /usr/local/bin/php /home/CPANEL_USER/bareeda-website/cron/activity-digest.php
 * Manual web test:       cron/activity-digest.php?key=<CRON_SECRET_KEY>
 */

if (PHP_SAPI !== 'cli') {
    // Web access is allowed only with the cron secret so the endpoint cannot
    // be triggered by outsiders.
    $given  = (string) ($_GET['key'] ?? '');
    $secret = defined('CRON_SECRET_KEY') ? (string) CRON_SECRET_KEY : '';
    if ($secret === '' || !hash_equals($secret, $given)) {
        http_response_code(404);
        exit('Not found');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SiteSettings.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/TelegramNotifier.php';

try {
    $db = Database::getInstance();
    $since = date('Y-m-d H:i:s', time() - 3600);

    $traffic = $db->fetchOne(
        "SELECT COUNT(*) AS page_views,
                COUNT(DISTINCT visitor_token) AS visitors,
                COUNT(DISTINCT ip) AS unique_ips
         FROM analytics_events
         WHERE event_type = 'page_view' AND created_at >= ?",
        [$since]
    );
    $counts = $db->fetchOne(
        "SELECT
            COALESCE(SUM(event_type = 'cart_add'), 0)      AS cart_adds,
            COALESCE(SUM(event_type = 'registration'), 0)  AS registrations,
            COALESCE(SUM(event_type = 'failed_login'), 0)  AS failed_logins,
            COALESCE(SUM(event_type = 'payment'), 0)       AS payments
         FROM analytics_events WHERE created_at >= ?",
        [$since]
    );
    $orders = $db->fetchOne(
        "SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
         FROM orders WHERE created_at >= ? AND status <> 'cancelled'",
        [$since]
    );
    $topPages = $db->query(
        "SELECT path, COUNT(*) AS views
         FROM analytics_events
         WHERE event_type = 'page_view' AND created_at >= ?
         GROUP BY path ORDER BY views DESC LIMIT 5",
        [$since]
    );
    $newIps = $db->query(
        "SELECT ip, country, MIN(created_at) AS first_seen
         FROM analytics_events
         WHERE ip <> '' AND created_at >= ?
           AND NOT EXISTS (
               SELECT 1 FROM analytics_events earlier
                WHERE earlier.ip = analytics_events.ip
                  AND earlier.created_at < ?
           )
         GROUP BY ip, country ORDER BY first_seen ASC LIMIT 12",
        [$since, $since]
    );

    $lines   = [];
    $lines[] = '<b>HOURLY ACTIVITY DIGEST</b> — ' . date('H:00') . '–' . date('H:i');
    $lines[] = '';
    $lines[] = '👁 Page views: ' . (int) ($traffic['page_views'] ?? 0)
        . ' · Visitors: ' . (int) ($traffic['visitors'] ?? 0)
        . ' · Unique IPs: ' . (int) ($traffic['unique_ips'] ?? 0);
    $lines[] = '🛒 Orders: ' . (int) ($orders['orders'] ?? 0)
        . ' · Revenue: ' . number_format((float) ($orders['revenue'] ?? 0), 2) . ' ETB';
    $lines[] = '➕ Cart adds: ' . (int) ($counts['cart_adds'] ?? 0)
        . ' · Registrations: ' . (int) ($counts['registrations'] ?? 0)
        . ' · Payments: ' . (int) ($counts['payments'] ?? 0)
        . ' · Failed logins: ' . (int) ($counts['failed_logins'] ?? 0);

    if (!empty($topPages)) {
        $lines[] = '';
        $lines[] = '<b>Top pages</b>';
        foreach ($topPages as $page) {
            $lines[] = '• ' . htmlspecialchars((string) $page['path'], ENT_QUOTES, 'UTF-8')
                . ' — ' . (int) $page['views'] . ' views';
        }
    }

    if (!empty($newIps)) {
        $lines[] = '';
        $lines[] = '<b>New IP addresses this hour</b>';
        foreach ($newIps as $row) {
            $country = (string) ($row['country'] ?? '') !== '' ? ' (' . $row['country'] . ')' : '';
            $lines[] = '• ' . htmlspecialchars((string) $row['ip'], ENT_QUOTES, 'UTF-8')
                . $country . ' — first seen ' . date('H:i', strtotime((string) $row['first_seen']));
        }
    }

    $message = implode("\n", $lines);
    $id = TelegramNotifier::enqueue('hourly_digest', $message);
    TelegramNotifier::attemptSend($id);

    $output = ['digest_outbox_id' => $id, 'chars' => strlen($message), 'timestamp' => date('c')];
    echo (PHP_SAPI === 'cli' ? json_encode($output, JSON_PRETTY_PRINT) : json_encode($output)) . PHP_EOL;
} catch (Throwable $error) {
    http_response_code(500);
    echo 'Digest failed: ' . $error->getMessage() . PHP_EOL;
}
