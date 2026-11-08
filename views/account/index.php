<?php
/**
 * View: Account Dashboard
 * Variables: $user, $recent_orders, $total_orders, $active_subscriptions
 */
?>

<section class="page-header">
    <h1>My Account &middot; የእኔ ሂሳብ</h1>
    <p class="amharic-subtitle">Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'Customer') ?>!</p>
</section>

<div class="account-layout">
    <!-- Sidebar -->
    <aside class="account-sidebar">
        <h3>Menu</h3>
        <ul>
            <li><a href="/account" class="active">Dashboard</a></li>
            <li><a href="/orders">My Orders</a></li>
            <li><a href="/subscriptions">Subscriptions</a></li>
            <li><a href="/account#profile">Edit Profile</a></li>
            <li><a href="/logout">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div>
        <!-- Stats -->
        <div class="grid grid-3 mb-4">
            <div class="stat-card">
                <div class="stat-value"><?= (int) $total_orders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($active_subscriptions) ?></div>
                <div class="stat-label">Active Subscriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="font-size: 1.4rem;"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                <div class="stat-label"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
            <div class="flex-between mb-3">
                <h3 style="color: var(--gold); font-size: 1rem;">Recent Orders</h3>
                <?php if ($total_orders > 0): ?>
                    <a href="/orders" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.78rem;">View All</a>
                <?php endif; ?>
            </div>

            <?php if (empty($recent_orders)): ?>
                <p class="text-muted" style="text-align: center; padding: 24px;">No orders yet. <a href="/products" style="color: var(--gold);">Start shopping!</a></p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
        <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong style="color: var(--gold);"><?= htmlspecialchars($order['order_number']) ?></strong></td>
                        <td style="color: var(--text-muted);"><?= formatDate($order['created_at']) ?></td>
                        <td>
                            <span class="badge status-<?= $order['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                            </span>
                        </td>
                        <td style="font-weight: 700;"><?= formatPrice((float) $order['total']) ?></td>
                        <td><a href="/orders/<?= (int) $order['id'] ?>" style="color: var(--gold); font-size: 0.85rem;">View</a></td>
                    </tr>
        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-2" style="gap: 16px;">
            <a href="/subscriptions" class="card" style="text-decoration: none; display: flex; align-items: center; gap: 16px;">
                <span style="color: var(--gold);"><?= icon('calendar', 34) ?></span>
                <div>
                    <h4 style="color: var(--text); font-size: 0.95rem; margin-bottom: 4px;">Manage Subscriptions</h4>
                    <p style="color: var(--text-muted); font-size: 0.82rem;">Set up recurring injera deliveries</p>
                </div>
            </a>
            <a href="/products" class="card" style="text-decoration: none; display: flex; align-items: center; gap: 16px;">
                <span style="color: var(--gold);"><?= icon('cart', 34) ?></span>
                <div>
                    <h4 style="color: var(--text); font-size: 0.95rem; margin-bottom: 4px;">Shop Products</h4>
                    <p style="color: var(--text-muted); font-size: 0.82rem;">Browse our fresh teff injera</p>
                </div>
            </a>
        </div>

        <!-- Profile Section -->
        <div id="profile" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-top: 24px;">
            <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 24px;">Edit Profile &middot; መገለጫ ያርትዑ</h3>

            <form method="POST" action="/account/update">
                <?= csrf_field() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required
                               value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required
                               value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" class="form-input" disabled
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                           style="opacity: 0.5; cursor: not-allowed;">
                    <small style="color: var(--text-muted); font-size: 0.78rem;">Email cannot be changed</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-input"
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                               placeholder="+251...">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="city">City</label>
                        <input type="text" id="city" name="city" class="form-input"
                               value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                               placeholder="Dire Dawa">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" name="address" class="form-input" rows="2"
                              placeholder="Your delivery address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

            <!-- Change Password -->
            <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid rgba(198,183,155,0.08);">
                <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 24px;">Change Password</h3>

                <form method="POST" action="/account/change-password">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" required
                                   minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
