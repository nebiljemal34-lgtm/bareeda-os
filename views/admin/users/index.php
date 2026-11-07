<?php
/**
 * View: Admin Users List
 * Variables: $users, $current_page, $total_pages
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Manage Users</h1>
    <p class="amharic-subtitle"><?= count($users) ?> users</p>
</section>

<div class="container" style="padding: 32px 24px;">
    <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Dashboard</a>

    <?php if (empty($users)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128101;</div>
            <h2>No Users</h2>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= (int) $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></strong></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($u['phone'] ?? '') ?></td>
                        <td>
                            <?php if (($u['role'] ?? '') === 'admin'): ?>
                                <span class="badge" style="background: rgba(198,183,155,0.15); color: var(--gold);">Admin</span>
                            <?php else: ?>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Customer</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= formatDate($u['created_at'] ?? '') ?></td>
                        <td><a href="/admin/users/<?= (int) $u['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="/admin/users?page=<?= $current_page - 1 ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                <?php if ($i === $current_page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="/admin/users?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="/admin/users?page=<?= $current_page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
