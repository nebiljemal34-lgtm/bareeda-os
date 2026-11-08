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

                <?php if ($is_guest): ?>
                <div class="form-group">
                    <label class="form-label" for="email">Email (optional)</label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="<?= htmlspecialchars(old('email', '')) ?>"
                           placeholder="you@example.com &mdash; for your order receipt">
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="address">Street Address *</label>
                    <input type="text" id="address" name="address" class="form-input" required
                           value="<?= htmlspecialchars(old('address', $user['address'] ?? '')) ?>"
                           placeholder="House number, street name, landmark">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="city">City / Sub-city *</label>
                        <select id="delivery_zone_id" name="delivery_zone_id" class="form-input" required>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= (int) $zone['id'] ?>"
                                        data-fee="<?= sanitize($zone['fee']) ?>"
                                        <?= (int) $selected_zone_id === (int) $zone['id'] ? 'selected' : '' ?>>
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

                <?php $selectedPayment = old('payment_method', 'cod'); ?>
                <label class="payment-option <?= $selectedPayment === 'cod' ? 'selected' : '' ?>">
                    <input type="radio" name="payment_method" value="cod" <?= $selectedPayment === 'cod' ? 'checked' : '' ?>>
                    <div>
                        <div class="payment-option-label">Cash on Delivery (COD)</div>
                        <div class="payment-option-desc">Pay with cash when your order is delivered. ገንዘብ በማድረስ ጊዜ ይከፈሉ።</div>
                    </div>
                </label>

                <?php if ($is_guest): ?>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;">
                    Guest checkout &mdash; pay cash when your injera arrives. No account required.
                </p>
                <?php endif; ?>

                <?php foreach ($payment_providers as $providerKey => $provider): ?>
                <label class="payment-option <?= $selectedPayment === $providerKey ? 'selected' : '' ?>">
                    <input type="radio" name="payment_method" value="<?= sanitize($providerKey) ?>" <?= $selectedPayment === $providerKey ? 'checked' : '' ?>>
                    <div>
                        <div class="payment-option-label"><?= sanitize($provider['name']) ?></div>
                        <div class="payment-option-desc">
                            <?= $provider['channel'] === 'bank' ? 'Bank payment' : 'Wallet payment' ?> with signed automatic verification.
                            <strong>Simulation only in this environment.</strong>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Step 3: Order Review -->
            <div class="checkout-step">
                <h3><span class="step-number">3</span> Order Review &middot; ትዕዛዝ ገምግም</h3>

                <?php foreach ($items as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(198,183,155,0.06);">
                    <div>
                        <strong style="color: var(--text);"><?= htmlspecialchars($item['name']) ?></strong>
                        <span style="color: var(--text-muted); font-size: 0.85rem;"> &times; <?= (int) $item['quantity'] ?></span>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= packPieces($item) ?> injera / pack</div>
                    </div>
                    <div style="color: var(--gold); font-weight: 700;"><?= formatPrice((float) $item['line_total']) ?></div>
                </div>
                <?php endforeach; ?>

                <div style="margin-top: 16px;">
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
                        <span>Delivery</span>
                        <span data-delivery><?= $delivery_fee > 0 ? formatPrice($delivery_fee) : 'FREE' ?></span>
                    </div>
                    <div class="summary-line total">
                        <span>Total</span>
                        <span data-total><?= formatPrice($total) ?></span>
                    </div>
                </div>
            </div>

            <!-- Place Order -->
            <?php if (!$accepting_orders): ?>
                <div class="alert alert-warning" style="margin-bottom:16px;">Online ordering is temporarily paused. Your cart is saved.</div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.05rem;" <?= !$accepting_orders ? 'disabled' : '' ?>>
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
                    <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(198,183,155,0.05); display: flex; align-items: center; justify-content: center; color: var(--gold); opacity: 0.5;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 8h16l-1 12H5L4 8Z"/><path d="M8 8a4 4 0 0 1 8 0"/></svg></div>
                <?php endif; ?>
                <div style="flex: 1;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text);"><?= htmlspecialchars($item['name']) ?></div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">Qty: <?= (int) $item['quantity'] ?> &middot; <?= packPieces($item) ?> injera / pack</div>
                </div>
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--gold);"><?= formatPrice((float) $item['line_total']) ?></div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top: 16px;">
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
                    <span>Delivery</span>
                    <span data-delivery><?= $delivery_fee > 0 ? formatPrice($delivery_fee) : 'FREE' ?></span>
                </div>
                <div class="summary-line total">
                    <span>Total</span>
                    <span data-total><?= formatPrice($total) ?></span>
                </div>
            </div>

            <?php if ($total_pieces <= $free_delivery_min_pieces): ?>
            <div style="margin-top: 16px; padding: 12px; border-radius: 8px; background: rgba(198,183,155,0.05); font-size: 0.82rem; color: var(--text-muted); text-align: center;">
                Add <?= max(1, $free_delivery_min_pieces + 1 - $total_pieces) ?> more injera for free delivery!
                <span style="display:block; opacity:0.7; margin-top:4px;"><?= $total_pieces ?> injera in your order &middot; free above <?= $free_delivery_min_pieces ?>.</span>
            </div>
            <?php else: ?>
            <div style="margin-top: 16px; padding: 12px; border-radius: 8px; background: rgba(76,175,80,0.08); font-size: 0.82rem; color: var(--success); text-align: center;">
                Free delivery unlocked &mdash; <?= $total_pieces ?> injera in your order.
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

var zoneSelect = document.getElementById('delivery_zone_id');
var subtotal = <?= json_encode((float) $subtotal) ?>;
var discount = <?= json_encode((float) $discount) ?>;
var totalPieces = <?= json_encode((int) $total_pieces) ?>;
var freeDeliveryMinPieces = <?= json_encode((int) $free_delivery_min_pieces) ?>;
function updateZoneTotals() {
    var option = zoneSelect.options[zoneSelect.selectedIndex];
    var fee = totalPieces > freeDeliveryMinPieces ? 0 : parseFloat(option.dataset.fee || '0');
    var total = Math.max(0, subtotal - discount + fee);
    var formatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.querySelectorAll('[data-delivery]').forEach(function(el) { el.textContent = fee > 0 ? formatter.format(fee) + ' ETB' : 'FREE'; });
    document.querySelectorAll('[data-total]').forEach(function(el) { el.textContent = formatter.format(total) + ' ETB'; });
}
zoneSelect.addEventListener('change', updateZoneTotals);
</script>
