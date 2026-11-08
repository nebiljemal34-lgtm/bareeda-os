<form method="POST" action="/admin/settings">
    <?= csrf_field() ?>

    <div class="adm-grid-2">
        <div>
            <div class="adm-card adm-mb-20">
                <div class="adm-card-header"><div class="adm-card-title">Business Information</div></div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="site_name">Website name</label>
                    <input class="adm-form-input" id="site_name" name="site_name" required value="<?= sanitize($settings['site_name'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="contact_email">Contact email</label>
                    <input class="adm-form-input" type="email" id="contact_email" name="contact_email" required value="<?= sanitize($settings['contact_email'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="contact_phone">Contact phone</label>
                    <input class="adm-form-input" id="contact_phone" name="contact_phone" value="<?= sanitize($settings['contact_phone'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="business_city">Business city</label>
                    <input class="adm-form-input" id="business_city" name="business_city" required value="<?= sanitize($settings['business_city'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="business_address">Public address</label>
                    <input class="adm-form-input" id="business_address" name="business_address" value="<?= sanitize($settings['business_address'] ?? '') ?>">
                </div>
            </div>

            <div class="adm-card">
                <div class="adm-card-header"><div class="adm-card-title">Social Links</div></div>
                <?php foreach (['telegram_url' => 'Telegram URL', 'facebook_url' => 'Facebook URL', 'instagram_url' => 'Instagram URL'] as $key => $label): ?>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="<?= $key ?>"><?= $label ?></label>
                    <input class="adm-form-input" type="url" id="<?= $key ?>" name="<?= $key ?>" value="<?= sanitize($settings[$key] ?? '') ?>" placeholder="https://">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div class="adm-card adm-mb-20">
                <div class="adm-card-header"><div class="adm-card-title">Ordering and Delivery</div></div>
                <label style="display:flex;align-items:center;gap:10px;margin-bottom:20px;cursor:pointer;">
                    <input type="checkbox" name="accepting_orders" value="1" <?= ($settings['accepting_orders'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span><strong>Accept new orders</strong><br><span class="adm-text-muted adm-text-sm">Disable this to pause checkout without taking the website offline.</span></span>
                </label>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="default_delivery_fee">Default delivery fee (ETB)</label>
                    <input class="adm-form-input" type="number" min="0" step="0.01" id="default_delivery_fee" name="default_delivery_fee" value="<?= sanitize($settings['default_delivery_fee'] ?? '50') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="free_delivery_min_pieces">Free delivery above (injera pieces)</label>
                    <input class="adm-form-input" type="number" min="0" step="1" id="free_delivery_min_pieces" name="free_delivery_min_pieces" value="<?= sanitize($settings['free_delivery_min_pieces'] ?? '50') ?>">
                    <span class="adm-text-muted adm-text-sm">Orders with strictly more than this many injera get free delivery. Exactly this many still pays the fee.</span>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="min_order_amount">Minimum order amount (ETB)</label>
                    <input class="adm-form-input" type="number" min="0" step="0.01" id="min_order_amount" name="min_order_amount" value="<?= sanitize($settings['min_order_amount'] ?? '100') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="announcement">Website announcement</label>
                    <textarea class="adm-form-input" id="announcement" name="announcement" rows="3" placeholder="Optional message shown above every public page"><?= sanitize($settings['announcement'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="adm-card adm-mb-20">
                <div class="adm-card-header"><div class="adm-card-title">Payment Integration</div></div>
                <ul class="adm-info-list">
                    <li><span class="info-label">Mode</span><span class="info-value"><span class="adm-badge adm-badge-pending"><?= sanitize(strtoupper($payment_mode)) ?></span></span></li>
                    <li><span class="info-label">Callback path</span><span class="info-value" style="word-break:break-all;"><?= sanitize($webhook_url) ?></span></li>
                </ul>
                <p class="adm-text-muted adm-text-sm" style="margin-top:14px;">Simulation never contacts a bank or wallet. Live payments remain unavailable until a merchant-approved provider adapter and callback secret are configured.</p>
            </div>

            <div class="adm-card adm-mb-20">
                <div class="adm-card-header"><div class="adm-card-title">Telegram Alerts</div></div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                    <span class="adm-badge <?= TelegramNotifier::isLive() ? 'adm-badge-delivered' : 'adm-badge-pending' ?>"><?= TelegramNotifier::isLive() ? 'LIVE DELIVERY' : 'SIMULATION MODE' ?></span>
                    <a href="/admin/telegram" class="adm-btn adm-btn-sm adm-btn-outline">Open alert outbox</a>
                </div>
                <p class="adm-text-muted adm-text-sm" style="margin-bottom:16px;">
                    Orders, new accounts, contact messages and QR scans become instant alerts. All visitor traffic
                    (visits, page views, interactions, revenue) arrives as one daily digest.
                </p>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="telegram_bot_token">Bot token (from @BotFather)</label>
                    <input class="adm-form-input" type="password" id="telegram_bot_token" name="telegram_bot_token" autocomplete="new-password"
                           placeholder="<?= ($settings['telegram_bot_token'] ?? '') !== '' ? 'Saved - leave blank to keep it' : '123456789:AA...' ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="telegram_chat_id">Your chat ID</label>
                    <input class="adm-form-input" id="telegram_chat_id" name="telegram_chat_id" value="<?= sanitize($settings['telegram_chat_id'] ?? '') ?>" placeholder="123456789">
                    <span class="adm-text-muted adm-text-sm">Send your bot any message, then open api.telegram.org/bot&lt;token&gt;/getUpdates and read chat.id.</span>
                </div>
                <label style="display:flex;align-items:center;gap:10px;margin-bottom:20px;cursor:pointer;">
                    <input type="checkbox" name="telegram_live_enabled" value="1" <?= ($settings['telegram_live_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <span><strong>Send alerts to Telegram (live delivery)</strong><br><span class="adm-text-muted adm-text-sm">Off = simulation: alerts are only recorded in the outbox for review.</span></span>
                </label>
                <div class="adm-form-group">
                    <span class="adm-form-label">Instant alerts for</span>
                    <?php $enabledEvents = TelegramNotifier::enabledEvents(); ?>
                    <?php foreach (TelegramNotifier::EVENTS as $key => $label): ?>
                    <label style="display:flex;align-items:center;gap:10px;margin-bottom:10px;cursor:pointer;">
                        <input type="checkbox" name="telegram_events[]" value="<?= $key ?>" <?= in_array($key, $enabledEvents, true) ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <label style="display:flex;align-items:center;gap:10px;margin-bottom:20px;cursor:pointer;">
                    <input type="checkbox" name="telegram_digest_enabled" value="1" <?= ($settings['telegram_digest_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span><strong>Daily activity digest</strong><br><span class="adm-text-muted adm-text-sm">Yesterday's visitors, page views, QR scans, orders and revenue in one message.</span></span>
                </label>
                <div class="adm-form-group">
                    <label class="adm-form-label" for="telegram_digest_hour">Digest hour (0-23, server time)</label>
                    <input class="adm-form-input" type="number" min="0" max="23" step="1" id="telegram_digest_hour" name="telegram_digest_hour" value="<?= sanitize($settings['telegram_digest_hour'] ?? '8') ?>">
                </div>
                <button type="submit" form="telegram-test-form" class="adm-btn adm-btn-outline" style="width:100%;">Send test alert</button>
            </div>

            <button type="submit" class="adm-btn adm-btn-gold" style="width:100%;padding:12px;">Save Website Settings</button>
        </div>
    </div>
</form>

<form id="telegram-test-form" method="POST" action="/admin/settings/telegram-test">
    <?= csrf_field() ?>
</form>
