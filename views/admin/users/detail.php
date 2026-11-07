<?php
/**
 * View: Admin User Detail
 * Variables: $user, $orders, $subs
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>User: <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h1>
</section>

<div class="container" style="padding: 32px 24px; max-width: 900px;">
    <a href="/admin/users" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Back to Users</a>

    <!-- User Info -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <div class="grid grid-2" style="gap: 16px;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Email</div>
                <a href="mailto:<?= htmlspecialchars($user['email'] ?? '') ?>" style="color: var(--gold); text-decoration: none;"><?= htmlspecialchars($user['email'] ?? '') ?></a>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Phone</div>
                <div><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Role</div>
                <span class="badge" style="background: rgba(198,183,155,0.1); color: var(--gold);"><?= ucfirst($user['role'] ?? 'customer') ?></span>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Joined</div>
                <div><?= formatDateTime($user['created_at'] ?? '') ?></div>
            </div>
            <?php if (!empty($user['address'])): ?>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Address</div>
                <div><?= nl2br(htmlspecialchars($user['address'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($user['city'])): ?>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">City</div>
                <div><?= htmlspecialchars($user['city']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Orders -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Orders (<?= count($orders) ?>)</h3>
        <?php if (empty($orders)): ?>
            <p class="text-muted">No orders.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Order #</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="color: var(--gold); font-weight: 600;"><?= htmlspecialchars($o['order_number']) ?></td>
                        <td style="color: var(--text-muted);"><?= formatDate($o['created_at']) ?></td>
                        <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst(str_replace('_', ' ', $o['status'])) ?></span></td>
                        <td style="font-weight: 700;"><?= formatPrice((float) $o['total']) ?></td>
                        <td><a href="/admin/orders/<?= (int) $o['id'] ?>" style="color: var(--gold); font-size: 0.85rem;">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Subscriptions -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Subscriptions (<?= count($subs) ?>)</h3>
        <?php if (empty($subs)): ?>
            <p class="text-muted">No subscriptions.</p>
        <?php else: ?>
            <?php foreach ($subs as $s): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(198,183,155,0.05);">
                <div>
                    <strong><?= htmlspecialchars($s['product_name'] ?? '') ?></strong>
                    <span style="color: var(--text-muted); font-size: 0.82rem;"> &mdash; <?= ucfirst($s['frequency'] ?? '') ?></span>
                </div>
                <span class="badge status-<?= $s['status'] ?? 'active' ?>"><?= ucfirst($s['status'] ?? '') ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
