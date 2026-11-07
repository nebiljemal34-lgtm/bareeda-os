<?php
/**
 * Admin User - Detail / Show
 *
 * Variables: $user, $orders, $subscriptions, $stats
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <div class="adm-page-subtitle">
            <a href="/admin/users" style="color:var(--adm-muted);">&larr; Back to Customers</a>
            &nbsp;&middot;&nbsp;
            Member since <?= formatDate($user['created_at']) ?>
        </div>
    </div>
    <span class="adm-badge adm-badge-<?= $user['role'] ?>" style="font-size:13px;padding:5px 14px;">
        <?= ucfirst($user['role']) ?>
    </span>
</div>

<div class="adm-grid-2" style="grid-template-columns: 1fr 2fr;">

    <!-- ── Left Column ──────────────────────────────────── -->
    <div>

        <!-- Customer Info -->
        <div class="adm-card adm-mb-20">
            <div class="adm-card-header">
                <div class="adm-card-title">Customer Info</div>
            </div>
            <ul class="adm-info-list">
                <li>
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= sanitize($user['email']) ?></span>
                </li>
                <li>
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?= !empty($user['phone']) ? sanitize($user['phone']) : '&mdash;' ?></span>
                </li>
                <li style="flex-direction:column;gap:4px;">
                    <span class="info-label">Address</span>
                    <span class="info-value"><?= !empty($user['address']) ? nl2br(sanitize($user['address'])) : '&mdash;' ?></span>
                </li>
                <li>
                    <span class="info-label">City</span>
                    <span class="info-value"><?= !empty($user['city']) ? sanitize($user['city']) : '&mdash;' ?></span>
                </li>
                <li>
                    <span class="info-label">Total Orders</span>
                    <span class="info-value"><?= (int) ($stats['total_orders'] ?? 0) ?></span>
                </li>
                <li>
                    <span class="info-label">Total Spent</span>
                    <span class="info-value" style="color:var(--adm-gold);"><?= formatPrice((float) ($stats['total_spent'] ?? 0)) ?></span>
                </li>
            </ul>
        </div>

        <!-- Role Update -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">Update Role</div>
            </div>
            <form method="POST" action="/admin/users/<?= $user['id'] ?>/role">
                <?= csrf_field() ?>
                <div class="adm-form-group">
                    <select name="role" class="adm-form-select">
                        <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn-gold adm-btn-sm" style="width:100%;"
                    <?= ((int) $user['id'] === currentUserId()) ? 'disabled title="Cannot change your own role"' : '' ?>>
                    Update Role
                </button>
                <?php if ((int) $user['id'] === currentUserId()): ?>
                    <div class="adm-text-sm adm-text-muted" style="margin-top:6px;text-align:center;">You cannot change your own role.</div>
                <?php endif; ?>
            </form>
        </div>

    </div>

    <!-- ── Right Column ─────────────────────────────────── -->
    <div>

        <!-- Subscriptions -->
        <?php if (!empty($subscriptions)): ?>
        <div class="adm-card adm-mb-20">
            <div class="adm-card-header">
                <div class="adm-card-title">Active Subscriptions</div>
                <div class="adm-text-muted adm-text-sm"><?= count($subscriptions) ?></div>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Frequency</th>
                            <th>Next Delivery</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td class="adm-fw-600"><?= sanitize($sub['product_name']) ?></td>
                            <td><?= ucfirst($sub['frequency']) ?></td>
                            <td class="adm-text-muted"><?= formatDate($sub['next_delivery_date']) ?></td>
                            <td><span class="adm-badge adm-badge-<?= $sub['status'] ?>"><?= ucfirst($sub['status']) ?></span></td>
                            <td><a href="/admin/subscriptions/<?= $sub['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order History -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">Order History</div>
                <div class="adm-text-muted adm-text-sm"><?= count($orders) ?> order(s)</div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="adm-empty">
                    <p>No orders yet.</p>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="/admin/orders/<?= $order['id'] ?>" class="adm-text-gold adm-fw-600">
                                        <?= sanitize($order['order_number']) ?>
                                    </a>
                                </td>
                                <td class="adm-text-muted"><?= formatDate($order['created_at'], 'M j, Y') ?></td>
                                <td class="adm-fw-600"><?= formatPrice((float) $order['total']) ?></td>
                                <td><span class="adm-badge adm-badge-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                                <td><span class="adm-badge adm-badge-<?= $order['status'] ?>"><?= str_replace('_', ' ', $order['status']) ?></span></td>
                                <td><a href="/admin/orders/<?= $order['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>
