<div class="adm-d-flex adm-gap-12 adm-mb-24" style="align-items:center;justify-content:space-between;flex-wrap:wrap;">
    <div>
        <div class="adm-text-muted">Visitor, order, and QR activity for the last <?= (int) $summary['days'] ?> days.</div>
        <div class="adm-text-muted adm-text-sm">Visitor counts are cookie-based browser estimates. IP addresses are not stored.</div>
    </div>
    <div class="adm-d-flex adm-gap-12">
        <?php foreach ([7, 30, 90] as $period): ?>
            <a href="/admin/analytics?days=<?= $period ?>" class="adm-btn adm-btn-sm <?= $summary['days'] === $period ? 'adm-btn-gold' : 'adm-btn-outline' ?>"><?= $period ?> days</a>
        <?php endforeach; ?>
    </div>
</div>

<div class="adm-stats-grid">
    <div class="adm-stat-card">
        <div class="adm-stat-label">Unique Visitors</div>
        <div class="adm-stat-value"><?= number_format($summary['visitors']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Across website and QR landing page</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Page Views</div>
        <div class="adm-stat-value"><?= number_format($summary['page_views']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Public storefront pages</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Orders</div>
        <div class="adm-stat-value"><?= number_format($summary['orders']) ?></div>
        <div class="adm-stat-trend up"><?= formatPrice($summary['revenue']) ?> non-cancelled revenue</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Ordering Customers</div>
        <div class="adm-stat-value"><?= number_format($summary['ordering_customers']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Distinct accounts with non-cancelled orders</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">QR Landing Page Opens</div>
        <div class="adm-stat-value" style="color:var(--adm-gold);"><?= number_format($summary['qr_scans']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Recorded from the canonical QR page</div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-label">Unique QR Visitors</div>
        <div class="adm-stat-value"><?= number_format($summary['qr_visitors']) ?></div>
        <div class="adm-stat-trend" style="color:var(--adm-muted);">Distinct browser cookies</div>
    </div>
</div>

<div class="adm-card adm-mb-24">
    <div class="adm-card-header">
        <div>
            <div class="adm-card-title">QR Landing Page</div>
            <div class="adm-text-muted adm-text-sm">Keep this single URL unchanged on all printed QR codes.</div>
        </div>
        <a href="/links" target="_blank" rel="noopener" class="adm-btn adm-btn-sm adm-btn-outline">Open Page</a>
    </div>
    <code style="display:block;padding:14px;border-radius:8px;background:rgba(255,255,255,0.03);color:var(--adm-gold);overflow-wrap:anywhere;">https://www.bareeda-injera.com/links</code>
</div>

<div class="adm-grid-2">
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Top Pages</div>
        </div>
        <?php if (empty($topPages)): ?>
            <div class="adm-empty"><p>No public page views recorded in this period.</p></div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>Page</th><th>Views</th><th>Visitors</th></tr></thead>
                    <tbody>
                    <?php foreach ($topPages as $page): ?>
                        <tr>
                            <td><a href="<?= sanitize($page['path']) ?>" target="_blank" rel="noopener"><?= sanitize($page['path']) ?></a></td>
                            <td class="adm-fw-600"><?= number_format((int) $page['views']) ?></td>
                            <td><?= number_format((int) $page['visitors']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title">Daily Activity</div>
            <span class="adm-text-muted adm-text-sm">Most recent 14 days</span>
        </div>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead><tr><th>Date</th><th>Visitors</th><th>Views</th><th>QR</th><th>Orders</th></tr></thead>
                <tbody>
                <?php foreach (array_reverse(array_slice($daily, -14)) as $day): ?>
                    <tr>
                        <td><?= formatDate($day['day'], 'M j') ?></td>
                        <td class="adm-fw-600"><?= number_format($day['visitors']) ?></td>
                        <td><?= number_format($day['page_views']) ?></td>
                        <td class="adm-text-gold"><?= number_format($day['qr_scans']) ?></td>
                        <td><?= number_format($day['orders']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
