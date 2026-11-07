<?php
/**
 * Admin Order - Detail / Show
 *
 * Variables: $order (with items, customer info)
 */

$orderStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
$paymentStatuses = ['pending', 'paid', 'refunded'];
$paymentMethods = ['cod' => 'Cash on Delivery', 'bank_transfer' => 'Bank Transfer', 'mobile_money' => 'Mobile Money'];
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Order <?= sanitize($order['order_number']) ?></div>
        <div class="adm-page-subtitle">
            <a href="/admin/orders" style="color:var(--adm-muted);">&larr; Back to Orders</a>
            &nbsp;&middot;&nbsp;
            Placed <?= formatDateTime($order['created_at']) ?>
        </div>
    </div>
    <div class="adm-d-flex adm-gap-8">
        <span class="adm-badge adm-badge-<?= $order['status'] ?>" style="font-size:13px;padding:5px 14px;"><?= str_replace('_', ' ', strtoupper($order['status'])) ?></span>
        <span class="adm-badge adm-badge-<?= $order['payment_status'] ?>" style="font-size:13px;padding:5px 14px;"><?= strtoupper($order['payment_status']) ?></span>
    </div>
</div>

<div class="adm-grid-2" style="grid-template-columns: 2fr 1fr;">

    <!-- ── Left Column ──────────────────────────────────── -->
    <div>

        <!-- Order Items -->
        <div class="adm-card adm-mb-20">
            <div class="adm-card-header">
                <div class="adm-card-title">Order Items</div>
                <div class="adm-text-muted adm-text-sm"><?= count($order['items']) ?> item(s)</div>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align:center;">Qty</th>
                            <th style="text-align:right;">Unit Price</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td>
                                <div class="adm-d-flex adm-ai-center adm-gap-8">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= sanitize($item['image_url']) ?>" alt="" class="adm-thumb" onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <div>
                                        <div class="adm-fw-600"><?= sanitize($item['name']) ?></div>
                                        <?php if (!empty($item['name_am'])): ?>
                                            <div class="adm-text-muted adm-text-sm"><?= sanitize($item['name_am']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align:center;"><?= (int) $item['quantity'] ?></td>
                            <td style="text-align:right;"><?= formatPrice((float) $item['unit_price']) ?></td>
                            <td style="text-align:right;" class="adm-fw-600"><?= formatPrice((float) $item['total_price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">Order Summary</div>
            </div>
            <ul class="adm-info-list">
                <li>
                    <span class="info-label">Subtotal</span>
                    <span class="info-value"><?= formatPrice((float) $order['subtotal']) ?></span>
                </li>
                <li>
                    <span class="info-label">Delivery Fee</span>
                    <span class="info-value"><?= formatPrice((float) $order['delivery_fee']) ?></span>
                </li>
                <?php if ((float) $order['discount_amount'] > 0): ?>
                <li>
                    <span class="info-label">Discount</span>
                    <span class="info-value" style="color:var(--adm-success);">-<?= formatPrice((float) $order['discount_amount']) ?></span>
                </li>
                <?php endif; ?>
                <li style="border-bottom:none;padding-top:12px;">
                    <span class="info-label" style="font-size:15px;font-weight:700;color:var(--adm-text);">Total</span>
                    <span class="info-value" style="font-size:18px;color:var(--adm-gold);"><?= formatPrice((float) $order['total']) ?></span>
                </li>
            </ul>
        </div>

    </div>

    <!-- ── Right Column ─────────────────────────────────── -->
    <div>

        <!-- Customer Info -->
        <div class="adm-card adm-mb-20">
            <div class="adm-card-header">
                <div class="adm-card-title">Customer</div>
            </div>
            <ul class="adm-info-list">
                <li>
                    <span class="info-label">Name</span>
                    <span class="info-value">
                        <a href="/admin/users/<?= $order['user_id'] ?>"><?= sanitize($order['first_name'] . ' ' . $order['last_name']) ?></a>
                    </span>
                </li>
                <li>
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= sanitize($order['email']) ?></span>
                </li>
                <?php if (!empty($order['phone'])): ?>
                <li>
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?= sanitize($order['phone']) ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Delivery Info -->
        <div class="adm-card adm-mb-20">
            <div class="adm-card-header">
                <div class="adm-card-title">Delivery Details</div>
            </div>
            <ul class="adm-info-list">
                <li style="flex-direction:column;gap:4px;">
                    <span class="info-label">Address</span>
                    <span class="info-value" style="word-break:break-word;"><?= nl2br(sanitize($order['delivery_address'])) ?></span>
                </li>
                <?php if (!empty($order['delivery_notes'])): ?>
                <li style="flex-direction:column;gap:4px;">
                    <span class="info-label">Notes</span>
                    <span class="info-value" style="word-break:break-word;"><?= nl2br(sanitize($order['delivery_notes'])) ?></span>
                </li>
                <?php endif; ?>
                <li>
                    <span class="info-label">Payment Method</span>
                    <span class="info-value"><?= sanitize($paymentMethods[$order['payment_method']] ?? $order['payment_method']) ?></span>
                </li>
            </ul>
        </div>

        <!-- Update Order Status -->
        <div class="adm-card adm-mb-20">
            <div class="adm-card-header">
                <div class="adm-card-title">Update Order Status</div>
            </div>
            <form method="POST" action="/admin/orders/<?= $order['id'] ?>/status">
                <?= csrf_field() ?>
                <div class="adm-form-group">
                    <select name="status" class="adm-form-select">
                        <?php foreach ($orderStatuses as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn-gold adm-btn-sm" style="width:100%;">
                    Update Status
                </button>
            </form>
        </div>

        <!-- Update Payment Status -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">Update Payment</div>
            </div>
            <form method="POST" action="/admin/orders/<?= $order['id'] ?>/payment">
                <?= csrf_field() ?>
                <div class="adm-form-group">
                    <select name="payment_status" class="adm-form-select">
                        <?php foreach ($paymentStatuses as $ps): ?>
                            <option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>>
                                <?= ucfirst($ps) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn-gold adm-btn-sm" style="width:100%;">
                    Update Payment
                </button>
            </form>
        </div>

    </div>

</div>
