<?php
/**
 * Bareeda Injera — cron trigger over HTTPS.
 *
 * Shared hosts often lack a predictable PHP CLI path, so the cPanel cron
 * calls this endpoint with curl instead. The cron secret authenticates it.
 *
 *   cron-run.php?job=telegram&key=<CRON_SECRET_KEY>   (every 15 minutes)
 *   cron-run.php?job=digest&key=<CRON_SECRET_KEY>     (hourly)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SiteSettings.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/ActivityDigest.php';
require_once __DIR__ . '/../classes/TelegramNotifier.php';

header('Content-Type: application/json; charset=utf-8');

$given  = (string) ($_GET['key'] ?? '');
$secret = (string) CRON_SECRET_KEY;
if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$job = (string) ($_GET['job'] ?? '');

try {
    switch ($job) {
        case 'telegram':
            $retried = TelegramNotifier::retryFailed();
            $digest = TelegramNotifier::sendDigestIfDue();
            echo json_encode([
                'ok' => true,
                'retried_sent' => $retried,
                'daily_digest_outbox_id' => $digest,
                'live' => TelegramNotifier::isLive(),
                'time' => date('c'),
            ]);
            break;

        case 'digest':
            $id = ActivityDigest::send(3600);
            echo json_encode(['ok' => true, 'hourly_digest_outbox_id' => $id, 'time' => date('c')]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown job']);
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $error->getMessage()]);
}
