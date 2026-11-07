<?php
/**
 * View: Admin Dashboard
 * Variables: $stats, $recent_orders, $low_stock, $sub_count
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Admin Dashboard</h1>
    <p class="amharic-subtitle">Overview of your business</p>
</section>

<div class="container" style="padding: 32px 24px;">
    <!-- Stats Grid -->
    <div class="grid grid-4 mb-4">
        <div class="stat-card">
            <div class="stat-value"><?= (int) $stats['today_orders'] ?></div>
            <div class="stat-label">Today's Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= formatPrice($stats['today_revenue']) ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int) $stats['pending_orders'] ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int) $sub_count ?></div>
            <div class="stat-label">Active Subscriptions</div>
        </div>
    </div>

    <div class="grid grid-2" style="gap: 24px;">
        <!-- Recent Orders -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px;">
            <div class="flex-between mb-3">
                <h3 style="color: var(--gold); font-size: 1rem;">Recent Orders</h3>
                <a href="/admin/orders" style="color: var(--gold); font-size: 0.82rem; text-decoration: none;">View All &rarr;</a>
            </div>
            <?php if (empty($recent_orders)): ?>
                <p class="text-muted text-center" style="padding: 24px;">No orders yet.</p>
            <?php else: ?>
                <?php foreach (array_slice($recent_orders, 0, 5) as $order): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(198,183,155,0.05);">
                    <div>
                        <strong style="color: var(--gold); font-size: 0.85rem;"><?= htmlspecialchars($order['order_number']) ?></strong>
                        <div style="font-size: 0.78rem; color: var(--text-muted);"><?= formatDate($order['created_at']) ?></div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge status-<?= $order['status'] ?>" style="font-size: 0.7rem;"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
                        <div style="font-size: 0.82rem; font-weight: 700; margin-top: 4px;"><?= formatPrice((float) $order['total']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Low Stock -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px;">
            <div class="flex-between mb-3">
                <h3 style="color: var(--gold); font-size: 1rem;">Low Stock Alert</h3>
                <a href="/admin/products" style="color: var(--gold); font-size: 0.82rem; text-decoration: none;">View All &rarr;</a>
            </div>
            <?php if (empty($low_stock)): ?>
                <p class="text-muted text-center" style="padding: 24px;">All products are well stocked.</p>
            <?php else: ?>
                <?php foreach ($low_stock as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(198,183,155,0.05);">
                    <div>
                        <strong style="font-size: 0.88rem;"><?= htmlspecialchars($item['name']) ?></strong>
                        <?php if (!empty($item['name_am'])): ?>
                            <span style="font-size: 0.78rem; color: var(--gold); opacity: 0.5; margin-left: 8px;"><?= htmlspecialchars($item['name_am']) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="badge" style="background: rgba(244,67,54,0.15); color: var(--danger);">
                        <?= (int) $item['stock_quantity'] ?> left
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Links -->
    <div style="margin-top: 32px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Quick Actions</h3>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/admin/products/create" class="btn btn-primary">+ Add Product</a>
            <a href="/admin/orders" class="btn btn-secondary">View Orders</a>
            <a href="/admin/users" class="btn btn-secondary">Manage Users</a>
            <a href="/admin/categories" class="btn btn-secondary">Categories</a>
            <a href="/admin/coupons" class="btn btn-secondary">Coupons</a>
            <a href="/admin/delivery-zones" class="btn btn-secondary">Delivery Zones</a>
            <a href="/admin/subscriptions" class="btn btn-secondary">Subscriptions</a>
        </div>
    </div>
</div>
