<?php
/**
 * Admin Reports - Products
 *
 * Variables: $topProducts, $lowStock, $neverSold
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Product Report</div>
        <div class="adm-page-subtitle">Performance analysis and stock alerts</div>
    </div>
    <div class="adm-d-flex adm-gap-8">
        <a href="/admin/reports/sales" class="adm-btn adm-btn-sm adm-btn-outline">Sales Report</a>
        <a href="/admin/reports/customers" class="adm-btn adm-btn-sm adm-btn-outline">Customer Report</a>
    </div>
</div>

<div class="adm-grid-2">

    <!-- ── Top Selling Products ─────────────────────────── -->
    <div class="adm-card" style="grid-column: 1 / -1;">
        <div class="adm-card-header">
            <div class="adm-card-title">Top Selling Products</div>
            <div class="adm-text-muted adm-text-sm">By revenue</div>
        </div>

        <?php if (empty($topProducts)): ?>
            <div class="adm-empty">
                <p>No product sales data available yet.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Product</th>
                            <th style="text-align:center;">Qty Sold</th>
                            <th style="text-align:right;">Revenue</th>
                            <th style="width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $i => $prod): ?>
                        <tr>
                            <td class="adm-text-muted"><?= $i + 1 ?></td>
                            <td>
                                <div class="adm-d-flex adm-ai-center adm-gap-8">
                                    <?php if (!empty($prod['image_url'])): ?>
                                        <img src="<?= sanitize($prod['image_url']) ?>" alt="" class="adm-thumb" onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <span class="adm-fw-600"><?= sanitize($prod['name']) ?></span>
                                </div>
                            </td>
                            <td style="text-align:center;" class="adm-fw-600"><?= number_format((float) $prod['total_qty_sold']) ?></td>
                            <td style="text-align:right;" class="adm-fw-600"><?= formatPrice((float) $prod['total_revenue']) ?></td>
                            <td><a href="/admin/products/<?= $prod['id'] ?>/edit" class="adm-btn adm-btn-sm adm-btn-outline">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Low Stock Alert ──────────────────────────────── -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Low Stock Alert</div>
            <div class="adm-text-muted adm-text-sm">&le; 15 units</div>
        </div>

        <?php if (empty($lowStock)): ?>
            <div class="adm-empty">
                <p>All products are well stocked.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align:center;">Stock</th>
                            <th style="text-align:right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStock as $prod): ?>
                        <tr>
                            <td>
                                <a href="/admin/products/<?= $prod['id'] ?>/edit" class="adm-fw-600"><?= sanitize($prod['name']) ?></a>
                            </td>
                            <td style="text-align:center;">
                                <span class="adm-badge adm-badge-low"><?= (int) $prod['stock_quantity'] ?></span>
                            </td>
                            <td style="text-align:right;"><?= formatPrice((float) $prod['price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Products Never Sold ──────────────────────────── -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Products Never Sold</div>
            <div class="adm-text-muted adm-text-sm">No order history</div>
        </div>

        <?php if (empty($neverSold)): ?>
            <div class="adm-empty">
                <p>All products have been ordered at least once.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align:center;">Stock</th>
                            <th style="text-align:right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($neverSold as $prod): ?>
                        <tr>
                            <td>
                                <a href="/admin/products/<?= $prod['id'] ?>/edit" class="adm-fw-600"><?= sanitize($prod['name']) ?></a>
                            </td>
                            <td style="text-align:center;"><?= (int) $prod['stock_quantity'] ?></td>
                            <td style="text-align:right;"><?= formatPrice((float) $prod['price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
