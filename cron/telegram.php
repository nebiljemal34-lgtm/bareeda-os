<?php
/**
 * Bareeda Injera - Telegram cron worker (CLI only).
 *
 * cPanel cron example (every 15 minutes):
 *   /usr/local/bin/php /home/CPANEL_USER/bareeda-website/cron/telegram.php
 * Adjust the path to where the project lives on your hosting account.
 * Retries failed alerts and records/sends the daily digest once per day.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SiteSettings.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/TelegramNotifier.php';

$sent   = TelegramNotifier::retryFailed();
$digest = TelegramNotifier::sendDigestIfDue();

echo json_encode([
    'retried_sent'     => $sent,
    'digest_outbox_id' => $digest,
    'live'             => TelegramNotifier::isLive(),
    'timestamp'        => date('Y-m-d H:i:s'),
]), PHP_EOL;