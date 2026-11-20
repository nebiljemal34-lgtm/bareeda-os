<?php
/**
 * Activity Sentinel — manual trigger + live status (secret-keyed, read-mostly).
 *
 *   ?key=<CRON_SECRET_KEY>&run=digest   send the digest now
 *   ?key=<CRON_SECRET_KEY>              show live activity summary
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SiteSettings.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/ActivityDigest.php';
require_once __DIR__ . '/../classes/TelegramNotifier.php';

header('Content-Type: text/plain; charset=utf-8');
$given  = (string) ($_GET['key'] ?? '');
$secret = (string) CRON_SECRET_KEY;
if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$db = Database::getInstance();
$run = (string) ($_GET['run'] ?? '');

if ($run === 'digest') {
    $id = ActivityDigest::send(3600, 'ACTIVITY DIGEST (manual test)');
    echo "Digest #$id enqueued + sent → check Telegram.\n";
    return;
}

echo "Telegram live: " . (TelegramNotifier::isLive() ? 'YES' : 'NO') . "\n";
echo "Server time: " . date('Y-m-d H:i:s') . " (Addis)\n\n";


$t = $db->fetchOne(
    "SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_token) AS visitors, COUNT(DISTINCT ip) AS ips
     FROM analytics_events WHERE event_type = 'page_view' AND DATE(created_at + INTERVAL 3 HOUR) = DATE(UTC_TIMESTAMP() + INTERVAL 3 HOUR)"
);
echo "TODAY  views: " . (int) $t['views'] . " · visitors: " . (int) $t['visitors']
    . " · unique IPs: " . (int) $t['ips'] . "\n\n";

echo "Last 10 events (newest first):\n";
$rows = $db->query(
    "SELECT event_type, path, ip, country, created_at
     FROM analytics_events ORDER BY id DESC LIMIT 10"
);
foreach ($rows as $row) {
    printf(
        "  %-26s %s %s %s\n",
        $row['event_type'],
        substr((string) $row['path'], 0, 28),
        ($row['ip'] ?: '-') . ($row['country'] ? ' (' . $row['country'] . ')' : ''),
        date('H:i', strtotime((string) $row['created_at'] . ' UTC')) . ' Addis'
    );
}

echo "\nLatest Telegram deliveries:\n";
foreach ($db->query("SELECT id, event_type, status, sent_at FROM telegram_outbox ORDER BY id DESC LIMIT 6") as $row) {
    printf("  #%d %-16s %-9s %s\n", $row['id'], $row['event_type'], $row['status'], substr((string) $row['sent_at'], 5, 14));
}
