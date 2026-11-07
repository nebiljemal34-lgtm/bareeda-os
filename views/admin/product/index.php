<?php
/**
 * Admin Products - List
 *
 * Variables: $products, $page, $totalPages, $total, $search
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Products</div>
        <div class="adm-page-subtitle"><?= $total ?> product<?= $total !== 1 ? 's' : '' ?> total</div>
    </div>
    <a href="/admin/products/create" class="adm-btn adm-btn-gold">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
    </a>
</div>

<!-- ── Search / Filter Bar ──────────────────────────────── -->
<div class="adm-filter-bar">
    <form method="GET" action="/admin/products" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text"
               name="search"
               class="adm-form-input"
               placeholder="Search products..."
               value="<?= sanitize($search) ?>"
               style="min-width:240px;">
        <button type="submit" class="adm-btn adm-btn-outline adm-btn-sm">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Search
        </button>
        <?php if ($search): ?>
            <a href="/admin/products" class="adm-btn adm-btn-sm adm-btn-outline" style="color:var(--adm-muted);border-color:var(--adm-border);">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- ── Products Table ───────────────────────────────────── -->
<?php if (empty($products)): ?>
    <div class="adm-card">
        <div class="adm-empty">
            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            <p><?= $search ? 'No products found matching "' . sanitize($search) . '".' : 'No products yet. Add your first product!' ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="adm-card" style="padding:0;">
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th style="width:50px;">Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= sanitize($product['image_url']) ?>" alt="" class="adm-thumb" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="adm-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--adm-muted);font-size:14px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="adm-fw-600"><?= sanitize($product['name']) ?></div>
                            <?php if (!empty($product['name_am'])): ?>
                                <div class="adm-text-muted adm-text-sm"><?= sanitize($product['name_am']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="adm-fw-600"><?= formatPrice((float) $product['price']) ?></td>
                        <td>
                            <?php
                                $stock = (int) $product['stock_quantity'];
                                $stockClass = $stock <= 0 ? 'adm-badge-low' : ($stock <= 10 ? 'adm-badge-pending' : 'adm-badge-delivered');
                            ?>
                            <span class="adm-badge <?= $stockClass ?>"><?= $stock ?></span>
                        </td>
                        <td>
                            <?php if ((int) $product['is_active'] === 1): ?>
                                <span class="adm-badge adm-badge-active">Active</span>
                            <?php else: ?>
                                <span class="adm-badge adm-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="adm-text-muted adm-text-sm"><?= formatDate($product['created_at'], 'M j, Y') ?></td>
                        <td>
                            <div class="adm-d-flex adm-gap-8">
                                <a href="/admin/products/<?= $product['id'] ?>/edit" class="adm-btn adm-btn-sm adm-btn-outline" title="Edit">
                                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                                <form method="POST" action="/admin/products/<?= $product['id'] ?>/delete" style="display:inline;" onsubmit="return confirm('Deactivate this product?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger" title="Delete">
                                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
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
            if ($search) $qs['search'] = $search;
            $baseQs = $qs;
        ?>

        <?php if ($page > 1): ?>
            <?php $qs = array_merge($baseQs, ['page' => $page - 1]); ?>
            <a href="/admin/products?<?= http_build_query($qs) ?>">&laquo; Prev</a>
        <?php else: ?>
            <span class="disabled">&laquo; Prev</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php $qs = array_merge($baseQs, ['page' => $i]); ?>
            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="/admin/products?<?= http_build_query($qs) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <?php $qs = array_merge($baseQs, ['page' => $page + 1]); ?>
            <a href="/admin/products?<?= http_build_query($qs) ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
