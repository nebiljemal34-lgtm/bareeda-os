<?php
/**
 * View: Admin Categories
 * Variables: $categories
 */
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Manage Categories</h1>
</section>

<div class="container" style="padding: 32px 24px; max-width: 800px;">
    <a href="/admin" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Dashboard</a>

    <!-- Add Category -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 24px; margin-bottom: 24px;">
        <h3 style="color: var(--gold); font-size: 1rem; margin-bottom: 16px;">Add New Category</h3>
        <form method="POST" action="/admin/categories/store" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
            <?= csrf_field() ?>
            <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
                <label class="form-label">Name (EN) *</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
                <label class="form-label">Name (AM)</label>
                <input type="text" name="name_am" class="form-input">
            </div>
            <div class="form-group" style="width: 100px; margin-bottom: 0;">
                <label class="form-label">Sort</label>
                <input type="number" name="sort_order" class="form-input" value="0">
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </div>

    <!-- Existing Categories -->
    <?php if (empty($categories)): ?>
        <p class="text-muted text-center">No categories yet.</p>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
        <div class="card" style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <strong style="color: var(--text);"><?= htmlspecialchars($cat['name']) ?></strong>
                <?php if (!empty($cat['name_am'])): ?>
                    <span style="color: var(--gold); opacity: 0.6; margin-left: 8px; font-size: 0.88rem;"><?= htmlspecialchars($cat['name_am']) ?></span>
                <?php endif; ?>
                <span style="color: var(--text-muted); font-size: 0.78rem; margin-left: 12px;">Sort: <?= (int) $cat['sort_order'] ?></span>
            </div>
            <div style="display: flex; gap: 8px;">
                <form method="POST" action="/admin/categories/<?= (int) $cat['id'] ?>/update" style="display: flex; gap: 8px; align-items: center;">
                    <?= csrf_field() ?>
                    <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" class="form-input" style="width: 140px; padding: 6px 10px; font-size: 0.82rem;" required>
                    <input type="text" name="name_am" value="<?= htmlspecialchars($cat['name_am'] ?? '') ?>" class="form-input" style="width: 120px; padding: 6px 10px; font-size: 0.82rem;">
                    <input type="number" name="sort_order" value="<?= (int) $cat['sort_order'] ?>" class="form-input" style="width: 60px; padding: 6px 10px; font-size: 0.82rem;">
                    <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.78rem;">Save</button>
                </form>
                <form method="POST" action="/admin/categories/<?= (int) $cat['id'] ?>/delete" class="inline-form"
                      onsubmit="return confirm('Delete this category?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.78rem;">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
