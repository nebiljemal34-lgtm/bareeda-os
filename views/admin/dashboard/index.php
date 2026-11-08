<?php
/**
 * Admin Dashboard - Overview
 *
 * Variables: $stats, $totalOrders, $activeSubscriptions, $recentOrders, $lowStockProducts
 */
?>

<!-- ── Stats Cards ──────────────────────────────────────── -->
<div class="adm-stats-grid">
    <div class="adm-stat-card">
        <div class="adm-stat-label">Total Orders</div>
        <div class="adm-stat-value"><?= number_format($totalOrders) ?></div>
        <div class="adm-stat-trend up">
            <?= $stats['today_orders'] ?> today
        </div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-label">Total Revenue</div>
        <div class="adm-stat-value"><?= number_format($stats['total_revenue'], 0) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">ETB &nbsp;&middot;&nbsp; Today: <?= number_format($stats['today_revenue'], 0) ?> ETB</div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-label">Pending Orders</div>
        <div class="adm-stat-value" style="color:<?= $stats['pending_orders'] > 0 ? 'var(--adm-warning)' : 'var(--adm-text)' ?>"><?= $stats['pending_orders'] ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Awaiting processing</div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-label">Active Subscriptions</div>
        <div class="adm-stat-value"><?= number_format($activeSubscriptions) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Recurring deliveries</div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-label">Visitors · 30 Days</div>
        <div class="adm-stat-value"><?= number_format($analytics['visitors']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);"><a href="/admin/analytics"><?= number_format($analytics['page_views']) ?> page views</a></div>
    </div>

    <div class="adm-stat-card">
        <div class="adm-stat-label">QR Page Opens · 30 Days</div>
        <div class="adm-stat-value"><?= number_format($analytics['qr_scans']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);"><a href="/admin/analytics"><?= number_format($analytics['qr_visitors']) ?> unique QR visitors</a></div>
    </div>
</div>

<!-- ── Quick Actions ────────────────────────────────────── -->
<div class="adm-d-flex adm-gap-12 adm-mb-24">
    <a href="/admin/products/create" class="adm-btn adm-btn-gold">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
    </a>
    <a href="/admin/orders" class="adm-btn adm-btn-outline">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        View All Orders
    </a>
    <a href="/admin/reports/sales" class="adm-btn adm-btn-outline">
        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Sales Report
    </a>
</div>

<!-- ── Recent Orders & Low Stock ────────────────────────── -->
<div class="adm-grid-2">

    <!-- Recent Orders -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Recent Orders</div>
            <a href="/admin/orders" class="adm-btn adm-btn-sm adm-btn-outline">View All</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="adm-empty">
                <p>No orders yet.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>
                                <a href="/admin/orders/<?= $order['id'] ?>" class="adm-text-gold adm-fw-600">
                                    <?= sanitize($order['order_number']) ?>
                                </a>
                            </td>
                            <td><?= sanitize($order['first_name'] . ' ' . $order['last_name']) ?></td>
                            <td class="adm-text-muted"><?= formatDate($order['created_at'], 'M j, g:ia') ?></td>
                            <td class="adm-fw-600"><?= formatPrice((float) $order['total']) ?></td>
                            <td><span class="adm-badge adm-badge-<?= $order['status'] ?>"><?= str_replace('_', ' ', $order['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Low Stock Alert -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Low Stock Alert</div>
            <a href="/admin/products" class="adm-btn adm-btn-sm adm-btn-outline">All Products</a>
        </div>

        <?php if (empty($lowStockProducts)): ?>
            <div class="adm-empty">
                <p>All products are well stocked.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockProducts as $product): ?>
                        <tr>
                            <td>
                                <a href="/admin/products/<?= $product['id'] ?>/edit" class="adm-text-gold adm-fw-600">
                                    <?= sanitize($product['name']) ?>
                                </a>
                                <?php if (!empty($product['name_am'])): ?>
                                    <div class="adm-text-muted adm-text-sm"><?= sanitize($product['name_am']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= formatPrice((float) $product['price']) ?></td>
                            <td>
                                <span class="adm-badge adm-badge-low"><?= (int) $product['stock_quantity'] ?> left</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
