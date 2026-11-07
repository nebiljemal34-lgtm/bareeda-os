<?php
/**
 * View: Checkout Success / Order Confirmation
 * Variables: $order
 */
?>

<section class="section">
    <div class="container" style="max-width: 640px; text-align: center;">

        <div class="success-icon">&#10003;</div>

        <h1 style="color: var(--gold); margin-bottom: 12px;">Order Confirmed!</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 8px;">
            Thank you for your order. We've received your order and it's being processed.
        </p>
        <p style="color: var(--gold); opacity: 0.7; font-size: 0.95rem; margin-bottom: 32px;">
            እናመሰግናለን! ትዕዛዝዎን ተቀብለናል።
        </p>

        <!-- Order Details Card -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.1); border-radius: var(--radius); padding: 32px; text-align: left; margin-bottom: 32px;">

            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(198,183,155,0.08);">
                <div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Order Number</div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--gold);"><?= htmlspecialchars($order['order_number']) ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Date</div>
                    <div style="font-size: 0.9rem; color: var(--text);"><?= formatDate($order['created_at']) ?></div>
                </div>
            </div>

            <!-- Items -->
            <?php if (!empty($order['items'])): ?>
            <div style="margin-bottom: 20px;">
                <?php foreach ($order['items'] as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.9rem;">
                    <span style="color: var(--text);">
                        <?= htmlspecialchars($item['name']) ?>
                        <span style="color: var(--text-muted);">&times; <?= (int) $item['quantity'] ?></span>
                    </span>
                    <span style="color: var(--gold); font-weight: 600;"><?= formatPrice((float) $item['total_price']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Totals -->
            <div style="border-top: 1px solid rgba(198,183,155,0.08); padding-top: 16px;">
                <div class="summary-line">
                    <span style="color: var(--text-muted);">Subtotal</span>
                    <span><?= formatPrice((float) $order['subtotal']) ?></span>
                </div>
                <div class="summary-line">
                    <span style="color: var(--text-muted);">Delivery</span>
                    <span><?= (float) $order['delivery_fee'] > 0 ? formatPrice((float) $order['delivery_fee']) : 'FREE' ?></span>
                </div>
                <?php if ((float) $order['discount_amount'] > 0): ?>
                <div class="summary-line">
                    <span style="color: var(--success);">Discount</span>
                    <span style="color: var(--success);">-<?= formatPrice((float) $order['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-line total">
                    <span>Total</span>
                    <span><?= formatPrice((float) $order['total']) ?></span>
                </div>
            </div>

            <!-- Delivery Info -->
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(198,183,155,0.08);">
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Delivery Address</div>
                <p style="color: var(--text); font-size: 0.9rem;"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
            </div>

            <!-- Payment Method -->
            <div style="margin-top: 16px;">
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Payment Method</div>
                <?php
                $paymentLabels = [
                    'cod' => 'Cash on Delivery',
                    'bank_transfer' => 'Bank Transfer',
                    'mobile_money' => 'Mobile Money (Telebirr / CBE Birr)',
                ];
                $method = $paymentLabels[$order['payment_method']] ?? $order['payment_method'];
                ?>
                <p style="color: var(--text); font-size: 0.9rem;"><?= htmlspecialchars($method) ?></p>
            </div>
        </div>

        <!-- Delivery Expectations -->
        <div style="background: rgba(198,183,155,0.05); border-radius: 10px; padding: 24px; margin-bottom: 32px; text-align: left;">
            <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 12px;">&#128666; What's Next?</h3>
            <ul style="color: var(--text-muted); font-size: 0.9rem; line-height: 2; padding-left: 20px;">
                <li>Your order status is currently <strong style="color: var(--warning);">Pending</strong></li>
                <li>We'll confirm your order shortly via phone or Telegram</li>
                <li>Delivery expected within 24 hours</li>
                <li>You can track your order in <a href="/orders" style="color: var(--gold);">My Orders</a></li>
            </ul>
        </div>

        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="/products" class="btn btn-primary">Continue Shopping</a>
            <a href="/orders/<?= (int) $order['id'] ?>" class="btn btn-secondary">View Order Details</a>
        </div>
    </div>
</section>
