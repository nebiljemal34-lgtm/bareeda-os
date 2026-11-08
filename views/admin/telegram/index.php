<?php
/**
 * View: Admin Telegram Alert Outbox
 * Variables: $rows, $counts, $status, $page, $pages, $live, $credentials,
 *            $digest_enabled, $digest_hour, $last_digest
 */
$badgeFor = [
    'simulated' => 'adm-badge-admin',
    'pending'   => 'adm-badge-confirmed',
    'sent'      => 'adm-badge-delivered',
    'failed'    => 'adm-badge-cancelled',
];
?>

<section class="page-header" style="padding: 40px 24px 30px;">
    <h1>Telegram Alerts</h1>
    <p class="amharic-subtitle">Every customer and visitor alert, recorded before it is sent</p>
</section>

<div class="container" style="padding: 32px 24px;">
    <div class="adm-card adm-mb-20" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <span class="adm-badge <?= $live ? 'adm-badge-delivered' : 'adm-badge-pending' ?>"><?= $live ? 'LIVE DELIVERY' : 'SIMULATION MODE' ?></span>
        <span class="adm-text-muted adm-text-sm" style="flex:1;min-width:220px;">
            <?= $live
                ? 'Alerts reach your Telegram chat as they happen. Failed sends are retried by the cron worker.'
                : 'Alerts are recorded here only. Nothing leaves the server until live delivery is switched on in Settings.' ?>
        </span>
        <a href="/admin/settings" class="adm-btn adm-btn-sm adm-btn-outline">Configure</a>
    </div>

    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-label">Recorded Alerts</div>
            <div class="adm-stat-value"><?= number_format((int) $counts['total']) ?></div>
            <div class="adm-stat-trend" style="color:var(--adm-muted);">All events, all time</div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-label">Simulated</div>
            <div class="adm-stat-value"><?= number_format((int) $counts['simulated']) ?></div>
            <div class="adm-stat-trend" style="color:var(--adm-warning);">Dry-run, not sent</div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-label">Sent</div>
            <div class="adm-stat-value"><?= number_format((int) $counts['sent']) ?></div>
            <div class="adm-stat-trend" style="color:var(--adm-success);">Delivered to Telegram</div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-label">Failed</div>
            <div class="adm-stat-value"><?= number_format((int) $counts['failed']) ?></div>
            <div class="adm-stat-trend" style="color:var(--adm-danger);">Waiting for retry</div>
        </div>
    </div>

    <div class="adm-card adm-mb-20">
        <div class="adm-card-header"><div class="adm-card-title">Daily Digest and Cron</div></div>
        <ul class="adm-info-list">
            <li><span class="info-label">Daily digest</span><span class="info-value"><?= $digest_enabled ? 'On, at ' . (int) $digest_hour . ':00 server time' : 'Off' ?></span></li>
            <li><span class="info-label">Last digest recorded</span><span class="info-value"><?= $last_digest !== '' ? sanitize($last_digest) : 'Not yet' ?></span></li>
            <li><span class="info-label">Credentials</span><span class="info-value"><?= $credentials ? 'Bot token and chat ID saved' : 'Bot token or chat ID missing' ?></span></li>
        </ul>
        <p class="adm-text-muted adm-text-sm" style="margin-top:14px;">
            The cron worker retries failed alerts and records the digest once per day. On cPanel add a cron job every 15 minutes:
            <code style="word-break:break-all;">/usr/local/bin/php /home/CPANEL_USER/bareeda-website/cron/telegram.php</code>
            (adjust the path), or call <code style="word-break:break-all;"><?= sanitize(APP_URL) ?>/cron/telegram?key=CRON_SECRET_KEY</code>.
        </p>
    </div>

    <div class="flex-between mb-3" style="flex-wrap:wrap;gap:8px;">
        <strong style="color:var(--text);">Alert outbox</strong>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php
            $filters = ['' => 'All', 'simulated' => 'Simulated', 'pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'];
            foreach ($filters as $val => $label):
            ?>
                <a href="/admin/telegram<?= $val !== '' ? '?status=' . $val : '' ?>"
                   class="btn <?= $status === $val ? 'btn-primary' : 'btn-secondary' ?>"
                   style="padding:6px 12px;font-size:0.75rem;"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon" style="color: var(--gold);">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            </div>
            <h2>No Alerts Yet</h2>
            <p>Orders, accounts, contact messages and QR scans will appear here the moment they happen.</p>
        </div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Tries</th>
                        <th>Message</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="adm-text-muted adm-text-sm" style="white-space:nowrap;"><?= formatDate($row['created_at'], 'M j, Y H:i') ?></td>
                        <td style="font-weight:600;"><?= sanitize(TelegramNotifier::eventLabel($row['event_type'])) ?></td>
                        <td><span class="adm-badge <?= $badgeFor[$row['status']] ?? 'adm-badge-pending' ?>"><?= sanitize($row['status']) ?></span></td>
                        <td class="adm-text-muted"><?= (int) $row['attempts'] ?></td>
                        <td style="max-width:420px;">
                            <details>
                                <summary class="adm-btn adm-btn-sm adm-btn-outline" style="cursor:pointer;">View message</summary>
                                <pre style="white-space:pre-wrap;word-break:break-word;font-size:12px;margin:10px 0 4px;padding:12px;background:rgba(0,0,0,0.25);border-radius:8px;max-height:260px;overflow:auto;"><?= sanitize($row['message']) ?></pre>
                                <?php if (!empty($row['last_error'])): ?>
                                    <div class="adm-text-sm" style="color:var(--adm-danger);margin-top:6px;"><?= sanitize($row['last_error']) ?></div>
                                <?php endif; ?>
                            </details>
                        </td>
                        <td>
                            <?php if (in_array($row['status'], ['failed', 'pending'], true)): ?>
                            <form method="POST" action="/admin/telegram/<?= (int) $row['id'] ?>/retry">
                                <?= csrf_field() ?>
                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-gold">Retry</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="adm-pagination">
            <?php $qs = $status !== '' ? '&status=' . $status : ''; ?>
            <?php if ($page > 1): ?>
                <a href="/admin/telegram?page=<?= $page - 1 . $qs ?>">&laquo; Prev</a>
            <?php else: ?>
                <span class="disabled">&laquo; Prev</span>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="/admin/telegram?page=<?= $i . $qs ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="/admin/telegram?page=<?= $page + 1 . $qs ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>