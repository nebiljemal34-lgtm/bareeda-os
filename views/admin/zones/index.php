<?php
/**
 * View: Admin Delivery Zones
 * Variables: $zones
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Delivery Zones</h1>
</section>

<div class="container" style="padding: 32px 24px; max-width: 800px;">
    <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Dashboard</a>

    <!-- Add Zone -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Add New Zone</h3>
        <form method="POST" action="/admin/delivery-zones/store" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
            <?= csrf_field() ?>
            <div class="form-group" style="flex: 2; min-width: 200px; margin-bottom: 0;">
                <label class="form-label">Zone Name *</label>
                <input type="text" name="zone_name" class="form-input" required placeholder="e.g. Addis Ababa - Bole">
            </div>
            <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                <label class="form-label">Fee (ETB) *</label>
                <input type="number" name="fee" class="form-input" required step="0.01" min="0" value="0">
            </div>
            <button type="submit" class="btn btn-primary">Add Zone</button>
        </form>
    </div>

    <!-- Existing Zones -->
    <?php if (empty($zones)): ?>
        <p class="text-muted text-center">No delivery zones configured.</p>
    <?php else: ?>
        <?php foreach ($zones as $zone): ?>
        <div class="card" style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <strong style="color: var(--text);"><?= htmlspecialchars($zone['zone_name']) ?></strong>
                <span style="color: var(--gold); font-weight: 700;"><?= formatPrice((float) $zone['fee']) ?></span>
                <?php if ((int) $zone['is_active']): ?>
                    <span class="badge badge-success" style="font-size: 0.7rem;">Active</span>
                <?php else: ?>
                    <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted); font-size: 0.7rem;">Inactive</span>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 8px;">
                <form method="POST" action="/admin/delivery-zones/<?= (int) $zone['id'] ?>/update" style="display: flex; gap: 8px; align-items: center;">
                    <?= csrf_field() ?>
                    <input type="text" name="zone_name" value="<?= htmlspecialchars($zone['zone_name']) ?>" class="form-input" style="width: 200px; padding: 6px 10px; font-size: 0.82rem;" required>
                    <input type="number" name="fee" value="<?= (float) $zone['fee'] ?>" class="form-input" style="width: 80px; padding: 6px 10px; font-size: 0.82rem;" step="0.01" min="0" required>
                    <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.78rem;">Save</button>
                </form>
                <form method="POST" action="/admin/delivery-zones/<?= (int) $zone['id'] ?>/toggle" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.78rem;">
                        <?= (int) $zone['is_active'] ? 'Disable' : 'Enable' ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
