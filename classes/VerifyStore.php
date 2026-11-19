<?php
/**
 * Bareeda Injera — letter verification snapshot store.
 *
 * The factory network is closed; the ERP pushes each letter's frozen public
 * payload here over the authenticated ingest endpoint. The public verify
 * page reads from this table FIRST — no factory connection is needed at
 * verification time, and the site keeps working when the factory is offline.
 */

require_once __DIR__ . '/../config/database.php';

class VerifyStore
{
    /**
     * Upsert one verification snapshot (the ERP's ingest payload).
     * @return bool
     */
    public static function upsert(array $payload): bool
    {
        $db = Database::getInstance();

        $token = (string) ($payload['token'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_-]{20,44}$/', $token)) {
            return false;
        }

        $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];
        $state  = ($payload['state'] ?? 'Active') === 'Revoked' ? 'Revoked' : 'Active';

        $db->execute(
            'INSERT INTO letter_verifications
                (token, reference, state, check_code, sealed, key_id, verify_url,
                 payload, issued_at, revoked_at, revoked_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 reference = VALUES(reference),
                 state = VALUES(state),
                 check_code = VALUES(check_code),
                 sealed = VALUES(sealed),
                 key_id = VALUES(key_id),
                 verify_url = VALUES(verify_url),
                 payload = VALUES(payload),
                 issued_at = VALUES(issued_at),
                 revoked_at = VALUES(revoked_at),
                 revoked_reason = VALUES(revoked_reason)',
            [
                $token,
                mb_substr((string) ($result['reference'] ?? ''), 0, 64),
                $state,
                substr((string) ($result['checkCode'] ?? ''), 0, 9),
                !empty($result['sealed']) ? 1 : 0,
                substr((string) ($result['keyId'] ?? ''), 0, 16),
                mb_substr((string) ($payload['verifyUrl'] ?? ''), 0, 512),
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                mb_substr((string) ($payload['issuedAt'] ?? ''), 0, 40),
                mb_substr((string) ($payload['revokedAt'] ?? ''), 0, 40),
                mb_substr((string) ($payload['revokedReason'] ?? ''), 0, 500),
            ]
        );
        return true;
    }

    /**
     * The stored public payload for a token, or null when not replicated.
     * @return array|null
     */
    public static function find(string $token): ?array
    {
        $row = Database::getInstance()->fetchOne(
            'SELECT payload FROM letter_verifications WHERE token = ? LIMIT 1',
            [$token]
        );
        if (!$row) {
            return null;
        }
        $payload = json_decode((string) $row['payload'], true);
        return is_array($payload) ? $payload : null;
    }
}
