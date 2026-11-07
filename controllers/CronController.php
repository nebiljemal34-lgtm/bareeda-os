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
        // Verify cron secret key
        $providedKey = $_GET['key'] ?? '';
        $secretKey   = defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : '';

        if (empty($secretKey) || empty($providedKey) || !hash_equals($secretKey, $providedKey)) {
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
}
