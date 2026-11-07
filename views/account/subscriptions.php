<?php
/**
 * View: Account Subscriptions
 * Variables: $subscriptions, $products
 */

$frequencyLabels = [
    'weekly'    => 'Weekly',
    'biweekly'  => 'Every 2 Weeks',
    'monthly'   => 'Monthly',
];
?>

<section class="page-header">
    <h1>My Subscriptions &middot; ምዝገባዎቼ</h1>
    <p class="amharic-subtitle">Manage your recurring injera deliveries</p>
</section>

<div class="account-layout">
    <!-- Sidebar -->
    <aside class="account-sidebar">
        <h3>Menu</h3>
        <ul>
            <li><a href="/account">Dashboard</a></li>
            <li><a href="/orders">My Orders</a></li>
            <li><a href="/subscriptions" class="active">Subscriptions</a></li>
            <li><a href="/account#profile">Edit Profile</a></li>
            <li><a href="/logout">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div>
        <!-- Create New Subscription -->
        <div style="background: rgba(198,183,155,0.04); border: 1px solid rgba(198,183,155,0.1); border-radius: var(--radius); padding: 24px; margin-bottom: 32px;">
            <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Create New Subscription</h3>
            <form method="POST" action="/subscriptions/create" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                <?= csrf_field() ?>

                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label class="form-label" for="sub_product">Product</label>
                    <select id="sub_product" name="product_id" class="form-input" required>
                        <option value="">Select a product...</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> - <?= formatPrice((float) $p['price']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex: 1; min-width: 160px; margin-bottom: 0;">
                    <label class="form-label" for="sub_freq">Frequency</label>
                    <select id="sub_freq" name="frequency" class="form-input" required>
                        <option value="weekly">Weekly</option>
                        <option value="biweekly">Every 2 Weeks</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Create Subscription</button>
            </form>
        </div>

        <!-- Active Subscriptions -->
        <?php if (empty($subscriptions)): ?>
            <div class="empty-state" style="padding: 40px;">
                <div class="empty-state-icon">&#128197;</div>
                <h2>No Subscriptions</h2>
                <p>Create a subscription above to get fresh injera delivered automatically.</p>
            </div>
        <?php else: ?>
            <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Your Subscriptions</h3>

            <?php foreach ($subscriptions as $sub): ?>
            <div class="card" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <?php if (!empty($sub['product_image'])): ?>
                        <img src="<?= htmlspecialchars($sub['product_image']) ?>" alt="" style="width: 56px; height: 56px; border-radius: 8px; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 56px; height: 56px; border-radius: 8px; background: rgba(198,183,155,0.05); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--gold); opacity: 0.3;">&#127838;</div>
                    <?php endif; ?>

                    <div>
                        <div style="font-weight: 700; color: var(--text);"><?= htmlspecialchars($sub['product_name'] ?? 'Product') ?></div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">
                            <?= $frequencyLabels[$sub['frequency']] ?? ucfirst($sub['frequency']) ?>
                            &bull; Next delivery: <?= formatDate($sub['next_delivery_date']) ?>
                        </div>
                        <div style="margin-top: 4px;">
                            <span class="badge status-<?= $sub['status'] ?>"><?= ucfirst($sub['status']) ?></span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <?php if ($sub['status'] === 'active'): ?>
                        <form method="POST" action="/subscriptions/<?= (int) $sub['id'] ?>/pause" class="inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.78rem;">Pause</button>
                        </form>
                        <form method="POST" action="/subscriptions/<?= (int) $sub['id'] ?>/cancel" class="inline-form"
                              onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.78rem;">Cancel</button>
                        </form>
                    <?php elseif ($sub['status'] === 'paused'): ?>
                        <form method="POST" action="/subscriptions/<?= (int) $sub['id'] ?>/resume" class="inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.78rem;">Resume</button>
                        </form>
                        <form method="POST" action="/subscriptions/<?= (int) $sub['id'] ?>/cancel" class="inline-form"
                              onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.78rem;">Cancel</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
