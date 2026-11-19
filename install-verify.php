<?php
/**
 * Letter verification — one-time installer.
 *
 * Creates the letter_verifications table, then removes itself. Requires the
 * ingest secret as ?key=<ERP_INGEST_SECRET> so only the operator can run it.
 *
 * Usage: https://www.bareeda-injera.com/install-verify.php?key=<secret>
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$given  = (string) ($_GET['key'] ?? '');
$secret = defined('ERP_INGEST_SECRET') ? (string) ERP_INGEST_SECRET : '';
if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo "Forbidden — run with ?key=<ERP_INGEST_SECRET>.\n";
    exit;
}

try {
    $db = Database::getInstance()->getPdo();
    $db->exec(
        'CREATE TABLE IF NOT EXISTS `letter_verifications` (
            `token`          VARCHAR(64)    NOT NULL,
            `reference`      VARCHAR(64)    NOT NULL DEFAULT \'\',
            `state`          ENUM(\'Active\',\'Revoked\') NOT NULL DEFAULT \'Active\',
            `check_code`     CHAR(9)        NOT NULL DEFAULT \'\',
            `sealed`         TINYINT(1)     NOT NULL DEFAULT 0,
            `key_id`         VARCHAR(16)    NOT NULL DEFAULT \'\',
            `verify_url`     VARCHAR(512)   NOT NULL DEFAULT \'\',
            `payload`        MEDIUMTEXT     NOT NULL,
            `issued_at`      VARCHAR(40)    NOT NULL DEFAULT \'\',
            `revoked_at`     VARCHAR(40)    NOT NULL DEFAULT \'\',
            `revoked_reason` VARCHAR(500)   NOT NULL DEFAULT \'\',
            `updated_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`token`),
            KEY `idx_reference` (`reference`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    echo "letter_verifications table ready.\n";

    // Self-remove so the installer cannot be re-run by an outsider.
    if (@unlink(__FILE__)) {
        echo "Installer removed itself. You are live.\n";
    } else {
        echo "WARNING: could not delete install-verify.php — delete it from File Manager now.\n";
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo "Install failed: " . $error->getMessage() . "\n";
}
