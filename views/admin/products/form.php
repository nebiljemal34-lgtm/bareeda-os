<?php
/**
 * View: Admin Product Form (Create / Edit)
 * Variables: $product (null for create), $categories
 */
$isEdit = !empty($product);
$action = $isEdit ? '/admin/products/' . (int) $product['id'] . '/update' : '/admin/products/store';
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h1>
</section>

<div class="container" style="padding: 32px 24px; max-width: 720px;">
    <a href="/admin/products" style="color: var(--gold); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 24px;">&larr; Back to Products</a>

    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(198,183,155,0.08); border-radius: var(--radius); padding: 32px;">
        <form method="POST" action="<?= $action ?>">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Product Name (EN) *</label>
                    <input type="text" id="name" name="name" class="form-input" required
                           value="<?= htmlspecialchars(old('name', $product['name'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="name_am">Product Name (AM)</label>
                    <input type="text" id="name_am" name="name_am" class="form-input"
                           value="<?= htmlspecialchars(old('name_am', $product['name_am'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description (EN)</label>
                <textarea id="description" name="description" class="form-input" rows="3"><?= htmlspecialchars(old('description', $product['description'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="description_am">Description (AM)</label>
                <textarea id="description_am" name="description_am" class="form-input" rows="3"><?= htmlspecialchars(old('description_am', $product['description_am'] ?? '')) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="price">Price (ETB) *</label>
                    <input type="number" id="price" name="price" class="form-input" required step="0.01" min="0"
                           value="<?= htmlspecialchars(old('price', $product['price'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="weight_kg">Weight (kg)</label>
                    <input type="number" id="weight_kg" name="weight_kg" class="form-input" step="0.001" min="0"
                           value="<?= htmlspecialchars(old('weight_kg', $product['weight_kg'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-input" required min="0"
                           value="<?= htmlspecialchars(old('stock_quantity', $product['stock_quantity'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="is_active">Status</label>
                    <select id="is_active" name="is_active" class="form-input">
                        <option value="1" <?= old('is_active', ($product['is_active'] ?? 1)) == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= old('is_active', ($product['is_active'] ?? 1)) == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="image_url">Image URL</label>
                <input type="text" id="image_url" name="image_url" class="form-input"
                       value="<?= htmlspecialchars(old('image_url', $product['image_url'] ?? '')) ?>"
                       placeholder="/uploads/products/...">
            </div>

            <!-- Categories -->
            <?php if (!empty($categories)): ?>
            <div class="form-group">
                <label class="form-label">Categories</label>
                <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px;">
                    <?php
                    $selectedCats = [];
                    if ($isEdit && !empty($product['categories'])) {
                        foreach ($product['categories'] as $c) {
                            $selectedCats[] = $c['id'];
                        }
                    }
                    $oldCats = $_POST['category_ids'] ?? [];
                    ?>
                    <?php foreach ($categories as $cat): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="category_ids[]" value="<?= (int) $cat['id'] ?>"
                                <?= in_array($cat['id'], $selectedCats) || in_array($cat['id'], $oldCats) ? 'checked' : '' ?>
                                style="accent-color: var(--gold);">
                            <span style="font-size: 0.9rem;"><?= htmlspecialchars($cat['name']) ?></span>
                            <?php if (!empty($cat['name_am'])): ?>
                                <span style="font-size: 0.78rem; color: var(--gold); opacity: 0.5;">(<?= htmlspecialchars($cat['name_am']) ?>)</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Product' : 'Create Product' ?></button>
                <a href="/admin/products" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
