<?php
/**
 * View: About Us Page
 */
?>

<!-- ═══ About Hero ═══ -->
<section class="about-hero">
    <h1>Our Story</h1>
    <p>
        Bareeda Injera was born from a simple belief: everyone deserves fresh, authentic teff injera made the traditional way.
        Founded in Dire Dawa, we combine time-honored recipes with modern convenience to bring Ethiopia's staple food to your table — daily.
    </p>
</section>

<!-- ═══ Mission & Vision ═══ -->
<section class="section">
    <div class="container">
        <div class="grid grid-2" style="gap: 48px; align-items: center;">
            <div>
                <h2 style="color: var(--gold); margin-bottom: 16px;">Our Mission</h2>
                <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 20px;">
                    To preserve and share the art of traditional Ethiopian injera making while making it accessible
                    to every household in Dire Dawa. We source the finest teff from Ethiopian farmers,
                    support local agriculture, and deliver freshness you can taste.
                </p>
                <p style="color: var(--text-muted); line-height: 1.8;">
                    Every injera we bake carries the warmth of Ethiopian hospitality — the spirit of "bareeda"
                    (ባረዳ), where community and food come together.
                </p>
            </div>
            <div style="aspect-ratio: 4/3; border-radius: var(--radius); overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 4px 16px rgba(0,0,0,0.3);">
                <img src="/assets/images/injera-product-hd.png" alt="Fresh Bareeda teff injera" loading="lazy" style="width: 130%; height: 100%; object-fit: cover; opacity: 0.3; margin-left: -30%;">
            </div>
        </div>
    </div>
</section>

<!-- ═══ Values ═══ -->
<section class="section section-light">
    <div class="container">
        <h2 class="section-title">Our Values</h2>
        <div class="grid grid-3">
            <div class="value-card card">
                <div class="value-icon" style="color: var(--gold);"><?= icon('check-circle', 40) ?></div>
                <h3>Quality First</h3>
                <p>We use only 100% organic Ethiopian teff. No shortcuts, no compromises. Every batch is tested for freshness and taste.</p>
            </div>
            <div class="value-card card">
                <div class="value-icon" style="color: var(--gold);"><?= icon('landmark', 40) ?></div>
                <h3>Tradition</h3>
                <p>Our recipes have been passed down through generations. We honor the traditional method of fermenting and baking injera on a mitad.</p>
            </div>
            <div class="value-card card">
                <div class="value-icon" style="color: var(--gold);"><?= icon('users', 40) ?></div>
                <h3>Community</h3>
                <p>We partner with local teff farmers and employ women in our community. When you choose Bareeda, you support Ethiopian families.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ Our Craft ═══ -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Our Craft</h2>
        <div class="grid grid-3">
            <div class="value-card card">
                <div class="value-icon" style="color: var(--gold);"><?= icon('leaf', 40) ?></div>
                <h3>Pure Ingredients</h3>
                <p>Carefully selected Ethiopian teff gives every injera its characteristic flavor, texture, and nutrition.</p>
            </div>
            <div class="value-card card">
                <div class="value-icon" style="color: var(--gold);"><?= icon('flame', 40) ?></div>
                <h3>Traditional Baking</h3>
                <p>Our Dire Dawa kitchen combines traditional fermentation and baking methods with hygienic, efficient production.</p>
            </div>
            <div class="value-card card">
                <div class="value-icon" style="color: var(--gold);"><?= icon('truck', 40) ?></div>
                <h3>Delivered Fresh</h3>
                <p>Freshly baked injera is prepared for local delivery so it reaches your table ready to share and enjoy.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ CTA ═══ -->
<section class="newsletter">
    <h2>Ready to Taste the Difference?</h2>
    <p>Order fresh teff injera today and experience authentic Ethiopian quality.</p>
    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
        <a href="/products" class="btn btn-primary">Shop Now</a>
        <a href="/contact" class="btn btn-secondary">Contact Us</a>
    </div>
</section>
