<?php

require_once __DIR__ . '/BaseController.php';

class AnalyticsController extends BaseController
{
    public function qrScan(): void
    {
        Analytics::trackQrScan();
        TelegramNotifier::notify('qr_scan', []);
        http_response_code(204);
    }
}
