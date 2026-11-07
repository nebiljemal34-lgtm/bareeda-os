<?php
/**
 * View: Admin Products List
 * Variables: $products, $current_page, $total_pages
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Manage Products</h1>
    <p class="amharic-subtitle"><?= count($products) ?> products</p>
</section>

<div class="container" style="padding: 32px 24px;">
    <div class="flex-between mb-3">
        <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem;">&larr; Back to Dashboard</a>
        <a href="/admin/products/create" class="btn btn-primary">+ Add Product</a>
    </div>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128230;</div>
            <h2>No Products</h2>
            <p>Add your first product to get started.</p>
            <a href="/admin/products/create" class="btn btn-primary">Add Product</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= (int) $p['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($p['name']) ?></strong>
                            <?php if (!empty($p['name_am'])): ?>
                                <div style="font-size: 0.78rem; color: var(--gold); opacity: 0.5;"><?= htmlspecialchars($p['name_am']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 700;"><?= formatPrice((float) $p['price']) ?></td>
                        <td>
                            <?php if ((int) $p['stock_quantity'] <= 0): ?>
                                <span class="badge badge-danger">Out</span>
                            <?php elseif ((int) $p['stock_quantity'] <= 10): ?>
                                <span class="badge badge-warning"><?= (int) $p['stock_quantity'] ?></span>
                            <?php else: ?>
                                <span style="color: var(--success);"><?= (int) $p['stock_quantity'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $p['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="/admin/products/<?= (int) $p['id'] ?>/edit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;">Edit</a>
                                <form method="POST" action="/admin/products/<?= (int) $p['id'] ?>/delete" class="inline-form"
                                      onsubmit="return confirm('Delete this product?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.75rem;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="/admin/products?page=<?= $current_page - 1 ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                <?php if ($i === $current_page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="/admin/products?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="/admin/products?page=<?= $current_page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
