<?php
/**
 * Activity Sentinel status probe. Secret-keyed (cron secret), read-only.
 * Shows telegram config state and the latest outbox deliveries.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SiteSettings.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/TelegramNotifier.php';

header('Content-Type: text/plain; charset=utf-8');
$given  = (string) ($_GET['key'] ?? '');
$secret = (string) CRON_SECRET_KEY;
if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

try {
    $db = Database::getInstance();
    echo "Telegram live: " . (TelegramNotifier::isLive() ? 'YES' : 'NO') . "\n";
    echo "Events enabled: " . TelegramNotifier::enabledEvents() ? implode(',', TelegramNotifier::enabledEvents()) . "\n" : "\n";
    echo "Digest hour: " . SiteSettings::get('telegram_digest_hour', '?') . " (daily)\n";
    echo "Server time: " . date('Y-m-d H:i:s') . "\n\n";

    echo "Latest outbox deliveries:\n";
    $rows = $db->query(
        "SELECT id, event_type, status, created_at, sent_at
         FROM telegram_outbox ORDER BY id DESC LIMIT 8"
    );
    foreach ($rows as $row) {
        printf(
            "  #%d %-16s %-9s created %s sent %s\n",
            $row['id'], $row['event_type'], $row['status'],
            substr((string) $row['created_at'], 5, 14), substr((string) $row['sent_at'], 5, 14)
        );
    }

    $hourly = $db->fetchOne(
        "SELECT COUNT(*) AS n, MAX(sent_at) AS last FROM telegram_outbox
         WHERE event_type = 'hourly_digest'"
    );
    echo "\nHourly digests delivered so far: " . (int) ($hourly['n'] ?? 0);
    if (!empty($hourly['last'])) {
        echo " (last: " . substr((string) $hourly['last'], 5, 14) . ")";
    }
    echo "\n";
} catch (Throwable $error) {
    http_response_code(500);
    echo "Status failed: " . $error->getMessage() . "\n";
}
