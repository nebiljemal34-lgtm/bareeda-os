<?php
/**
 * Admin Reports - Sales
 *
 * Variables: $summary, $breakdown, $period, $dateFrom, $dateTo, $label
 */
?>

<!-- ── Page Header ──────────────────────────────────────── -->
<div class="adm-page-header">
    <div>
        <div class="adm-page-title">Sales Report</div>
        <div class="adm-page-subtitle">
            <?= formatDate($dateFrom) ?> &mdash; <?= formatDate($dateTo) ?>
            &nbsp;&middot;&nbsp;
            Grouped by <?= $label ?>
        </div>
    </div>
    <div class="adm-d-flex adm-gap-8">
        <a href="/admin/reports/products" class="adm-btn adm-btn-sm adm-btn-outline">Product Report</a>
        <a href="/admin/reports/customers" class="adm-btn adm-btn-sm adm-btn-outline">Customer Report</a>
    </div>
</div>

<!-- ── Date Range & Period Selector ─────────────────────── -->
<div class="adm-card adm-mb-20" style="padding:14px 18px;">
    <form method="GET" action="/admin/reports/sales" class="adm-filter-bar" style="margin:0;">
        <div>
            <label class="adm-form-label" style="margin-bottom:3px;font-size:11px;">Period</label>
            <select name="period" class="adm-form-select" style="min-width:120px;">
                <option value="daily" <?= $period === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= $period === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>
        </div>
        <div>
            <label class="adm-form-label" style="margin-bottom:3px;font-size:11px;">From</label>
            <input type="date" name="date_from" class="adm-form-input" value="<?= sanitize($dateFrom) ?>" style="width:160px;">
        </div>
        <div>
            <label class="adm-form-label" style="margin-bottom:3px;font-size:11px;">To</label>
            <input type="date" name="date_to" class="adm-form-input" value="<?= sanitize($dateTo) ?>" style="width:160px;">
        </div>
        <div style="display:flex;align-items:flex-end;">
            <button type="submit" class="adm-btn adm-btn-outline adm-btn-sm">Apply</button>
        </div>
    </form>
</div>

<!-- ── Summary Stats ────────────────────────────────────── -->
<div class="adm-stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
    <div class="adm-stat-card">
        <div class="adm-stat-label">Total Sales</div>
        <div class="adm-stat-value"><?= number_format((float) ($summary['total_sales'] ?? 0), 0) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">ETB</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Total Orders</div>
        <div class="adm-stat-value"><?= number_format((int) ($summary['order_count'] ?? 0)) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">non-cancelled</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Average Order Value</div>
        <div class="adm-stat-value"><?= number_format((float) ($summary['avg_order_value'] ?? 0), 0) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">ETB</div>
    </div>
</div>

<!-- ── Sales Chart Placeholder ──────────────────────────── -->
<div class="adm-card adm-mb-24">
    <div class="adm-card-header">
        <div class="adm-card-title">Sales Trend</div>
    </div>
    <div id="salesChart" style="width:100%;height:280px;display:flex;align-items:center;justify-content:center;background:rgba(198,183,155,0.03);border-radius:8px;border:1px dashed var(--adm-border);">
        <div style="text-align:center;color:var(--adm-muted);">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:0.4;margin-bottom:8px;"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            <p style="font-size:13px;margin:0;">Chart visualization placeholder</p>
            <p style="font-size:11px;margin:4px 0 0;opacity:0.7;">Integrate Chart.js or similar library for interactive charts</p>
        </div>
    </div>
</div>

<!-- ── Sales Breakdown Table ────────────────────────────── -->
<div class="adm-card">
    <div class="adm-card-header">
        <div class="adm-card-title">Sales by <?= $label ?></div>
        <div class="adm-text-muted adm-text-sm"><?= count($breakdown) ?> period(s)</div>
    </div>

    <?php if (empty($breakdown)): ?>
        <div class="adm-empty">
            <p>No sales data for the selected period.</p>
        </div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th><?= $label ?></th>
                        <th style="text-align:center;">Orders</th>
                        <th style="text-align:right;">Total Sales</th>
                        <th style="text-align:right;">Avg Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($breakdown as $row): ?>
                    <tr>
                        <td class="adm-fw-600"><?= sanitize($row['period_label']) ?></td>
                        <td style="text-align:center;"><?= number_format((int) $row['order_count']) ?></td>
                        <td style="text-align:right;" class="adm-fw-600"><?= formatPrice((float) $row['total_sales']) ?></td>
                        <td style="text-align:right;"><?= formatPrice((float) $row['avg_order_value']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:rgba(198,183,155,0.05);">
                        <td class="adm-fw-700">Total</td>
                        <td style="text-align:center;" class="adm-fw-700"><?= number_format((int) ($summary['order_count'] ?? 0)) ?></td>
                        <td style="text-align:right;" class="adm-fw-700"><?= formatPrice((float) ($summary['total_sales'] ?? 0)) ?></td>
                        <td style="text-align:right;" class="adm-fw-700"><?= formatPrice((float) ($summary['avg_order_value'] ?? 0)) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
