<?php
/**
 * View: Contact Page
 */

$contactPhone = setting('contact_phone', APP_PHONE);
$contactEmail = setting('contact_email', APP_EMAIL);
$businessCity = setting('business_city', 'Dire Dawa');
$businessAddress = setting('business_address', 'Jerba Aliyi');
$fullAddress = $businessAddress;
if (stripos($fullAddress, $businessCity) === false) {
    $fullAddress .= ', ' . $businessCity;
}
if (stripos($fullAddress, 'Ethiopia') === false) {
    $fullAddress .= ', Ethiopia';
}
$phoneHref = preg_replace('/[^0-9+]/', '', $contactPhone);
?>

<!-- ═══ Page Header ═══ -->
<section class="page-header">
    <h1>Contact Us</h1>
    <p class="amharic-subtitle">We'd love to hear from you. Reach out anytime.</p>
</section>

<!-- ═══ Contact Content ═══ -->
<section class="section">
    <div class="container">
        <div class="grid grid-2" style="gap: 48px; align-items: start;">

            <!-- Contact Info -->
            <div>
                <h2 style="color: var(--gold); margin-bottom: 24px;">Get In Touch</h2>

                <div class="contact-info-card">
                    <div class="contact-info-icon" style="color: var(--gold);"><?= icon('phone', 22) ?></div>
                    <div class="contact-info-text">
                        <h4>Phone</h4>
                        <p><a href="tel:<?= sanitize($phoneHref) ?>" style="color: inherit;"><?= sanitize($contactPhone) ?></a></p>
                        <p style="font-size: 0.78rem; opacity: 0.7;">Call or Telegram</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon" style="color: var(--gold);"><?= icon('mail', 22) ?></div>
                    <div class="contact-info-text">
                        <h4>Email</h4>
                        <p><a href="mailto:<?= sanitize($contactEmail) ?>" style="color: inherit; overflow-wrap: anywhere;"><?= sanitize($contactEmail) ?></a></p>
                        <p style="font-size: 0.78rem; opacity: 0.7;">We reply within 24 hours</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon" style="color: var(--gold);"><?= icon('map-pin', 22) ?></div>
                    <div class="contact-info-text">
                        <h4>Address</h4>
                        <p><?= sanitize($fullAddress) ?></p>
                        <p style="font-size: 0.78rem; opacity: 0.7;">Local delivery across <?= sanitize($businessCity) ?></p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon" style="color: var(--gold);"><?= icon('clock', 22) ?></div>
                    <div class="contact-info-text">
                        <h4>Business Hours</h4>
                        <p>Monday &ndash; Saturday: 6:00 AM &ndash; 6:00 PM</p>
                        <p>Sunday: 7:00 AM &ndash; 2:00 PM</p>
                    </div>
                </div>

                <!-- Social Links -->
                <div style="margin-top: 32px;">
                    <h3 style="color: var(--gold); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">Follow Us</h3>
                    <div class="footer-social">
                        <?php if (setting('telegram_url') !== ''): ?>
                            <a href="<?= sanitize(setting('telegram_url')) ?>" title="Telegram" aria-label="Telegram" target="_blank" rel="noopener"><?= icon('send', 18) ?></a>
                        <?php endif; ?>
                        <?php if (setting('facebook_url') !== ''): ?>
                            <a href="<?= sanitize(setting('facebook_url')) ?>" title="Facebook" aria-label="Facebook" target="_blank" rel="noopener"><?= icon('facebook', 18) ?></a>
                        <?php endif; ?>
                        <?php if (setting('instagram_url') !== ''): ?>
                            <a href="<?= sanitize(setting('instagram_url')) ?>" title="Instagram" aria-label="Instagram" target="_blank" rel="noopener"><?= icon('instagram', 18) ?></a>
                        <?php endif; ?>
                        <a href="tel:<?= sanitize($phoneHref) ?>" title="Call Us" aria-label="Call Bareeda"><?= icon('phone', 18) ?></a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="form-card" style="max-width: 100%;">
                <h2>Send a Message</h2>
                <form method="POST" action="/contact">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label" for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-input" required
                               value="<?= htmlspecialchars(old('name')) ?>"
                               placeholder="Your full name">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" required
                               value="<?= htmlspecialchars(old('email')) ?>"
                               placeholder="you@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-input"
                               value="<?= htmlspecialchars(old('subject')) ?>"
                               placeholder="What is this about?">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="message">Message *</label>
                        <textarea id="message" name="message" class="form-input" rows="5" required
                                  placeholder="Tell us how we can help..."><?= htmlspecialchars(old('message')) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
                </form>
            </div>
        </div>

        <a class="card mt-4" href="https://maps.google.com/?q=Jerba+Aliyi+Dire+Dawa+Ethiopia" target="_blank" rel="noopener" style="display: flex; align-items: center; justify-content: center; gap: 20px; min-height: 180px; text-align: left; text-decoration: none;">
            <div style="color: var(--gold); flex-shrink: 0;"><?= icon('map-pin', 48) ?></div>
            <div>
                <h2 style="color: var(--gold); font-size: 1.2rem; margin-bottom: 8px;">Find Our Bakery</h2>
                <p class="text-muted"><?= sanitize($fullAddress) ?></p>
                <p style="color: var(--gold); font-size: 0.85rem; margin-top: 12px;">Open in Google Maps &nearr;</p>
            </div>
        </a>
    </div>
</section>
