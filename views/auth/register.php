<?php
/**
 * View: Register Page
 */
?>

<section class="section" style="min-height: 70vh; display: flex; align-items: center;">
    <div class="container">
        <div class="form-card">
            <h2>Create Account &middot; መለያ ይፍጠሩ</h2>
            <p class="text-center text-muted mb-3" style="font-size: 0.9rem;">Join Bareeda for fresh injera delivery</p>

            <form method="POST" action="/register">
                <?= csrf_field() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required
                               value="<?= htmlspecialchars(old('first_name')) ?>"
                               placeholder="First name" autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required
                               value="<?= htmlspecialchars(old('last_name')) ?>"
                               placeholder="Last name">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-input" required
                           value="<?= htmlspecialchars(old('email')) ?>"
                           placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input"
                           value="<?= htmlspecialchars(old('phone')) ?>"
                           placeholder="+251...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-input" required
                           placeholder="At least <?= PASSWORD_MIN_LENGTH ?> characters"
                           minlength="<?= PASSWORD_MIN_LENGTH ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" required
                           placeholder="Repeat your password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                    Create Account
                </button>
            </form>

            <div class="form-footer">
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Already have an account? <a href="/login">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</section>
