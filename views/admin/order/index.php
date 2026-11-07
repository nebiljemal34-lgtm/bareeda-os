<?php
/**
 * Admin Orders - List
 *
 * Variables: $orders, $page, $totalPages, $total, $status, $dateFrom, $dateTo
 */

$statuses = ['' => 'All Statuses', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Orders</div>
        <div class="adm-page-subtitle"><?= $total ?> order<?= $total !== 1 ? 's' : '' ?> found</div>
    </div>
</div>

<!-- ── Filters ──────────────────────────────────────────── -->
<div class="adm-card adm-mb-20" style="padding:14px 18px;">
    <form method="GET" action="/admin/orders" class="adm-filter-bar" style="margin:0;">
        <div>
            <label class="adm-form-label" style="margin-bottom:3px;font-size:11px;">Status</label>
            <select name="status" class="adm-form-select" style="min-width:150px;">
                <?php foreach ($statuses as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="adm-form-label" style="margin-bottom:3px;font-size:11px;">From Date</label>
            <input type="date" name="date_from" class="adm-form-input" value="<?= sanitize($dateFrom) ?>" style="width:160px;">
        </div>
        <div>
            <label class="adm-form-label" style="margin-bottom:3px;font-size:11px;">To Date</label>
            <input type="date" name="date_to" class="adm-form-input" value="<?= sanitize($dateTo) ?>" style="width:160px;">
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px;">
            <button type="submit" class="adm-btn adm-btn-outline adm-btn-sm">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
            <?php if ($status || $dateFrom || $dateTo): ?>
                <a href="/admin/orders" class="adm-btn adm-btn-sm" style="color:var(--adm-muted);border-color:var(--adm-border);">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── Orders Table ─────────────────────────────────────── -->
<?php if (empty($orders)): ?>
    <div class="adm-card">
        <div class="adm-empty">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No orders found.</p>
        </div>
    </div>
<?php else: ?>
    <div class="adm-card" style="padding:0;">
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="width:80px;">Action</th>
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
                        <td>
                            <div class="adm-fw-600"><?= sanitize($order['first_name'] . ' ' . $order['last_name']) ?></div>
                            <div class="adm-text-muted adm-text-sm"><?= sanitize($order['email']) ?></div>
                        </td>
                        <td class="adm-text-muted"><?= formatDate($order['created_at'], 'M j, Y') ?><br><span class="adm-text-sm"><?= formatDate($order['created_at'], 'g:i A') ?></span></td>
                        <td class="adm-fw-600"><?= formatPrice((float) $order['total']) ?></td>
                        <td>
                            <span class="adm-badge adm-badge-<?= $order['payment_status'] ?>"><?= $order['payment_status'] ?></span>
                        </td>
                        <td>
                            <span class="adm-badge adm-badge-<?= $order['status'] ?>"><?= str_replace('_', ' ', $order['status']) ?></span>
                        </td>
                        <td>
                            <a href="/admin/orders/<?= $order['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="adm-pagination">
        <?php
            $qs = [];
            if ($status) $qs['status'] = $status;
            if ($dateFrom) $qs['date_from'] = $dateFrom;
            if ($dateTo) $qs['date_to'] = $dateTo;
            $baseQs = $qs;
        ?>

        <?php if ($page > 1): ?>
            <?php $qs = array_merge($baseQs, ['page' => $page - 1]); ?>
            <a href="/admin/orders?<?= http_build_query($qs) ?>">&laquo; Prev</a>
        <?php else: ?>
            <span class="disabled">&laquo; Prev</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php $qs = array_merge($baseQs, ['page' => $i]); ?>
            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="/admin/orders?<?= http_build_query($qs) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <?php $qs = array_merge($baseQs, ['page' => $page + 1]); ?>
            <a href="/admin/orders?<?= http_build_query($qs) ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
