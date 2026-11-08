<?php
/**
 * Bareeda Injera - Telegram Alerts
 *
 * Simulation-first delivery: every alert is recorded in telegram_outbox.
 * Nothing leaves the server until an admin explicitly switches on live
 * delivery AND a valid bot token + chat ID are saved.
 */

class TelegramNotifier
{
    public const EVENTS = [
        'new_order'        => 'New orders',
        'new_registration' => 'New customer accounts',
        'contact_message'  => 'Contact form messages',
        'qr_scan'          => 'QR hub scans',
    ];

    public const MAX_ATTEMPTS = 5;

    public static function enabledEvents(): array
    {
        $csv = SiteSettings::get('telegram_events', implode(',', array_keys(self::EVENTS)));
        return array_values(array_intersect(explode(',', $csv), array_keys(self::EVENTS)));
    }

    public static function validToken(string $token): bool
    {
        return (bool) preg_match('/^\d{5,}:[A-Za-z0-9_-]{30,}$/', $token);
    }

    public static function validChatId(string $chatId): bool
    {
        return (bool) preg_match('/^(-?\d{3,}|@[A-Za-z0-9_]{5,})$/', $chatId);
    }

    public static function credentialsReady(): bool
    {
        return self::validToken(SiteSettings::get('telegram_bot_token'))
            && self::validChatId(SiteSettings::get('telegram_chat_id'));
    }

    public static function isLive(): bool
    {
        return SiteSettings::get('telegram_live_enabled', '0') === '1' && self::credentialsReady();
    }

    public static function notify(string $event, array $data): void
    {
        if (!isset(self::EVENTS[$event]) || !in_array($event, self::enabledEvents(), true)) {
            return;
        }
        try {
            self::enqueue($event, self::render($event, $data));
        } catch (Throwable $e) {
            error_log('Telegram notify failed: ' . $e->getMessage());
        }
    }

    public static function enqueue(string $event, string $message): int
    {
        $live = self::isLive();
        $id = (int) Database::getInstance()->insert('telegram_outbox', [
            'event_type' => $event,
            'message'    => mb_substr($message, 0, 4000),
            'status'     => $live ? 'pending' : 'simulated',
        ]);
        if ($live) {
            self::attemptSend($id);
        }
        return $id;
    }

    public static function attemptSend(int $id): array
    {
        $db  = Database::getInstance();
        $row = $db->fetchOne("SELECT * FROM telegram_outbox WHERE id = ?", [$id]);
        if (!$row) {
            return ['ok' => false, 'error' => 'Alert not found.'];
        }
        if ($row['status'] === 'sent') {
            return ['ok' => true, 'error' => ''];
        }
        if (!self::isLive()) {
            return ['ok' => false, 'error' => 'Live delivery is switched off; the alert stays in the outbox.'];
        }

        $db->execute("UPDATE telegram_outbox SET attempts = attempts + 1 WHERE id = ?", [$id]);
        [$ok, $error] = self::httpSend($row['message']);
        if ($ok) {
            $db->execute("UPDATE telegram_outbox SET status = 'sent', sent_at = NOW(), last_error = NULL WHERE id = ?", [$id]);
        } else {
            $db->execute("UPDATE telegram_outbox SET status = 'failed', last_error = ? WHERE id = ?", [mb_substr($error, 0, 255), $id]);
        }
        return ['ok' => $ok, 'error' => $error];
    }

    private static function httpSend(string $message): array
    {
        $url     = 'https://api.telegram.org/bot' . SiteSettings::get('telegram_bot_token') . '/sendMessage';
        $payload = json_encode([
            'chat_id'                  => SiteSettings::get('telegram_chat_id'),
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT        => 10,
                ]);
                $body = curl_exec($ch);
                if ($body === false) {
                    $err = curl_error($ch);
                    curl_close($ch);
                    return [false, 'Network: ' . $err];
                }
                curl_close($ch);
            } else {
                $ctx = stream_context_create(['http' => [
                    'method'        => 'POST',
                    'header'        => "Content-Type: application/json\r\n",
                    'content'       => $payload,
                    'timeout'       => 10,
                    'ignore_errors' => true,
                ]]);
                $body = @file_get_contents($url, false, $ctx);
                if ($body === false) {
                    return [false, 'Network: HTTPS request failed (cURL and allow_url_fopen unavailable).'];
                }
            }

            $json = json_decode((string) $body, true);
            if (is_array($json) && ($json['ok'] ?? false) === true) {
                return [true, ''];
            }
            return [false, 'Telegram: ' . (string) ($json['description'] ?? 'unexpected response')];
        } catch (Throwable $e) {
            return [false, 'Send error: ' . $e->getMessage()];
        }
    }

    public static function retryFailed(int $limit = 20): int
    {
        if (!self::isLive()) {
            return 0;
        }
        $rows = Database::getInstance()->query(
            "SELECT id FROM telegram_outbox
             WHERE (status = 'failed' AND attempts < ?)
                OR (status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
             ORDER BY id ASC LIMIT " . max(1, min(100, $limit)),
            [self::MAX_ATTEMPTS]
        );
        $sent = 0;
        foreach ($rows as $row) {
            if (self::attemptSend((int) $row['id'])['ok']) {
                $sent++;
            }
        }
        return $sent;
    }

    public static function sendDigestIfDue(): ?int
    {
        if (SiteSettings::get('telegram_digest_enabled', '1') !== '1') {
            return null;
        }
        $hour = max(0, min(23, (int) SiteSettings::get('telegram_digest_hour', '8')));
        if ((int) date('G') < $hour) {
            return null;
        }
        $today = date('Y-m-d');
        if (SiteSettings::get('telegram_last_digest_date') === $today) {
            return null;
        }

        $day = date('Y-m-d', strtotime('yesterday'));
        $id  = self::enqueue('daily_digest', self::render('daily_digest', self::digestData($day)));
        Database::getInstance()->execute(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES ('telegram_last_digest_date', ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$today]
        );
        SiteSettings::refresh();
        return $id;
    }

    public static function digestData(string $day): array
    {
        $db = Database::getInstance();
        $traffic = $db->fetchOne(
            "SELECT COUNT(DISTINCT visitor_token) AS visitors,
                    COALESCE(SUM(event_type = 'page_view'), 0) AS page_views,
                    COALESCE(SUM(event_type = 'qr_scan'), 0) AS qr_scans
             FROM analytics_events WHERE DATE(created_at) = ?",
            [$day]
        );
        $orders = $db->fetchOne(
            "SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
             FROM orders WHERE DATE(created_at) = ? AND status <> 'cancelled'",
            [$day]
        );
        $pages = $db->query(
            "SELECT path, COUNT(*) AS views FROM analytics_events
             WHERE event_type = 'page_view' AND DATE(created_at) = ?
             GROUP BY path ORDER BY views DESC, path ASC LIMIT 5",
            [$day]
        );

        return [
            'day'        => $day,
            'visitors'   => (int) ($traffic['visitors'] ?? 0),
            'page_views' => (int) ($traffic['page_views'] ?? 0),
            'qr_scans'   => (int) ($traffic['qr_scans'] ?? 0),
            'orders'     => (int) ($orders['orders'] ?? 0),
            'revenue'    => (float) ($orders['revenue'] ?? 0),
            'pages'      => $pages,
        ];
    }

    public static function eventLabel(string $event): string
    {
        if (isset(self::EVENTS[$event])) {
            return self::EVENTS[$event];
        }
        if ($event === 'daily_digest') {
            return 'Daily activity digest';
        }
        if ($event === 'test') {
            return 'Test alert';
        }
        return $event;
    }

    public static function render(string $event, array $data): string
    {
        $e   = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $now = date('D, d M Y H:i');

        switch ($event) {
            case 'new_order':
                $o     = $data['order'];
                $lines = $data['lines'] ?? [];
                $msg   = '<b>NEW ORDER ' . $e($o['order_number']) . '</b>' . "\n";
                $msg  .= $e(trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')))
                    . (!empty($data['guest']) ? ' (guest checkout)' : ' (registered customer)') . "\n";
                $msg  .= 'Phone: ' . $e($o['phone'] ?? '') . "\n";
                if (!empty($o['email'])) {
                    $msg .= 'Email: ' . $e($o['email']) . "\n";
                }
                $msg .= "\n<b>Items</b>\n";
                foreach ($lines as $line) {
                    $msg .= (int) $line['quantity'] . ' x ' . $e($line['name'])
                        . ' (' . (int) $line['pieces'] . ' injera/pack) - '
                        . $e(formatPrice((float) $line['line_total'])) . "\n";
                }
                $msg .= "\nSubtotal: " . $e(formatPrice((float) $o['subtotal'])) . "\n";
                $msg .= 'Delivery: ' . ((float) $o['delivery_fee'] > 0 ? $e(formatPrice((float) $o['delivery_fee'])) : 'FREE') . "\n";
                $msg .= '<b>Total: ' . $e(formatPrice((float) $o['total'])) . "</b>\n";
                $msg .= 'Payment: ' . $e(self::paymentLabel((string) $o['payment_method'])) . "\n";
                $msg .= 'Deliver to: ' . $e($o['delivery_address']) . "\n";
                if (!empty($o['notes'])) {
                    $msg .= 'Notes: ' . $e($o['notes']) . "\n";
                }
                $msg .= "\nManage: " . APP_URL . '/admin/orders/' . (int) $o['id'];
                return $msg;

            case 'new_registration':
                return "<b>NEW CUSTOMER ACCOUNT</b>\n"
                    . $e($data['name']) . "\n"
                    . 'Email: ' . $e($data['email']) . "\n"
                    . 'Phone: ' . $e($data['phone'] !== '' ? $data['phone'] : 'not provided') . "\n"
                    . 'Time: ' . $now;

            case 'contact_message':
                $body = mb_substr((string) $data['message'], 0, 1500);
                return "<b>CONTACT FORM MESSAGE</b>\n"
                    . $e($data['name']) . ' &lt;' . $e($data['email']) . "&gt;\n"
                    . 'Subject: ' . $e($data['subject'] !== '' ? $data['subject'] : 'none') . "\n\n"
                    . $e($body) . "\n\nTime: " . $now;

            case 'qr_scan':
                $today = (int) Database::getInstance()->fetchOne(
                    "SELECT COUNT(*) AS n FROM analytics_events WHERE event_type = 'qr_scan' AND DATE(created_at) = CURDATE()"
                )['n'];
                return "<b>QR HUB OPENED</b>\n"
                    . "A visitor opened the QR landing page (/links).\n"
                    . 'Scans today: ' . $today . "\n"
                    . 'Time: ' . $now;

            case 'daily_digest':
                $msg = '<b>DAILY ACTIVITY - ' . $e($data['day']) . "</b>\n"
                    . 'Visitors: ' . (int) $data['visitors'] . "\n"
                    . 'Page views: ' . (int) $data['page_views'] . "\n"
                    . 'QR scans: ' . (int) $data['qr_scans'] . "\n"
                    . 'Orders: ' . (int) $data['orders'] . "\n"
                    . 'Revenue: ' . $e(formatPrice((float) $data['revenue'])) . "\n\n"
                    . "<b>Most viewed pages</b>\n";
                if (empty($data['pages'])) {
                    $msg .= "No page views recorded.\n";
                } else {
                    foreach ($data['pages'] as $page) {
                        $msg .= $e($page['path']) . ' - ' . (int) $page['views'] . " views\n";
                    }
                }
                return $msg;

            case 'test':
                return "<b>BAREEDA ALERT TEST</b>\n"
                    . "This is a test alert from the website admin panel.\n"
                    . 'Time: ' . $now;
        }

        return '<b>' . $e(self::eventLabel($event)) . '</b>' . "\n" . 'Time: ' . $now;
    }

    private static function paymentLabel(string $method): string
    {
        $map = ['cod' => 'Cash on delivery'];
        return $map[$method] ?? ucwords(str_replace('_', ' ', $method));
    }
}