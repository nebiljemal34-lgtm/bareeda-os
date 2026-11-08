<?php
/**
 * View: Admin Subscriptions List
 * Variables: $subscriptions, $current_page, $total_pages
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Manage Subscriptions</h1>
    <p class="amharic-subtitle"><?= count($subscriptions) ?> subscriptions</p>
</section>

<div class="container" style="padding: 32px 24px;">
    <div class="flex-between mb-3">
        <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem;">&larr; Dashboard</a>
        <form method="POST" action="/admin/subscriptions/process" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Process Due Subscriptions</button>
        </form>
    </div>

    <?php if (empty($subscriptions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon" style="color: var(--gold);"><?= icon('calendar', 56) ?></div>
            <h2>No Subscriptions</h2>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Frequency</th>
                        <th>Next Delivery</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= (int) $sub['id'] ?></td>
                        <td>
                            <?= htmlspecialchars(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? '')) ?>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($sub['email'] ?? '') ?></div>
                        </td>
                        <td><?= htmlspecialchars($sub['product_name'] ?? '') ?></td>
                        <td><?= ucfirst($sub['frequency'] ?? '') ?></td>
                        <td><?= formatDate($sub['next_delivery_date'] ?? '') ?></td>
                        <td><span class="badge status-<?= $sub['status'] ?? 'active' ?>"><?= ucfirst($sub['status'] ?? '') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="/admin/subscriptions?page=<?= $current_page - 1 ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                <?php if ($i === $current_page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="/admin/subscriptions?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="/admin/subscriptions?page=<?= $current_page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
