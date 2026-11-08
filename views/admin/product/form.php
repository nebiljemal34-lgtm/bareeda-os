<?php
/**
 * Admin Product - Create / Edit Form
 *
 * Variables: $product (null for create), $categories, $isEdit, $currentCatIds (for edit)
 */

$product     = $product ?? null;
$isEdit      = $isEdit ?? false;
$categories  = $categories ?? [];
$currentCatIds = $currentCatIds ?? [];

$formAction = $isEdit
    ? '/admin/products/' . $product['id'] . '/update'
    : '/admin/products/store';
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></div>
        <div class="adm-page-subtitle">
            <a href="/admin/products" style="color:var(--adm-muted);">&larr; Back to Products</a>
        </div>
    </div>
</div>

<!-- ── Product Form ─────────────────────────────────────── -->
<div class="adm-card" style="max-width:800px;">
    <form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Name (English) -->
        <div class="adm-form-group">
            <label class="adm-form-label" for="name">Product Name (English) <span style="color:var(--adm-danger);">*</span></label>
            <input type="text" id="name" name="name" class="adm-form-input" required
                   value="<?= sanitize(old('name', $product['name'] ?? '')) ?>"
                   placeholder="e.g. Classic Teff Injera">
        </div>

        <!-- Name (Amharic) -->
        <div class="adm-form-group">
            <label class="adm-form-label" for="name_am">Product Name (Amharic)</label>
            <input type="text" id="name_am" name="name_am" class="adm-form-input"
                   value="<?= sanitize(old('name_am', $product['name_am'] ?? '')) ?>"
                   placeholder="e.g. የተለመደ የጤፍ እንጀራ">
        </div>

        <!-- Description (English) -->
        <div class="adm-form-group">
            <label class="adm-form-label" for="description">Description (English)</label>
            <textarea id="description" name="description" class="adm-form-textarea" rows="3"
                      placeholder="Product description in English..."><?= sanitize(old('description', $product['description'] ?? '')) ?></textarea>
        </div>

        <!-- Description (Amharic) -->
        <div class="adm-form-group">
            <label class="adm-form-label" for="description_am">Description (Amharic)</label>
            <textarea id="description_am" name="description_am" class="adm-form-textarea" rows="3"
                      placeholder="የምርት መግለጫ በአማርኛ..."><?= sanitize(old('description_am', $product['description_am'] ?? '')) ?></textarea>
        </div>

        <!-- Price & Weight -->
        <div class="adm-form-row">
            <div class="adm-form-group">
                <label class="adm-form-label" for="price">Price (ETB) <span style="color:var(--adm-danger);">*</span></label>
                <input type="number" id="price" name="price" class="adm-form-input" required min="0" step="0.01"
                       value="<?= sanitize(old('price', $product['price'] ?? '')) ?>"
                       placeholder="0.00">
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label" for="weight_kg">Weight (kg)</label>
                <input type="number" id="weight_kg" name="weight_kg" class="adm-form-input" min="0" step="0.001"
                       value="<?= sanitize(old('weight_kg', $product['weight_kg'] ?? '')) ?>"
                       placeholder="0.000">
            </div>
        </div>

        <!-- Pieces per pack (drives the free-delivery threshold) -->
        <div class="adm-form-group">
            <label class="adm-form-label" for="pieces">Injera pieces per pack</label>
            <input type="number" id="pieces" name="pieces" class="adm-form-input" min="1" step="1"
                   value="<?= sanitize(old('pieces', $product['pieces'] ?? '1')) ?>"
                   placeholder="1">
            <span class="adm-text-muted adm-text-sm">Orders with more than the configured piece count get free delivery.</span>
        </div>

        <!-- Stock & Category -->
        <div class="adm-form-row">
            <div class="adm-form-group">
                <label class="adm-form-label" for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" class="adm-form-input" min="0" step="1"
                       value="<?= sanitize(old('stock_quantity', $product['stock_quantity'] ?? '0')) ?>"
                       placeholder="0">
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">Category</label>
                <select name="category_ids[]" class="adm-form-select" multiple style="min-height:70px;">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"
                            <?php
                                $selected = in_array($cat['id'], $currentCatIds);
                                // Also check old input
                                $oldCats = old('category_ids', '');
                                if ($oldCats && in_array($cat['id'], (array) $oldCats)) $selected = true;
                                echo $selected ? 'selected' : '';
                            ?>>
                            <?= sanitize($cat['name']) ?>
                            <?php if (!empty($cat['name_am'])): ?>(<?= sanitize($cat['name_am']) ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="adm-text-sm adm-text-muted" style="margin-top:4px;">Hold Ctrl/Cmd to select multiple</div>
            </div>
        </div>

        <!-- Image Upload -->
        <div class="adm-form-group">
            <label class="adm-form-label" for="image">Product Image</label>
            <?php if ($isEdit && !empty($product['image_url'])): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= sanitize($product['image_url']) ?>" alt="Current image"
                         style="max-width:120px;max-height:120px;border-radius:8px;border:1px solid var(--adm-border);">
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" class="adm-form-input" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="adm-text-sm adm-text-muted" style="margin-top:4px;">
                Max 5 MB. Formats: JPEG, PNG, WebP, GIF.
                <?= $isEdit ? 'Leave empty to keep current image.' : '' ?>
            </div>
        </div>

        <!-- Is Active -->
        <div class="adm-form-group">
            <label class="adm-form-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                    <?= ($isEdit ? (int)($product['is_active'] ?? 1) : 1) ? 'checked' : '' ?>>
                <span class="adm-form-label" style="margin:0;">Active (visible on storefront)</span>
            </label>
        </div>

        <!-- Submit -->
        <div style="display:flex;gap:12px;margin-top:24px;">
            <button type="submit" class="adm-btn adm-btn-gold">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <?= $isEdit ? 'Update Product' : 'Create Product' ?>
            </button>
            <a href="/admin/products" class="adm-btn adm-btn-outline">Cancel</a>
        </div>
    </form>
</div>
