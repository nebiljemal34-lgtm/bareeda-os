<?php
/**
 * View: Contact Page
 */
?>

<!-- ═══ Page Header ═══ -->
<section class="page-header">
    <h1>Contact Us &middot; አግኙን</h1>
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
                    <div class="contact-info-icon">&#9742;</div>
                    <div class="contact-info-text">
                        <h4>Phone</h4>
                        <p><?= APP_PHONE ?></p>
                        <p style="font-size: 0.78rem; opacity: 0.7;">Call or Telegram</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon">&#9993;</div>
                    <div class="contact-info-text">
                        <h4>Email</h4>
                        <p><?= APP_EMAIL ?></p>
                        <p style="font-size: 0.78rem; opacity: 0.7;">We reply within 24 hours</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon">&#128205;</div>
                    <div class="contact-info-text">
                        <h4>Address</h4>
                        <p>Addis Ababa, Ethiopia</p>
                        <p style="font-size: 0.78rem; opacity: 0.7;">Delivery across all sub-cities</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon">&#128338;</div>
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
                        <a href="https://t.me/bareedainjera" title="Telegram" target="_blank" rel="noopener">&#9993;</a>
                        <a href="https://www.facebook.com/bareedainjera" title="Facebook" target="_blank" rel="noopener">f</a>
                        <a href="https://www.instagram.com/bareedainjera" title="Instagram" target="_blank" rel="noopener">&#9737;</a>
                        <a href="tel:+251940434748" title="Call Us">&#9742;</a>
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

        <!-- Map Placeholder -->
        <div class="mt-4" style="border-radius: var(--radius); overflow: hidden; height: 300px; background: rgba(198,183,155,0.04); border: 1px solid rgba(198,183,155,0.08); display: flex; align-items: center; justify-content: center;">
            <div class="text-center">
                <div style="font-size: 3rem; opacity: 0.3; margin-bottom: 12px;">&#127758;</div>
                <p class="text-muted">Map &mdash; Addis Ababa, Ethiopia</p>
                <p style="font-size: 0.8rem; color: var(--text-muted); opacity: 0.5;">Google Maps embed can be added here</p>
            </div>
        </div>
    </div>
</section>
