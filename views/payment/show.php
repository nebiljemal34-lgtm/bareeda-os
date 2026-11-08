<?php
$statusLabels = ['pending' => 'Awaiting payment', 'paid' => 'Payment verified', 'failed' => 'Payment failed', 'expired' => 'Payment expired'];
$statusLabel = $statusLabels[$transaction['status']] ?? ucfirst($transaction['status']);
?>

<section class="section">
    <div class="container" style="max-width: 720px;">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(198,183,155,.1);display:grid;place-items:center;color:var(--gold);">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>
            </div>
            <div>
                <h1 style="font-size:1.8rem;margin-bottom:2px;"><?= sanitize($provider['name']) ?></h1>
                <div class="text-muted">Order <?= sanitize($transaction['order_number']) ?></div>
            </div>
        </div>

        <?php if ($simulation): ?>
            <div class="alert alert-warning" style="margin-bottom:20px;">
                <strong>Simulation mode.</strong> No real money is collected and no bank or wallet is contacted.
            </div>
        <?php endif; ?>

        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(198,183,155,.12);border-radius:var(--radius);padding:28px;">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <div class="text-muted" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.6px;">Amount</div>
                    <div style="font-size:2rem;font-weight:800;color:var(--gold);"><?= formatPrice((float) $transaction['amount']) ?></div>
                </div>
                <span class="badge <?= $transaction['status'] === 'paid' ? 'badge-success' : ($transaction['status'] === 'failed' ? 'badge-danger' : 'badge-warning') ?>" id="payment-status"><?= sanitize($statusLabel) ?></span>
            </div>

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(198,183,155,.08);">
                <div class="summary-line"><span>Payment reference</span><strong><?= sanitize($transaction['public_id']) ?></strong></div>
                <?php if ($transaction['provider_reference']): ?>
                    <div class="summary-line"><span>Provider reference</span><strong><?= sanitize($transaction['provider_reference']) ?></strong></div>
                <?php endif; ?>
                <?php if ($transaction['verified_at']): ?>
                    <div class="summary-line"><span>Verified at</span><strong><?= formatDateTime($transaction['verified_at']) ?></strong></div>
                <?php endif; ?>
                <?php if ($transaction['failure_reason']): ?>
                    <div class="summary-line"><span>Reason</span><strong><?= sanitize($transaction['failure_reason']) ?></strong></div>
                <?php endif; ?>
            </div>

            <?php if ($transaction['status'] === 'pending'): ?>
                <p class="text-muted" style="margin:24px 0 16px;">This page checks for verified payment updates automatically. Do not close it while completing your payment.</p>
                <?php if ($simulation): ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <form method="POST" action="/payment/<?= sanitize($transaction['public_id']) ?>/simulate">
                            <?= csrf_field() ?>
                            <input type="hidden" name="outcome" value="success">
                            <button type="submit" class="btn btn-primary" style="width:100%;">Simulate successful payment</button>
                        </form>
                        <form method="POST" action="/payment/<?= sanitize($transaction['public_id']) ?>/simulate">
                            <?= csrf_field() ?>
                            <input type="hidden" name="outcome" value="failed">
                            <button type="submit" class="btn btn-secondary" style="width:100%;">Simulate failed payment</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php elseif ($transaction['status'] === 'paid'): ?>
                <div class="alert alert-success" style="margin:24px 0;">Automatic verification succeeded. Your order payment status is now <strong><?= sanitize($transaction['order_payment_status']) ?></strong>.</div>
                <a href="/order/confirmation/<?= sanitize($transaction['order_number']) ?>" class="btn btn-primary">View order confirmation</a>
            <?php else: ?>
                <div class="alert alert-danger" style="margin:24px 0 16px;">Your order is saved, but it has not been marked as paid. No stock or payment amount was changed by the failed verification.</div>
                <form method="POST" action="/payment/<?= sanitize($transaction['public_id']) ?>/retry">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Create a new payment attempt</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($events)): ?>
            <div style="margin-top:24px;background:rgba(255,255,255,.02);border:1px solid rgba(198,183,155,.08);border-radius:var(--radius);padding:22px;">
                <h3 style="font-size:1rem;margin-bottom:14px;">Verification activity</h3>
                <?php foreach ($events as $event): ?>
                    <div style="display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid rgba(198,183,155,.05);font-size:.84rem;">
                        <span><?= sanitize(str_replace('_', ' ', $event['event_type'])) ?></span>
                        <span class="text-muted"><?= sanitize($event['processing_result']) ?> &middot; <?= formatDateTime($event['created_at']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top:20px;"><a href="/orders/<?= (int) $transaction['order_id'] ?>" style="color:var(--gold);text-decoration:none;">&larr; Back to order</a></div>
    </div>
</section>

<?php if ($transaction['status'] === 'pending'): ?>
<script>
(function () {
    var currentStatus = <?= json_encode($transaction['status']) ?>;
    setInterval(function () {
        fetch(<?= json_encode('/payment/' . $transaction['public_id'] . '/status') ?>, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                if (data && data.status !== currentStatus) { window.location.reload(); }
            })
            .catch(function () {});
    }, 5000);
})();
</script>
<?php endif; ?>
