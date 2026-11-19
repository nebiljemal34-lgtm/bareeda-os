-- ============================================================
-- Letter verification — snapshot replication from the ERP
--
-- The factory network is closed, so the public website verifies letters
-- from this local table. The ERP pushes each letter's frozen public
-- payload here at issue/reissue/revoke over an authenticated ingest
-- endpoint. Run once in phpMyAdmin.
-- ============================================================

CREATE TABLE IF NOT EXISTS `letter_verifications` (
    `token`          VARCHAR(64)    NOT NULL,
    `reference`      VARCHAR(64)    NOT NULL DEFAULT '',
    `state`          ENUM('Active','Revoked') NOT NULL DEFAULT 'Active',
    `check_code`     CHAR(9)        NOT NULL DEFAULT '',
    `sealed`         TINYINT(1)     NOT NULL DEFAULT 0,
    `key_id`         VARCHAR(16)    NOT NULL DEFAULT '',
    `verify_url`     VARCHAR(512)   NOT NULL DEFAULT '',
    `payload`        MEDIUMTEXT     NOT NULL,
    `issued_at`      VARCHAR(40)    NOT NULL DEFAULT '',
    `revoked_at`     VARCHAR(40)    NOT NULL DEFAULT '',
    `revoked_reason` VARCHAR(500)   NOT NULL DEFAULT '',
    `updated_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`token`),
    KEY `idx_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
