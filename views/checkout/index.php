<?php
/**
 * View: Checkout Page
 * Variables: $items, $cart_count, $subtotal, $delivery_fee, $total, $user, $zones
 */
?>

<section class="page-header">
    <h1>Checkout &middot; ክፍያ</h1>
    <p class="amharic-subtitle">Complete your order</p>
</section>

<form method="POST" action="/checkout">
    <?= csrf_field() ?>

    <div class="checkout-layout">
        <div>
            <!-- Step 1: Delivery Address -->
            <div class="checkout-step">
                <h3><span class="step-number">1</span> Delivery Address</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" required
                               value="<?= htmlspecialchars(old('full_name', ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" class="form-input" required
                               value="<?= htmlspecialchars(old('phone', $user['phone'] ?? '')) ?>"
                               placeholder="+251...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Street Address *</label>
                    <input type="text" id="address" name="address" class="form-input" required
                           value="<?= htmlspecialchars(old('address', $user['address'] ?? '')) ?>"
                           placeholder="House number, street name, landmark">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="city">City / Sub-city *</label>
                        <select id="city" name="city" class="form-input" required>
                            <option value="">Select area...</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= htmlspecialchars($zone['zone_name']) ?>"
                                        <?= old('city', $user['city'] ?? '') === $zone['zone_name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($zone['zone_name']) ?>
                                    (<?= formatPrice((float) $zone['fee']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="delivery_notes">Delivery Notes</label>
                    <textarea id="delivery_notes" name="delivery_notes" class="form-input" rows="2"
                              placeholder="Special instructions for delivery (optional)"><?= htmlspecialchars(old('delivery_notes')) ?></textarea>
                </div>
            </div>

            <!-- Step 2: Payment Method -->
            <div class="checkout-step">
                <h3><span class="step-number">2</span> Payment Method &middot; የክፍያ ዘዴ</h3>

                <label class="payment-option selected">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <div>
                        <div class="payment-option-label">Cash on Delivery (COD)</div>
                        <div class="payment-option-desc">Pay with cash when your order is delivered. ገንዘብ በማድረስ ጊዜ ይከፈሉ።</div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="bank_transfer">
                    <div>
                        <div class="payment-option-label">Bank Transfer</div>
                        <div class="payment-option-desc">
                            Transfer to our account before delivery.
                            <br><strong>Bank:</strong> Commercial Bank of Ethiopia
                            <br><strong>Account:</strong> 1000XXXXXXXXXX
                            <br><strong>Name:</strong> Bareeda Injera PLC
                        </div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="mobile_money">
                    <div>
                        <div class="payment-option-label">Mobile Money (Telebirr / CBE Birr)</div>
                        <div class="payment-option-desc">
                            Send payment via Telebirr or CBE Birr.
                            <br><strong>Telebirr:</strong> <?= APP_PHONE ?>
                            <br><strong>Name:</strong> Bareeda Injera
                        </div>
                    </div>
                </label>
            </div>

            <!-- Step 3: Order Review -->
            <div class="checkout-step">
                <h3><span class="step-number">3</span> Order Review &middot; ትዕዛዝ ገምግም</h3>

                <?php foreach ($items as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(198,183,155,0.06);">
                    <div>
                        <strong style="color: var(--text);"><?= htmlspecialchars($item['name']) ?></strong>
                        <span style="color: var(--text-muted); font-size: 0.85rem;"> &times; <?= (int) $item['quantity'] ?></span>
                    </div>
                    <div style="color: var(--gold); font-weight: 700;"><?= formatPrice((float) $item['line_total']) ?></div>
                </div>
                <?php endforeach; ?>

                <div style="margin-top: 16px;">
                    <div class="summary-line">
                        <span>Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Delivery</span>
                        <span><?= $delivery_fee > 0 ? formatPrice($delivery_fee) : 'FREE' ?></span>
                    </div>
                    <div class="summary-line total">
                        <span>Total</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                </div>
            </div>

            <!-- Place Order -->
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.05rem;">
                Place Order &middot; ትዕዛዝ ያስቀምጡ &rarr;
            </button>

            <p style="text-align: center; font-size: 0.78rem; color: var(--text-muted); margin-top: 16px;">
                By placing your order, you agree to our <a href="/terms" style="color: var(--gold);">Terms of Service</a>.
            </p>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="order-summary-box">
            <h3>Order Summary</h3>

            <?php foreach ($items as $item): ?>
            <div style="display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(198,183,155,0.05);">
                <?php if (!empty($item['image_url'])): ?>
                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(198,183,155,0.05); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--gold); opacity: 0.3;">&#127838;</div>
                <?php endif; ?>
                <div style="flex: 1;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text);"><?= htmlspecialchars($item['name']) ?></div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">Qty: <?= (int) $item['quantity'] ?></div>
                </div>
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--gold);"><?= formatPrice((float) $item['line_total']) ?></div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top: 16px;">
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="summary-line">
                    <span>Delivery</span>
                    <span><?= $delivery_fee > 0 ? formatPrice($delivery_fee) : '<span style="color: var(--success);">FREE</span>' ?></span>
                </div>
                <div class="summary-line total">
                    <span>Total</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
            </div>

            <?php if ($subtotal < FREE_DELIVERY_THRESHOLD): ?>
            <div style="margin-top: 16px; padding: 12px; border-radius: 8px; background: rgba(198,183,155,0.05); font-size: 0.82rem; color: var(--text-muted); text-align: center;">
                Add <?= formatPrice(FREE_DELIVERY_THRESHOLD - $subtotal) ?> more for free delivery!
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
// Payment option selection highlight
document.querySelectorAll('.payment-option input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-option').forEach(function(opt) { opt.classList.remove('selected'); });
        this.closest('.payment-option').classList.add('selected');
    });
});
</script>
