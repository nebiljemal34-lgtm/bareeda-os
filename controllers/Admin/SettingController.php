<?php

require_once __DIR__ . '/AdminBaseController.php';

class AdminSettingController extends AdminBaseController
{
    public function index(): void
    {
        $this->render('admin/settings/index', [
            'pageTitle' => 'Website Settings',
            'settings' => SiteSettings::all(),
            'payment_mode' => PAYMENT_MODE,
            'webhook_url' => APP_URL . '/payment/callback/{provider}',
        ]);
    }

    public function update(): void
    {
        $keys = [
            'site_name', 'contact_email', 'contact_phone', 'business_city', 'business_address',
            'default_delivery_fee', 'free_delivery_min_pieces', 'min_order_amount', 'announcement',
            'telegram_url', 'facebook_url', 'instagram_url',
        ];
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        // SEC-09 FIX: Sanitize announcement — strip HTML and cap length
        if (isset($values['announcement'])) {
            $values['announcement'] = substr(strip_tags($values['announcement']), 0, 2000);
        }
        $values['accepting_orders'] = isset($_POST['accepting_orders']) ? '1' : '0';

        if ($values['site_name'] === '' || $values['business_city'] === '') {
            flash('error', 'Site name and business city are required.');
            redirect('/admin/settings');
        }
        if (!is_valid_email($values['contact_email'])) {
            flash('error', 'Enter a valid contact email address.');
            redirect('/admin/settings');
        }
        foreach (['default_delivery_fee', 'min_order_amount'] as $key) {
            if (!is_numeric($values[$key]) || (float) $values[$key] < 0) {
                flash('error', 'Order amounts and delivery fees must be zero or greater.');
                redirect('/admin/settings');
            }
            $values[$key] = number_format((float) $values[$key], 2, '.', '');
        }
        if (!ctype_digit($values['free_delivery_min_pieces'])) {
            flash('error', 'Free delivery piece count must be a whole number zero or greater.');
            redirect('/admin/settings');
        }
        $values['free_delivery_min_pieces'] = (string) (int) $values['free_delivery_min_pieces'];
        foreach (['telegram_url', 'facebook_url', 'instagram_url'] as $key) {
            if ($values[$key] !== '') {
                $url = filter_var($values[$key], FILTER_VALIDATE_URL);
                $scheme = strtolower((string) parse_url($values[$key], PHP_URL_SCHEME));
                if ($url === false || !in_array($scheme, ['http', 'https'], true)) {
                    flash('error', 'Social links must be valid HTTP or HTTPS URLs.');
                    redirect('/admin/settings');
                }
            }
        }

        $values['telegram_chat_id'] = trim((string) ($_POST['telegram_chat_id'] ?? ''));
        $values['telegram_live_enabled'] = isset($_POST['telegram_live_enabled']) ? '1' : '0';
        $values['telegram_digest_enabled'] = isset($_POST['telegram_digest_enabled']) ? '1' : '0';
        $values['telegram_digest_hour'] = trim((string) ($_POST['telegram_digest_hour'] ?? '8'));
        $events = array_intersect((array) ($_POST['telegram_events'] ?? []), array_keys(TelegramNotifier::EVENTS));
        $values['telegram_events'] = implode(',', $events);

        $token = trim((string) ($_POST['telegram_bot_token'] ?? ''));
        if ($token !== '') {
            if (!TelegramNotifier::validToken($token)) {
                flash('error', 'Bot token must look like 123456789:AA... (digits, colon, then the secret part from BotFather).');
                redirect('/admin/settings');
            }
            $values['telegram_bot_token'] = $token;
        }
        if ($values['telegram_chat_id'] !== '' && !TelegramNotifier::validChatId($values['telegram_chat_id'])) {
            flash('error', 'Chat ID must be a numeric ID (like 123456789 or -1001234567890) or an @channelname.');
            redirect('/admin/settings');
        }
        if (!ctype_digit($values['telegram_digest_hour']) || (int) $values['telegram_digest_hour'] > 23) {
            flash('error', 'Digest hour must be a whole number between 0 and 23.');
            redirect('/admin/settings');
        }
        $effectiveToken = $values['telegram_bot_token'] ?? SiteSettings::get('telegram_bot_token');
        if ($values['telegram_live_enabled'] === '1'
            && (!TelegramNotifier::validToken($effectiveToken) || !TelegramNotifier::validChatId($values['telegram_chat_id']))) {
            flash('error', 'Live Telegram delivery needs a valid bot token and chat ID. Save them first, then switch live mode on.');
            redirect('/admin/settings');
        }

        SiteSettings::updateMany($values, currentUserId());
        flash('success', 'Website settings saved and applied.');
        redirect('/admin/settings');
    }

    public function telegramTest(): void
    {
        $id = TelegramNotifier::enqueue('test', TelegramNotifier::render('test', []));
        $row = Database::getInstance()->fetchOne("SELECT status, last_error FROM telegram_outbox WHERE id = ?", [$id]);
        if (($row['status'] ?? '') === 'sent') {
            flash('success', 'Test alert delivered to your Telegram chat.');
        } elseif (($row['status'] ?? '') === 'failed') {
            flash('error', 'Telegram rejected the test alert: ' . ($row['last_error'] ?? 'unknown error'));
        } else {
            flash('success', 'Test alert recorded in the outbox (simulation mode). Turn on live delivery to send alerts to Telegram.');
        }
        redirect('/admin/settings');
    }
}
