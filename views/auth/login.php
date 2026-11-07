<?php
/**
 * View: Login Page
 */
?>

<section class="section" style="min-height: 70vh; display: flex; align-items: center;">
    <div class="container">
        <div class="form-card">
            <h2>Welcome Back &middot; እንኳን ደህና መጡ</h2>
            <p class="text-center text-muted mb-3" style="font-size: 0.9rem;">Sign in to your Bareeda account</p>

            <form method="POST" action="/login">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required
                           value="<?= htmlspecialchars(old('email')) ?>"
                           placeholder="you@example.com" autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required
                           placeholder="Your password">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div class="form-check" style="margin-bottom: 0;">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="/forgot-password" style="color: var(--gold); font-size: 0.85rem; text-decoration: none;">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                    Sign In
                </button>
            </form>

            <div class="form-footer">
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Don't have an account? <a href="/register">Create one</a>
                </p>
            </div>
        </div>
    </div>
</section>
