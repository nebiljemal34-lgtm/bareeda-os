<?php
/**
 * View: Admin Order Detail
 * Variables: $order
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Order: <?= htmlspecialchars($order['order_number']) ?></h1>
</section>

<div class="container" style="padding: 32px 24px; max-width: 900px;">
    <a href="/admin/orders" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Back to Orders</a>

    <!-- Order Info -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <div class="grid grid-4" style="gap: 16px;">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Customer</div>
                <div style="font-weight: 600;"><?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?></div>
                <div style="font-size: 0.82rem; color: var(--text-muted);"><?= htmlspecialchars($order['email'] ?? '') ?></div>
                <?php if (!empty($order['phone'])): ?>
                    <div style="font-size: 0.82rem; color: var(--text-muted);"><?= htmlspecialchars($order['phone']) ?></div>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Date</div>
                <div><?= formatDateTime($order['created_at']) ?></div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Total</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: var(--gold);"><?= formatPrice((float) $order['total']) ?></div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Payment</div>
                <div><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></div>
                <span class="badge" style="background: rgba(198,183,155,0.08); color: var(--text-muted); margin-top: 4px;"><?= ucfirst($order['payment_status']) ?></span>
            </div>
        </div>
    </div>

    <!-- Status Updates -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Update Status</h3>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
            <form method="POST" action="/admin/orders/<?= (int) $order['id'] ?>/status" style="display: flex; gap: 8px; align-items: end;">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label" for="order_status">Order Status</label>
                    <select id="order_status" name="status" class="form-input" style="min-width: 160px;">
                        <?php foreach (['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 20px;">Update</button>
            </form>

            <form method="POST" action="/admin/orders/<?= (int) $order['id'] ?>/payment" style="display: flex; gap: 8px; align-items: end;">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label" for="pay_status">Payment Status</label>
                    <select id="pay_status" name="payment_status" class="form-input" style="min-width: 120px;">
                        <?php foreach (['pending','paid','refunded'] as $ps): ?>
                            <option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 20px;">Update</button>
            </form>
        </div>
        <div style="margin-top: 12px;">
            Current: <span class="badge status-<?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
        </div>
    </div>

    <!-- Items -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Items</h3>
        <?php if (!empty($order['items'])): ?>
        <table class="data-table">
            <thead>
                <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= formatPrice((float) $item['unit_price']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td style="font-weight: 700; color: var(--gold);"><?= formatPrice((float) $item['total_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(198,183,155,0.08); max-width: 300px; margin-left: auto;">
            <div class="summary-line"><span>Subtotal</span><span><?= formatPrice((float) $order['subtotal']) ?></span></div>
            <div class="summary-line"><span>Delivery</span><span><?= formatPrice((float) $order['delivery_fee']) ?></span></div>
            <?php if ((float) $order['discount_amount'] > 0): ?>
                <div class="summary-line"><span style="color: var(--success);">Discount</span><span style="color: var(--success);">-<?= formatPrice((float) $order['discount_amount']) ?></span></div>
            <?php endif; ?>
            <div class="summary-line total"><span>Total</span><span><?= formatPrice((float) $order['total']) ?></span></div>
        </div>
    </div>

    <!-- Delivery Address -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 12px;">Delivery Address</h3>
        <p style="color: var(--text); line-height: 1.7;"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
        <?php if (!empty($order['delivery_notes'])): ?>
            <div style="margin-top: 12px; padding: 12px; border-radius: 8px; background: rgba(198,183,155,0.04);">
                <strong style="font-size: 0.82rem; color: var(--text-muted);">Notes:</strong>
                <p style="font-size: 0.88rem; margin-top: 4px;"><?= nl2br(htmlspecialchars($order['delivery_notes'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
