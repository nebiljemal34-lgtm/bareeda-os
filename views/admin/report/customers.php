<?php
/**
 * Admin Reports - Customers
 *
 * Variables: $totalCustomers, $newThisMonth, $returningRate, $topCustomers
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Customer Report</div>
        <div class="adm-page-subtitle">Customer analytics and insights</div>
    </div>
    <div class="adm-d-flex adm-gap-8">
        <a href="/admin/reports/sales" class="adm-btn adm-btn-sm adm-btn-outline">Sales Report</a>
        <a href="/admin/reports/products" class="adm-btn adm-btn-sm adm-btn-outline">Product Report</a>
    </div>
</div>

<!-- ── Summary Stats ────────────────────────────────────── -->
<div class="adm-stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
    <div class="adm-stat-card">
        <div class="adm-stat-label">Total Customers</div>
        <div class="adm-stat-value"><?= number_format($totalCustomers) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Registered users</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">New This Month</div>
        <div class="adm-stat-value"><?= number_format($newThisMonth) ?></div>
        <div class="adm-stat-trend up"><?= date('F Y') ?></div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Returning Rate</div>
        <div class="adm-stat-value"><?= $returningRate ?>%</div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Customers with 2+ orders</div>
    </div>
</div>

<!-- ── Top Customers ────────────────────────────────────── -->
<div class="adm-card">
    <div class="adm-card-header">
        <div class="adm-card-title">Top Customers</div>
        <div class="adm-text-muted adm-text-sm">By total spending</div>
    </div>

    <?php if (empty($topCustomers)): ?>
        <div class="adm-empty">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <p>No customer order data available yet.</p>
        </div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th style="text-align:center;">Orders</th>
                        <th style="text-align:right;">Total Spent</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCustomers as $i => $cust): ?>
                    <tr>
                        <td class="adm-text-muted"><?= $i + 1 ?></td>
                        <td class="adm-fw-600"><?= sanitize($cust['first_name'] . ' ' . $cust['last_name']) ?></td>
                        <td class="adm-text-muted"><?= sanitize($cust['email']) ?></td>
                        <td class="adm-text-muted"><?= !empty($cust['phone']) ? sanitize($cust['phone']) : '&mdash;' ?></td>
                        <td style="text-align:center;" class="adm-fw-600"><?= number_format((int) $cust['total_orders']) ?></td>
                        <td style="text-align:right;" class="adm-fw-600"><?= formatPrice((float) $cust['total_spent']) ?></td>
                        <td>
                            <a href="/admin/users/<?= $cust['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
