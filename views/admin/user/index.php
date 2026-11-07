<?php
/**
 * Admin Users - List
 *
 * Variables: $users, $page, $totalPages, $total
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Customers</div>
        <div class="adm-page-subtitle"><?= $total ?> user<?= $total !== 1 ? 's' : '' ?> registered</div>
    </div>
</div>

<!-- ── Users Table ──────────────────────────────────────── -->
<?php if (empty($users)): ?>
    <div class="adm-card">
        <div class="adm-empty">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <p>No customers registered yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="adm-card" style="padding:0;">
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                        <th style="width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="adm-fw-600"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td>
                            <a href="mailto:<?= sanitize($user['email']) ?>" class="adm-text-muted"><?= sanitize($user['email']) ?></a>
                        </td>
                        <td class="adm-text-muted"><?= !empty($user['phone']) ? sanitize($user['phone']) : '&mdash;' ?></td>
                        <td>
                            <span class="adm-badge adm-badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                        </td>
                        <td class="adm-fw-600"><?= (int) ($user['order_count'] ?? 0) ?></td>
                        <td class="adm-fw-600"><?= formatPrice((float) ($user['total_spent'] ?? 0)) ?></td>
                        <td class="adm-text-muted adm-text-sm"><?= formatDate($user['created_at'], 'M j, Y') ?></td>
                        <td>
                            <a href="/admin/users/<?= $user['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline">View</a>
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
            <a href="/admin/users?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php else: ?>
            <span class="disabled">&laquo; Prev</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="/admin/users?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="/admin/users?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
