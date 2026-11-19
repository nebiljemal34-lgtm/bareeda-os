<?php
/**
 * Bareeda Injera — hourly Activity Sentinel digest (CLI).
 *
 * cPanel cron: /usr/local/bin/php /home/bareedzr/public_html/bareeda-os/cron/activity-digest.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../classes/ActivityDigest.php';

try {
    $id = ActivityDigest::send(3600);
    echo json_encode([
        'digest_outbox_id' => $id,
        'timestamp'        => date('c'),
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $error) {
    http_response_code(500);
    echo 'Digest failed: ' . $error->getMessage() . PHP_EOL;
}
