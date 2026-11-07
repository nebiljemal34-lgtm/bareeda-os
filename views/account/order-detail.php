<?php
/**
 * View: Account Order Detail
 * Variables: $order
 */

$statusLabels = [
    'pending'          => 'Pending',
    'confirmed'        => 'Confirmed',
    'preparing'        => 'Preparing',
    'out_for_delivery' => 'Out for Delivery',
    'delivered'        => 'Delivered',
    'cancelled'        => 'Cancelled',
];

$paymentLabels = [
    'cod'           => 'Cash on Delivery',
    'bank_transfer' => 'Bank Transfer',
    'mobile_money'  => 'Mobile Money',
];
?>

<section class="page-header">
    <h1>Order Details</h1>
    <p class="amharic-subtitle"><?= htmlspecialchars($order['order_number']) ?></p>
</section>

<div class="account-layout">
    <!-- Sidebar -->
    <aside class="account-sidebar">
        <h3>Menu</h3>
        <ul>
            <li><a href="/account">Dashboard</a></li>
            <li><a href="/orders" class="active">My Orders</a></li>
            <li><a href="/subscriptions">Subscriptions</a></li>
            <li><a href="/account#profile">Edit Profile</a></li>
            <li><a href="/logout">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div>
        <div style="margin-bottom: 16px;">
            <a href="/orders" style="color: var(--gold); text-decoration: none; font-size: 0.9rem;">&larr; Back to Orders</a>
        </div>

        <!-- Order Info -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
            <div class="grid grid-4" style="gap: 16px;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Order Number</div>
                    <div style="font-weight: 800; color: var(--gold);"><?= htmlspecialchars($order['order_number']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Date</div>
                    <div style="font-weight: 600;"><?= formatDateTime($order['created_at']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Status</div>
                    <span class="badge status-<?= $order['status'] ?>">
                        <?= $statusLabels[$order['status']] ?? ucfirst($order['status']) ?>
                    </span>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Total</div>
                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--gold);"><?= formatPrice((float) $order['total']) ?></div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
            <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Items</h3>

            <?php if (!empty($order['items'])): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                <?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <?php if (!empty($item['name_am'])): ?>
                                        <div style="font-size: 0.78rem; color: var(--gold); opacity: 0.5;"><?= htmlspecialchars($item['name_am']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="color: var(--text-muted);"><?= formatPrice((float) $item['unit_price']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td style="font-weight: 700; color: var(--gold);"><?= formatPrice((float) $item['total_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Totals -->
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(198,183,155,0.08); max-width: 320px; margin-left: auto;">
                <div class="summary-line">
                    <span style="color: var(--text-muted);">Subtotal</span>
                    <span><?= formatPrice((float) $order['subtotal']) ?></span>
                </div>
                <div class="summary-line">
                    <span style="color: var(--text-muted);">Delivery Fee</span>
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
        </div>

        <!-- Delivery & Payment Info -->
        <div class="grid grid-2" style="gap: 24px;">
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px;">
                <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Delivery Address</h3>
                <p style="color: var(--text); line-height: 1.7;"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                <?php if (!empty($order['delivery_notes'])): ?>
                    <div style="margin-top: 12px; padding: 12px; border-radius: 8px; background: rgba(198,183,155,0.04);">
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Notes</div>
                        <p style="font-size: 0.88rem; color: var(--text);"><?= nl2br(htmlspecialchars($order['delivery_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px;">
                <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Payment Information</h3>
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Method</div>
                    <div style="font-weight: 600;"><?= $paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Status</div>
                    <span class="badge" style="background: rgba(198,183,155,0.08); color: var(--text-muted);">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
