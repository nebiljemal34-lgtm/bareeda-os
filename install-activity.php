<?php
/**
 * Activity Sentinel — one-time installer.
 *
 * 1. Widens analytics_events: per-event IP, country, user agent, referrer,
 *    user id, and the new business event types.
 * 2. Configures the Telegram notifier (bot token, chat id, live switch).
 * 3. Removes itself. Requires the cron secret as ?key=<CRON_SECRET_KEY>.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$given  = (string) ($_GET['key'] ?? '');
$secret = (string) CRON_SECRET_KEY;
if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo "Forbidden — run with ?key=<CRON_SECRET_KEY>.\n";
    exit;
}

/** The bareeda-website installer may be an older schema — the analytics
 * migration tolerates an existing table. Values come from the ERP's
 * notification bot (never committed). */
$BOT_TOKEN = (string) (getenv('ACTIVITY_BOT_TOKEN') ?: '');
$CHAT_ID   = (string) (getenv('ACTIVITY_CHAT_ID') ?: '');

try {
    $db = Database::getInstance()->getPdo();

    // 1. analytics_events: add context columns + widen event types.
    $columns = $db->query("SHOW COLUMNS FROM analytics_events")->fetchAll(PDO::FETCH_ASSOC);
    $names   = array_column($columns, 'Field');
    $alters  = [
        'ip'         => "ADD COLUMN `ip` VARCHAR(45) NOT NULL DEFAULT ''",
        'country'    => "ADD COLUMN `country` CHAR(2) NOT NULL DEFAULT ''",
        'user_agent' => "ADD COLUMN `user_agent` VARCHAR(255) NOT NULL DEFAULT ''",
        'referrer'   => "ADD COLUMN `referrer` VARCHAR(255) NOT NULL DEFAULT ''",
        'user_id'    => "ADD COLUMN `user_id` INT UNSIGNED NULL DEFAULT NULL",
    ];
    foreach ($alters as $name => $sql) {
        if (!in_array($name, $names, true)) {
            $db->exec("ALTER TABLE `analytics_events` $sql");
            echo "analytics_events: added $name\n";
        }
    }
    $type = $db->query("SHOW COLUMNS FROM analytics_events LIKE 'event_type'")->fetch(PDO::FETCH_ASSOC);
    if ($type && strpos($type['Type'], 'cart_add') === false) {
        $db->exec("ALTER TABLE `analytics_events`
            MODIFY `event_type` ENUM('page_view','qr_scan','cart_add','registration','payment','failed_login','verification_scan')
            NOT NULL");
        echo "analytics_events: event types widened\n";
    }

    // 2. Telegram credentials (Activity Sentinel channel).
    if ($BOT_TOKEN !== '' && $CHAT_ID !== '') {
        $stmt = $db->prepare(
            "INSERT INTO site_settings (setting_key, setting_value, updated_by)
             VALUES (:k, :v, 'activity-installer')
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        foreach ([
            'telegram_bot_token'    => $BOT_TOKEN,
            'telegram_chat_id'      => $CHAT_ID,
            'telegram_live_enabled' => '1',
            'telegram_digest_enabled' => '1',
            'telegram_digest_hour'  => '8',
            'telegram_events'       => 'new_order,new_registration,contact_message,qr_scan,payment_event',
        ] as $key => $value) {
            $stmt->execute([':k' => $key, ':v' => $value]);
        }
        echo "site_settings: telegram configured (bot + chat + live enabled)\n";
    } else {
        echo "site_settings: ACTIVITY_BOT_TOKEN / ACTIVITY_CHAT_ID not set — telegram config skipped\n";
    }

    if (@unlink(__FILE__)) {
        echo "Installer removed itself. Activity Sentinel is ready.\n";
    } else {
        echo "WARNING: could not delete install-activity.php — delete it now.\n";
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo "Install failed: " . $error->getMessage() . "\n";
}
