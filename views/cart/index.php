<?php
/**
 * View: Shopping Cart
 * Variables: $items, $cart_count, $subtotal, $delivery_fee, $total
 */
?>

<section class="page-header">
    <h1>Shopping Cart &middot; ጋሪ</h1>
    <p class="amharic-subtitle"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?> in your cart</p>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($items)): ?>
            <!-- Empty Cart -->
            <div class="empty-state">
                <div class="empty-state-icon">&#128722;</div>
                <h2>Your Cart is Empty</h2>
                <p>Looks like you haven't added any fresh injera yet!</p>
                <a href="/products" class="btn btn-primary">Browse Products &rarr;</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr 380px; gap: 40px; align-items: start;">

                <!-- Cart Items -->
                <div class="cart-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Item</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th style="width: 140px;">Quantity</th>
                                <th>Subtotal</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             class="cart-item-img"
                                             onerror="this.style.display='none';">
                                    <?php else: ?>
                                        <div class="cart-item-img" style="display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--gold); opacity: 0.3;">&#127838;</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/products/<?= (int) $item['product_id'] ?>" style="color: var(--text); text-decoration: none; font-weight: 600;">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                    <?php if (!empty($item['name_am'])): ?>
                                        <div style="font-size: 0.78rem; color: var(--gold); opacity: 0.5;"><?= htmlspecialchars($item['name_am']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-muted);"><?= formatPrice((float) $item['price']) ?></td>
                                <td>
                                    <form method="POST" action="/cart/update">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                        <div class="qty-control">
                                            <button type="button" onclick="let i=this.parentNode.querySelector('input');let f=this.closest('form');i.value=Math.max(1,parseInt(i.value)-1);f.submit();">&#8722;</button>
                                            <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>"
                                                   min="1" max="<?= (int) $item['stock_quantity'] ?>"
                                                   onchange="this.closest('form').submit();">
                                            <button type="button" onclick="let i=this.parentNode.querySelector('input');let f=this.closest('form');i.value=Math.min(<?= (int) $item['stock_quantity'] ?>,parseInt(i.value)+1);f.submit();">&#43;</button>
                                        </div>
                                    </form>
                                </td>
                                <td style="font-weight: 700; color: var(--gold);">
                                    <?= formatPrice((float) $item['line_total']) ?>
                                </td>
                                <td>
                                    <form method="POST" action="/cart/remove">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                        <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.2rem; padding: 4px; opacity: 0.6; transition: opacity 0.2s;"
                                                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"
                                                title="Remove item">&times;</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="/products" class="btn btn-secondary">&larr; Continue Shopping</a>
                        <form method="POST" action="/cart/clear" class="inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn" style="background: rgba(244,67,54,0.1); color: var(--danger); border: 1px solid rgba(244,67,54,0.2);">Clear Cart</button>
                        </form>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3 style="color: var(--gold); margin-bottom: 20px;">Order Summary</h3>

                    <div class="summary-line">
                        <span>Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Delivery Fee</span>
                        <span><?= $delivery_fee > 0 ? formatPrice($delivery_fee) : '<span style="color: var(--success);">FREE</span>' ?></span>
                    </div>
                    <?php if ($delivery_fee > 0): ?>
                    <div style="font-size: 0.78rem; color: var(--text-muted); opacity: 0.7; margin-bottom: 8px;">
                        Free delivery on orders over <?= formatPrice(FREE_DELIVERY_THRESHOLD) ?>
                    </div>
                    <?php endif; ?>

                    <div class="summary-line total">
                        <span>Total</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <a href="/checkout" class="btn btn-primary mt-3" style="width: 100%; padding: 16px; font-size: 1rem;">
                            Proceed to Checkout &rarr;
                        </a>
                    <?php else: ?>
                        <a href="/login" class="btn btn-primary mt-3" style="width: 100%; padding: 16px; font-size: 1rem;">
                            Login to Checkout &rarr;
                        </a>
                        <p style="font-size: 0.82rem; color: var(--text-muted); text-align: center; margin-top: 12px;">
                            Or <a href="/register" style="color: var(--gold);">create an account</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Responsive override -->
            <style>
                @media (max-width: 768px) {
                    .section > .container > div[style*="grid-template-columns: 1fr 380px"] {
                        grid-template-columns: 1fr !important;
                    }
                    .cart-summary { margin-left: 0 !important; }
                }
            </style>
        <?php endif; ?>
    </div>
</section>
