<?php
/**
 * Admin Subscriptions - List
 *
 * Variables: $subscriptions, $page, $totalPages, $total
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Subscriptions</div>
        <div class="adm-page-subtitle"><?= $total ?> subscription<?= $total !== 1 ? 's' : '' ?> total</div>
    </div>
</div>

<!-- ── Subscriptions Table ──────────────────────────────── -->
<?php if (empty($subscriptions)): ?>
    <div class="adm-card">
        <div class="adm-empty">
            <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
            <p>No subscriptions yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="adm-card" style="padding:0;">
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Frequency</th>
                        <th>Next Delivery</th>
                        <th>Status</th>
                        <th>Since</th>
                        <th style="width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                    <tr>
                        <td class="adm-fw-600">#<?= (int) $sub['id'] ?></td>
                        <td>
                            <div class="adm-fw-600"><?= sanitize($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                            <div class="adm-text-muted adm-text-sm"><?= sanitize($sub['email']) ?></div>
                        </td>
                        <td>
                            <?= sanitize($sub['product_name']) ?>
                            <div class="adm-text-muted adm-text-sm"><?= formatPrice((float) $sub['product_price']) ?></div>
                        </td>
                        <td>
                            <span class="adm-badge adm-badge-confirmed"><?= ucfirst($sub['frequency']) ?></span>
                        </td>
                        <td class="adm-text-muted"><?= formatDate($sub['next_delivery_date'], 'M j, Y') ?></td>
                        <td>
                            <span class="adm-badge adm-badge-<?= $sub['status'] ?>"><?= ucfirst($sub['status']) ?></span>
                        </td>
                        <td class="adm-text-muted adm-text-sm"><?= formatDate($sub['created_at'], 'M j, Y') ?></td>
                        <td>
                            <a href="/admin/subscriptions/<?= $sub['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline">View</a>
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
        <?php if ($page > 1): ?>
            <a href="/admin/subscriptions?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php else: ?>
            <span class="disabled">&laquo; Prev</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="/admin/subscriptions?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="/admin/subscriptions?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
