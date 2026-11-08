<?php
/**
 * Bareeda Injera - Cron Controller
 *
 * Handles scheduled tasks (subscription processing).
 * Protected by a secret key to prevent unauthorized access.
 */

require_once __DIR__ . '/BaseController.php';

class CronController extends BaseController
{
    /**
     * GET /cron/process-subscriptions?key=CRON_SECRET_KEY
     *
     * Requires a valid cron secret key as query parameter.
     * Set CRON_SECRET_KEY in config.php for production.
     */
    public function processSubscriptions(): void
    {
        // SEC-02 FIX: Fail closed if secret key is unset or placeholder
        if (CRON_SECRET_IS_PLACEHOLDER) {
            http_response_code(503);
            json_response(['success' => false, 'error' => 'Cron not configured'], 503);
            return;
        }

        // Verify cron secret key
        $providedKey = $_GET['key'] ?? '';
        $secretKey   = CRON_SECRET_KEY;

        if (empty($providedKey) || !hash_equals($secretKey, $providedKey)) {
            http_response_code(403);
            json_response(['success' => false, 'error' => 'Forbidden'], 403);
            return;
        }

        $subModel = new Subscription();
        $result   = $subModel->processDueSubscriptions();

        json_response([
            'success'   => true,
            'processed' => $result['processed'],
            'failed'    => $result['failed'],
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * GET /cron/telegram?key=CRON_SECRET_KEY
     *
     * Retries failed alerts and sends the daily activity digest once per day.
     * cPanel cron can call this URL or run cron/telegram.php via CLI.
     */
    public function telegram(): void
    {
        // SEC-02 FIX: Fail closed if secret key is unset or placeholder
        if (CRON_SECRET_IS_PLACEHOLDER) {
            http_response_code(503);
            json_response(['success' => false, 'error' => 'Cron not configured'], 503);
            return;
        }

        $providedKey = $_GET['key'] ?? '';
        $secretKey   = CRON_SECRET_KEY;

        if (empty($providedKey) || !hash_equals($secretKey, $providedKey)) {
            http_response_code(403);
            json_response(['success' => false, 'error' => 'Forbidden'], 403);
            return;
        }

        json_response([
            'success'         => true,
            'retried_sent'    => TelegramNotifier::retryFailed(),
            'digest_outbox_id' => TelegramNotifier::sendDigestIfDue(),
            'live'            => TelegramNotifier::isLive(),
            'timestamp'       => date('Y-m-d H:i:s'),
        ]);
    }
}
