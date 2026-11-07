<?php
/**
 * Admin Subscription - Detail / Show
 *
 * Variables: $subscription, $customer, $deliveries
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Subscription #<?= (int) $subscription['id'] ?></div>
        <div class="adm-page-subtitle">
            <a href="/admin/subscriptions" style="color:var(--adm-muted);">&larr; Back to Subscriptions</a>
            &nbsp;&middot;&nbsp;
            Started <?= formatDate($subscription['created_at']) ?>
        </div>
    </div>
    <span class="adm-badge adm-badge-<?= $subscription['status'] ?>" style="font-size:13px;padding:5px 14px;">
        <?= ucfirst($subscription['status']) ?>
    </span>
</div>

<div class="adm-grid-3" style="grid-template-columns: 1fr 1fr 1fr;">

    <!-- Subscription Info -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Subscription Details</div>
        </div>
        <ul class="adm-info-list">
            <li>
                <span class="info-label">Frequency</span>
                <span class="info-value"><?= ucfirst($subscription['frequency']) ?></span>
            </li>
            <li>
                <span class="info-label">Next Delivery</span>
                <span class="info-value"><?= formatDate($subscription['next_delivery_date']) ?></span>
            </li>
            <li>
                <span class="info-label">Status</span>
                <span class="adm-badge adm-badge-<?= $subscription['status'] ?>"><?= ucfirst($subscription['status']) ?></span>
            </li>
            <li>
                <span class="info-label">Created</span>
                <span class="info-value"><?= formatDateTime($subscription['created_at']) ?></span>
            </li>
        </ul>
    </div>

    <!-- Customer Info -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Customer</div>
        </div>
        <?php if ($customer): ?>
        <ul class="adm-info-list">
            <li>
                <span class="info-label">Name</span>
                <span class="info-value">
                    <a href="/admin/users/<?= $customer['id'] ?>"><?= sanitize($customer['first_name'] . ' ' . $customer['last_name']) ?></a>
                </span>
            </li>
            <li>
                <span class="info-label">Email</span>
                <span class="info-value"><?= sanitize($customer['email']) ?></span>
            </li>
            <?php if (!empty($customer['phone'])): ?>
            <li>
                <span class="info-label">Phone</span>
                <span class="info-value"><?= sanitize($customer['phone']) ?></span>
            </li>
            <?php endif; ?>
            <?php if (!empty($customer['address'])): ?>
            <li style="flex-direction:column;gap:4px;">
                <span class="info-label">Address</span>
                <span class="info-value"><?= sanitize($customer['address']) ?><?= $customer['city'] ? ', ' . sanitize($customer['city']) : '' ?></span>
            </li>
            <?php endif; ?>
        </ul>
        <?php else: ?>
            <div class="adm-empty"><p>Customer not found.</p></div>
        <?php endif; ?>
    </div>

    <!-- Product Info -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Product</div>
        </div>
        <ul class="adm-info-list">
            <li>
                <span class="info-label">Name</span>
                <span class="info-value"><?= sanitize($subscription['product_name']) ?></span>
            </li>
            <?php if (!empty($subscription['product_name_am'])): ?>
            <li>
                <span class="info-label">Name (Amharic)</span>
                <span class="info-value"><?= sanitize($subscription['product_name_am']) ?></span>
            </li>
            <?php endif; ?>
            <li>
                <span class="info-label">Price</span>
                <span class="info-value"><?= formatPrice((float) $subscription['product_price']) ?></span>
            </li>
        </ul>
        <?php if (!empty($subscription['product_image'])): ?>
            <div style="margin-top:12px;">
                <img src="<?= sanitize($subscription['product_image']) ?>" alt="" style="max-width:100%;border-radius:8px;border:1px solid var(--adm-border);">
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Delivery Schedule ────────────────────────────────── -->
<div class="adm-card adm-mt-20">
    <div class="adm-card-header">
        <div class="adm-card-title">Delivery Schedule</div>
        <div class="adm-text-muted adm-text-sm"><?= count($deliveries) ?> delivery record(s)</div>
    </div>

    <?php if (empty($deliveries)): ?>
        <div class="adm-empty">
            <p>No delivery records found.</p>
        </div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th>Delivered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $i => $delivery): ?>
                    <tr>
                        <td class="adm-fw-600"><?= $i + 1 ?></td>
                        <td><?= formatDate($delivery['scheduled_date']) ?></td>
                        <td>
                            <?php
                                $dStatus = $delivery['status'];
                                $badgeClass = $dStatus === 'delivered' ? 'adm-badge-delivered' : ($dStatus === 'skipped' ? 'adm-badge-cancelled' : 'adm-badge-pending');
                            ?>
                            <span class="adm-badge <?= $badgeClass ?>"><?= ucfirst($dStatus) ?></span>
                        </td>
                        <td class="adm-text-muted">
                            <?= $delivery['delivered_at'] ? formatDateTime($delivery['delivered_at']) : '&mdash;' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Status Actions ───────────────────────────────────── -->
<?php if ($subscription['status'] !== 'cancelled'): ?>
<div class="adm-card adm-mt-20">
    <div class="adm-card-header">
        <div class="adm-card-title">Actions</div>
    </div>
    <div class="adm-d-flex adm-gap-12" style="flex-wrap:wrap;">
        <?php if ($subscription['status'] === 'active'): ?>
            <form method="POST" action="/admin/subscriptions/<?= $subscription['id'] ?>/status" onsubmit="return confirm('Pause this subscription?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="pause">
                <button type="submit" class="adm-btn adm-btn-sm" style="background:rgba(251,191,36,0.15);color:var(--adm-warning);border:1px solid rgba(251,191,36,0.3);">
                    <svg viewBox="0 0 24 24" style="width:13px;height:13px;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                    Pause
                </button>
            </form>
            <form method="POST" action="/admin/subscriptions/<?= $subscription['id'] ?>/status" onsubmit="return confirm('Cancel this subscription permanently?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">
                    <svg viewBox="0 0 24 24" style="width:13px;height:13px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancel
                </button>
            </form>
        <?php elseif ($subscription['status'] === 'paused'): ?>
            <form method="POST" action="/admin/subscriptions/<?= $subscription['id'] ?>/status">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="resume">
                <button type="submit" class="adm-btn adm-btn-sm adm-btn-success">
                    <svg viewBox="0 0 24 24" style="width:13px;height:13px;"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Resume
                </button>
            </form>
            <form method="POST" action="/admin/subscriptions/<?= $subscription['id'] ?>/status" onsubmit="return confirm('Cancel this subscription permanently?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger">
                    <svg viewBox="0 0 24 24" style="width:13px;height:13px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancel
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
