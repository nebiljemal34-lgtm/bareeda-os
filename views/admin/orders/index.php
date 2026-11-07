<?php
/**
 * View: Admin Orders List
 * Variables: $orders, $current_page, $total_pages, $filter_status
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Manage Orders</h1>
    <p class="amharic-subtitle"><?= count($orders) ?> orders</p>
</section>

<div class="container" style="padding: 32px 24px;">
    <div class="flex-between mb-3">
        <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem;">&larr; Dashboard</a>
        <div style="display: flex; gap: 8px;">
            <?php
            $statuses = ['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
            foreach ($statuses as $val => $label):
            ?>
                <a href="/admin/orders<?= $val ? '?status=' . $val : '' ?>"
                   class="btn <?= ($filter_status ?? '') === $val ? 'btn-primary' : 'btn-secondary' ?>"
                   style="padding: 6px 12px; font-size: 0.75rem;"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128230;</div>
            <h2>No Orders Found</h2>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong style="color: var(--gold);"><?= htmlspecialchars($order['order_number']) ?></strong></td>
                        <td>
                            <?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($order['email'] ?? '') ?></div>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= formatDate($order['created_at']) ?></td>
                        <td><span class="badge status-<?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span></td>
                        <td><span class="badge" style="background: rgba(198,183,155,0.08); color: var(--text-muted); font-size: 0.7rem;"><?= ucfirst($order['payment_status']) ?></span></td>
                        <td style="font-weight: 700;"><?= formatPrice((float) $order['total']) ?></td>
                        <td><a href="/admin/orders/<?= (int) $order['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="/admin/orders?page=<?= $current_page - 1 ?><?= $filter_status ? '&status=' . $filter_status : '' ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                <?php if ($i === $current_page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="/admin/orders?page=<?= $i ?><?= $filter_status ? '&status=' . $filter_status : '' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="/admin/orders?page=<?= $current_page + 1 ?><?= $filter_status ? '&status=' . $filter_status : '' ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
