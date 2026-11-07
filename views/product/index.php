<?php
/**
 * View: Product Listing
 * Variables: $products, $categories, $cart_count, $current_page, $total_pages, $total_items,
 *            $sort_by, $sort_dir, $current_category, $search_query, $page_title
 */
?>

<!-- ═══ Page Header ═══ -->
<section class="page-header">
    <h1><?= htmlspecialchars($page_title) ?> &middot; እኛ ምርቶች</h1>
    <?php if ($search_query): ?>
        <p class="amharic-subtitle"><?= $total_items ?> result<?= $total_items !== 1 ? 's' : '' ?> for "<?= htmlspecialchars($search_query) ?>"</p>
    <?php else: ?>
        <p class="amharic-subtitle">Premium teff injera, baked fresh daily</p>
    <?php endif; ?>
</section>

<!-- ═══ Shop Layout ═══ -->
<div class="shop-layout">

    <!-- Sidebar -->
    <aside class="shop-sidebar">
        <h3>Categories &middot; ምድቦች</h3>
        <ul>
            <li>
                <a href="/products" class="<?= $current_category === 0 ? 'active' : '' ?>">
                    All Products
                </a>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li>
                <a href="/categories/<?= (int) $cat['id'] ?>"
                   class="<?= $current_category === (int) $cat['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                    <?php if (!empty($cat['name_am'])): ?>
                        <span style="opacity: 0.5; font-size: 0.82rem;"> (<?= htmlspecialchars($cat['name_am']) ?>)</span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Search -->
        <div style="margin-top: 32px;">
            <h3>Search &middot; ፈልግ</h3>
            <form method="GET" action="/search" style="margin-top: 12px;">
                <input type="text" name="q" class="form-input" placeholder="Search products..."
                       value="<?= htmlspecialchars($search_query) ?>" style="font-size: 0.85rem;">
                <button type="submit" class="btn btn-primary mt-1" style="width: 100%; font-size: 0.82rem; padding: 10px;">Search</button>
            </form>
        </div>

        <!-- Sort -->
        <div style="margin-top: 32px;">
            <h3>Sort By</h3>
            <form method="GET" action="/products" style="margin-top: 12px;">
                <?php if ($current_category > 0): ?>
                    <!-- Stay in category context -->
                <?php endif; ?>
                <?php if ($search_query): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
                <?php endif; ?>
                <select name="sort" class="form-input" onchange="this.form.submit()" style="font-size: 0.85rem; padding: 10px;">
                    <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Newest</option>
                    <option value="price" <?= $sort_by === 'price' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Name: A-Z</option>
                </select>
            </form>
        </div>
    </aside>

    <!-- Product Grid -->
    <div>
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128269;</div>
                <h2>No Products Found</h2>
                <p>Try adjusting your search or browse our categories.</p>
                <a href="/products" class="btn btn-primary mt-2">View All Products</a>
            </div>
        <?php else: ?>
            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?= htmlspecialchars($product['image_url']) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="product-card-img" loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="product-card-img-placeholder" style="display:none;">&#127838;</div>
                    <?php else: ?>
                        <div class="product-card-img-placeholder">&#127838;</div>
                    <?php endif; ?>

                    <div class="product-card-body">
                        <a href="/products/<?= (int) $product['id'] ?>" style="text-decoration: none;">
                            <div class="product-card-name"><?= htmlspecialchars($product['name']) ?></div>
                            <?php if (!empty($product['name_am'])): ?>
                                <div class="product-card-name-am"><?= htmlspecialchars($product['name_am']) ?></div>
                            <?php endif; ?>
                        </a>

                        <div class="product-card-price"><?= formatPrice((float) $product['price']) ?></div>

                        <?php if (!empty($product['weight_kg'])): ?>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px;">
                                <?= number_format((float) $product['weight_kg'], 2) ?> kg
                            </div>
                        <?php endif; ?>

                        <div class="product-card-actions">
                            <a href="/products/<?= (int) $product['id'] ?>" class="btn btn-secondary">Details</a>
                            <?php if ((int) $product['stock_quantity'] > 0): ?>
                            <form method="POST" action="/cart/add" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                            </form>
                            <?php else: ?>
                            <button class="btn" disabled style="opacity: 0.4; cursor: not-allowed; flex: 1; padding: 10px 16px; font-size: 0.82rem; background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid rgba(198,183,155,0.1); border-radius: 50px;">Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $qs = [];
                if ($search_query) $qs['q'] = $search_query;
                if ($sort_by !== 'created_at') $qs['sort'] = $sort_by;
                if ($current_category > 0) {
                    $baseUrl = '/categories/' . $current_category;
                } elseif ($search_query) {
                    $baseUrl = '/search';
                } else {
                    $baseUrl = '/products';
                }
                $qsStr = !empty($qs) ? '&' . http_build_query($qs) : '';
                ?>

                <?php if ($current_page > 1): ?>
                    <a href="<?= $baseUrl ?>?page=<?= $current_page - 1 ?><?= $qsStr ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $current_page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>?page=<?= $i ?><?= $qsStr ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="<?= $baseUrl ?>?page=<?= $current_page + 1 ?><?= $qsStr ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
