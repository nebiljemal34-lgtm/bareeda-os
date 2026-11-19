<?php
/**
 * One-time Activity Sentinel delivery test. Self-removing.
 * Requires the cron secret as ?key=<CRON_SECRET_KEY>.
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

$msg  = "<b>ACTIVITY SENTINEL IS LIVE</b>\n";
$msg .= "The website now reports to this chat:\n";
$msg .= "• New orders (items, total, customer, city)\n";
$msg .= "• New registrations · Payment activity\n";
$msg .= "• Verification scans · Failed logins\n";
$msg .= "• Hourly digest: views, visitors, new IPs\n";
$msg .= "\nTime: " . date('D d M Y H:i') . " (Dire Dawa)";

$id = TelegramNotifier::enqueue('test', $msg);
$result = TelegramNotifier::attemptSend($id);
echo "outbox #$id — " . ($result['ok'] ? "DELIVERED to your Telegram ✓" : "FAILED: " . $result['error']) . "\n";
echo @unlink(__FILE__) ? "Test script removed itself.\n" : "Delete test-alert.php manually.\n";
