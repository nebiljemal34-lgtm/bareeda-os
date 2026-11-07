<?php
/**
 * View: Product Detail
 * Variables: $product, $related, $cart_count
 */
?>

<div class="container" style="padding-top: 20px;">
    <nav style="margin-bottom: 24px; font-size: 0.85rem;">
        <a href="/" style="color: var(--text-muted); text-decoration: none;">Home</a>
        <span style="color: var(--text-muted);"> / </span>
        <a href="/products" style="color: var(--text-muted); text-decoration: none;">Products</a>
        <span style="color: var(--text-muted);"> / </span>
        <span style="color: var(--gold);"><?= htmlspecialchars($product['name']) ?></span>
    </nav>
</div>

<!-- ═══ Product Detail ═══ -->
<section class="product-detail">
    <div>
        <?php if (!empty($product['image_url'])): ?>
            <img src="<?= htmlspecialchars($product['image_url']) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 class="product-detail-img"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="product-detail-img-placeholder" style="display:none;">&#127838;</div>
        <?php else: ?>
            <div class="product-detail-img-placeholder">&#127838;</div>
        <?php endif; ?>
    </div>

    <div class="product-detail-info">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <?php if (!empty($product['name_am'])): ?>
            <div class="name-am"><?= htmlspecialchars($product['name_am']) ?></div>
        <?php endif; ?>

        <div class="price"><?= formatPrice((float) $product['price']) ?></div>

        <?php if (!empty($product['weight_kg'])): ?>
            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">
                Weight: <?= number_format((float) $product['weight_kg'], 2) ?> kg
            </div>
        <?php endif; ?>

        <!-- Stock Status -->
        <?php
        $stock = (int) $product['stock_quantity'];
        if ($stock > 10) {
            $stockClass = 'stock-in';
            $stockText = 'In Stock';
        } elseif ($stock > 0) {
            $stockClass = 'stock-low';
            $stockText = "Only {$stock} left!";
        } else {
            $stockClass = 'stock-out';
            $stockText = 'Out of Stock';
        }
        ?>
        <div class="stock-status">
            <span class="stock-dot <?= $stockClass ?>"></span>
            <span style="color: var(--text-muted);"><?= $stockText ?></span>
        </div>

        <!-- Description -->
        <?php if (!empty($product['description'])): ?>
            <div class="description"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($product['description_am'])): ?>
            <div class="description" style="opacity: 0.7; font-style: italic; margin-top: 8px;">
                <?= nl2br(htmlspecialchars($product['description_am'])) ?>
            </div>
        <?php endif; ?>

        <!-- Categories -->
        <?php if (!empty($product['categories'])): ?>
        <div style="margin-bottom: 24px;">
            <?php foreach ($product['categories'] as $cat): ?>
                <a href="/categories/<?= (int) $cat['id'] ?>" class="badge" style="background: rgba(198,183,155,0.1); color: var(--gold); text-decoration: none;">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Add to Cart -->
        <?php if ($stock > 0): ?>
        <form method="POST" action="/cart/add" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

            <div class="qty-control">
                <button type="button" onclick="let i=this.parentNode.querySelector('input');i.value=Math.max(1,parseInt(i.value)-1);">&#8722;</button>
                <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" style="width: 56px;">
                <button type="button" onclick="let i=this.parentNode.querySelector('input');i.value=Math.min(<?= $stock ?>,parseInt(i.value)+1);">&#43;</button>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 14px 32px; font-size: 1rem;">
                Add to Cart
            </button>
        </form>
        <?php else: ?>
        <div class="mt-3">
            <button class="btn" disabled style="opacity: 0.4; cursor: not-allowed; padding: 14px 32px; font-size: 1rem; background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid rgba(198,183,155,0.1); border-radius: 50px;">
                Out of Stock
            </button>
        </div>
        <?php endif; ?>

        <!-- Delivery Info -->
        <div style="margin-top: 32px; padding: 20px; border-radius: 10px; background: rgba(198,183,155,0.04); border: 1px solid rgba(198,183,155,0.08);">
            <div style="display: flex; gap: 12px; align-items: flex-start; margin-bottom: 12px;">
                <span style="font-size: 1.2rem;">&#128666;</span>
                <div>
                    <strong style="color: var(--text); font-size: 0.9rem;">Same-Day Delivery</strong>
                    <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 2px;">Order before noon for evening delivery across Addis Ababa</p>
                </div>
            </div>
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <span style="font-size: 1.2rem;">&#9989;</span>
                <div>
                    <strong style="color: var(--text); font-size: 0.9rem;">Freshness Guaranteed</strong>
                    <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 2px;">Baked fresh every morning. 100% pure teff.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ Related Products ═══ -->
<?php if (!empty($related)): ?>
<section class="section section-light">
    <div class="container">
        <h2 class="section-title">You May Also Like</h2>
        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
            <?php foreach ($related as $rel): ?>
            <div class="product-card">
                <?php if (!empty($rel['image_url'])): ?>
                    <img src="<?= htmlspecialchars($rel['image_url']) ?>"
                         alt="<?= htmlspecialchars($rel['name']) ?>"
                         class="product-card-img" loading="lazy"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="product-card-img-placeholder" style="display:none;">&#127838;</div>
                <?php else: ?>
                    <div class="product-card-img-placeholder">&#127838;</div>
                <?php endif; ?>
                <div class="product-card-body">
                    <a href="/products/<?= (int) $rel['id'] ?>" style="text-decoration: none;">
                        <div class="product-card-name"><?= htmlspecialchars($rel['name']) ?></div>
                    </a>
                    <div class="product-card-price"><?= formatPrice((float) $rel['price']) ?></div>
                    <div class="product-card-actions">
                        <a href="/products/<?= (int) $rel['id'] ?>" class="btn btn-secondary">View</a>
                        <?php if ((int) $rel['stock_quantity'] > 0): ?>
                        <form method="POST" action="/cart/add" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $rel['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary">Add</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
