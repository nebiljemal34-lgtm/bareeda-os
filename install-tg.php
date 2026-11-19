<?php
/**
 * One-time: configure the Telegram channel for the Activity Sentinel.
 * Requires the cron secret as ?key=<CRON_SECRET_KEY>. Self-removing.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');
$given  = (string) ($_GET['key'] ?? '');
$secret = (string) CRON_SECRET_KEY;
if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}
try {
    $db = Database::getInstance()->getPdo();
    $token = defined('ACTIVITY_BOT_TOKEN') ? (string) ACTIVITY_BOT_TOKEN : '';
    $chat  = defined('ACTIVITY_CHAT_ID') ? (string) ACTIVITY_CHAT_ID : '';
    if ($token === '' || $chat === '') {
        echo "Credentials missing from local-overrides.\n";
        exit;
    }
    $stmt = $db->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, updated_by)
         VALUES (:k, :v, 'activity-sentinel')
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    foreach ([
        'telegram_bot_token'      => $token,
        'telegram_chat_id'        => $chat,
        'telegram_live_enabled'   => '1',
        'telegram_digest_enabled' => '1',
        'telegram_digest_hour'    => '8',
        'telegram_events'         => 'new_order,new_registration,contact_message,qr_scan,payment_event',
    ] as $key => $value) {
        $stmt->execute([':k' => $key, ':v' => $value]);
    }
    echo "Telegram configured: bot " . substr($token, 0, 12) . "… → chat $chat\n";
    echo @unlink(__FILE__) ? "Installer removed itself.\n" : "WARNING: delete install-tg.php manually.\n";
} catch (Throwable $error) {
    http_response_code(500);
    echo "Failed: " . $error->getMessage() . "\n";
}
