<?php
/**
 * View: Home Page
 * Variables: $featured, $cart_count
 */
?>

<!-- ═══ Hero Section ═══ -->
<section class="hero">
    <div class="hero-content">
        <p class="subtitle">100% Organic Teff &bull; Baked Fresh Daily</p>
        <h1>
            Fresh Teff Injera,
            <em>Delivered Daily</em>
        </h1>
        <p class="subtitle-am">100% ከጤፍ ብቻ የተዘጋጀ እንጀራ</p>
        <div class="hero-actions">
            <a href="/products" class="btn btn-primary">Shop Now &rarr;</a>
            <a href="/about" class="btn btn-secondary">Our Story</a>
        </div>
    </div>
</section>

<!-- ═══ Featured Products ═══ -->
<?php if (!empty($featured)): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title">Our Products &middot; </h2>
        <div class="grid grid-3" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
            <?php foreach ($featured as $product): ?>
            <div class="product-card">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-card-img" loading="lazy">
                <?php else: ?>
                    <div class="product-card-img-placeholder"><?= icon('bag', 48) ?></div>
                <?php endif; ?>
                <div class="product-card-body">
                    <div class="product-card-name"><?= htmlspecialchars($product['name']) ?></div>
                    <?php if (!empty($product['name_am'])): ?>
                        <div class="product-card-name-am"><?= htmlspecialchars($product['name_am']) ?></div>
                    <?php endif; ?>
                    <div class="product-card-price"><?= formatPrice((float) $product['price']) ?></div>
                    <div class="product-card-actions">
                        <a href="/products/<?= (int) $product['id'] ?>" class="btn btn-secondary">View Details</a>
                        <?php if ((int) $product['stock_quantity'] > 0): ?>
                        <form method="POST" action="/cart/add" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/products" class="btn btn-secondary">View All Products &rarr;</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ Why Choose Us ═══ -->
<section class="section section-light">
    <div class="container">
        <h2 class="section-title">Why Choose Bareeda &middot; ለምን በሬዳ?</h2>
        <div class="grid grid-3">
            <div class="feature-card card">
                <div class="feature-icon"><?= icon('leaf', 32) ?></div>
                <h3>100% Pure Teff</h3>
                <p>Our injera is made exclusively from premium Ethiopian teff grain. No wheat, no barley, no fillers. Just pure, nutritious teff.</p>
            </div>
            <div class="feature-card card">
                <div class="feature-icon"><?= icon('flame', 32) ?></div>
                <h3>Baked Fresh Daily</h3>
                <p>Every injera is hand-poured and freshly baked each morning. We never sell day-old injera. Freshness is our promise.</p>
            </div>
            <div class="feature-card card">
                <div class="feature-icon"><?= icon('truck', 32) ?></div>
                <h3>Free Delivery</h3>
                <p>Free delivery on orders of more than 100 pieces of injera. ከ100 እንጀራ በላይ ለሚያዙ ነፃ ማድረስ!</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ Newsletter ═══ -->
<section class="newsletter">
    <h2>Stay Fresh &middot; ትኩስ ሁን</h2>
    <p>Subscribe to our newsletter for exclusive offers, new products, and injera tips delivered to your inbox.</p>
    <form class="newsletter-form" method="POST" action="/contact">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Your email address" required>
        <input type="hidden" name="name" value="Newsletter Subscriber">
        <input type="hidden" name="subject" value="Newsletter Subscription">
        <input type="hidden" name="message" value="Subscribe me to the newsletter.">
        <button type="submit" class="btn btn-primary">Subscribe</button>
    </form>
</section>
