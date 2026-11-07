<?php
/**
 * View: Admin Coupons
 * Variables: $coupons
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Manage Coupons</h1>
</section>

<div class="container" style="padding: 32px 24px; max-width: 900px;">
    <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Dashboard</a>

    <!-- Add Coupon -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Create Coupon</h3>
        <form method="POST" action="/admin/coupons/store">
            <?= csrf_field() ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Code *</label>
                    <input type="text" name="code" class="form-input" required placeholder="e.g. SAVE20" style="text-transform: uppercase;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Type</label>
                    <select name="discount_type" class="form-input">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed (ETB)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Value *</label>
                    <input type="number" name="discount_value" class="form-input" required step="0.01" min="0">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 12px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Min Order (ETB)</label>
                    <input type="number" name="min_order_amount" class="form-input" step="0.01" min="0" value="0">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Max Uses (0 = unlimited)</label>
                    <input type="number" name="max_uses" class="form-input" min="0" value="0">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" class="form-input">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Create Coupon</button>
        </form>
    </div>

    <!-- Existing Coupons -->
    <?php if (empty($coupons)): ?>
        <p class="text-muted text-center">No coupons yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Order</th>
                        <th>Uses</th>
                        <th>Status</th>
                        <th>Expires</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $c): ?>
                    <tr>
                        <td><strong style="color: var(--gold);"><?= htmlspecialchars($c['code']) ?></strong></td>
                        <td>
                            <?= $c['discount_type'] === 'percentage' ? $c['discount_value'] . '%' : formatPrice((float) $c['discount_value']) ?>
                        </td>
                        <td style="color: var(--text-muted);"><?= formatPrice((float) $c['min_order_amount']) ?></td>
                        <td style="color: var(--text-muted);"><?= (int) $c['used_count'] ?> / <?= (int) $c['max_uses'] === 0 ? '&infin;' : (int) $c['max_uses'] ?></td>
                        <td>
                            <?php if ((int) $c['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.82rem;">
                            <?= !empty($c['expires_at']) ? formatDate($c['expires_at']) : 'Never' ?>
                        </td>
                        <td>
                            <form method="POST" action="/admin/coupons/<?= (int) $c['id'] ?>/toggle" class="inline-form">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">
                                    Toggle
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
