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
                <div class="empty-state-icon" style="color: var(--gold);"><?= icon('cart', 64) ?></div>
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
                                        <div class="cart-item-img" style="display: flex; align-items: center; justify-content: center; color: var(--gold); opacity: 0.5;"><?= icon('bag', 28) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/products/<?= (int) $item['product_id'] ?>" style="color: var(--text); text-decoration: none; font-weight: 600;">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                    <?php if (!empty($item['name_am'])): ?>
                                        <div style="font-size: 0.78rem; color: var(--gold); opacity: 0.5;"><?= htmlspecialchars($item['name_am']) ?></div>
                                    <?php endif; ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                        <?= packPieces($item) ?> injera / pack
                                    </div>
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

                    <?php if ($coupon_code !== ''): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;padding:12px;border:1px solid rgba(198,183,155,0.15);border-radius:8px;">
                            <span>Coupon <strong><?= sanitize($coupon_code) ?></strong></span>
                            <form method="POST" action="/coupon/remove">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary" style="padding:6px 10px;font-size:0.78rem;">Remove</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="/coupon/apply" style="display:flex;gap:8px;margin-bottom:20px;">
                            <?= csrf_field() ?>
                            <label for="coupon_code" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">Coupon code</label>
                            <input type="text" id="coupon_code" name="coupon_code" class="form-input" placeholder="Coupon code" required maxlength="50" style="min-width:0;">
                            <button type="submit" class="btn btn-secondary" style="white-space:nowrap;">Apply</button>
                        </form>
                    <?php endif; ?>

                    <div class="summary-line">
                        <span>Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="summary-line" style="color:var(--success);">
                        <span>Coupon discount</span>
                        <span>-<?= formatPrice($discount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-line">
                        <span>Delivery Fee</span>
                        <span><?= $delivery_fee > 0 ? formatPrice($delivery_fee) : '<span style="color: var(--success);">FREE</span>' ?></span>
                    </div>
                    <?php
                    $cartPieces = 0;
                    foreach ($items as $it) { $cartPieces += (int) $it['quantity'] * max(1, (int) ($it['pieces'] ?? 1)); }
                    $minPieces = freeDeliveryMinPieces();
                    ?>
                    <?php if ($delivery_fee > 0): ?>
                    <div style="font-size: 0.78rem; color: var(--text-muted); opacity: 0.85; margin-bottom: 8px;">
                        Free delivery on orders of more than <?= $minPieces ?> injera.
                        <?php if ($cartPieces > 0): ?>
                            Add <?= max(1, $minPieces + 1 - $cartPieces) ?> more injera to qualify (<?= $cartPieces ?> in cart).
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size: 0.78rem; color: var(--success); margin-bottom: 8px;">
                        You qualify for free delivery (<?= $cartPieces ?> injera).
                    </div>
                    <?php endif; ?>

                    <div class="summary-line total">
                        <span>Total</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>

                    <a href="/checkout" class="btn btn-primary mt-3" style="width: 100%; padding: 16px; font-size: 1rem;">
                        Proceed to Checkout &rarr;
                    </a>
                    <p style="font-size: 0.82rem; color: var(--text-muted); text-align: center; margin-top: 12px;">
                        No account needed &mdash; checkout as a guest.
                    </p>
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
                @media (max-width: 640px) {
                    .cart-table-wrap { overflow-x: visible; }
                    .cart-table-wrap .data-table thead { display: none; }
                    .cart-table-wrap .data-table,
                    .cart-table-wrap .data-table tbody { display: block; width: 100%; }
                    .cart-table-wrap .data-table tr {
                        display: grid; grid-template-columns: 64px 1fr auto;
                        gap: 6px 12px; padding: 16px; margin-bottom: 14px;
                        background: rgba(255,255,255,0.03);
                        border: 1px solid rgba(198,183,155,0.1);
                        border-radius: 12px;
                    }
                    .cart-table-wrap .data-table td { display: block; padding: 0; border: none; }
                    .cart-table-wrap .data-table tr:hover td { background: transparent; }
                    .cart-table-wrap .data-table td:nth-child(1) { grid-column: 1; grid-row: 1 / span 2; }
                    .cart-table-wrap .data-table td:nth-child(2) { grid-column: 2; grid-row: 1; }
                    .cart-table-wrap .data-table td:nth-child(3) { grid-column: 2; grid-row: 2; font-size: 0.8rem; align-self: center; }
                    .cart-table-wrap .data-table td:nth-child(4) { grid-column: 2 / span 2; grid-row: 3; }
                    .cart-table-wrap .data-table td:nth-child(5) { grid-column: 3; grid-row: 2; justify-self: end; align-self: center; }
                    .cart-table-wrap .data-table td:nth-child(6) { grid-column: 3; grid-row: 1; justify-self: end; }
                }
            </style>
        <?php endif; ?>
    </div>
</section>
