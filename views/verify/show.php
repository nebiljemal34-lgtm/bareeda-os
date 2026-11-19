<?php
/**
 * View: Public letter verification
 *
 * Variables: $verify_state (null for the entry form), $verify (display fields),
 *            $verify_token (submitted code, if any)
 */

$companyName = 'Bareeda Injera and Foods PLC';
$companyAm   = 'በሬዳ እንጀራ እና ምግቦች ኃላ. የተ. የግ. ማህበር.';
$tagline     = 'The Way Injera Should Taste!';
$contactEmail = setting('contact_email', APP_EMAIL);
$businessCity = setting('business_city', 'Dire Dawa');

$copy = [
    'active' => [
        'badge' => 'Verified',
        'title' => 'This letter is genuine',
        'lede'  => 'This reference is held in the Bareeda letter register, and the record matches the fingerprint recorded when it was issued.',
    ],
    'revoked' => [
        'badge' => 'Cancelled',
        'title' => 'This letter was withdrawn',
        'lede'  => 'Bareeda cancelled this letter. Please do not rely on it — ask us for the current reference.',
    ],
    'altered' => [
        'badge' => 'Altered',
        'title' => 'This record changed after issue',
        'lede'  => 'The stored record no longer matches the fingerprint taken when the letter was issued. Contact us before acting on this document.',
    ],
    'unknown' => [
        'badge' => 'Not recognised',
        'title' => 'We can’t match this code',
        'lede'  => 'No letter in the Bareeda register matches this code. Check the code from your letter, or contact us so we can help.',
    ],
    'unavailable' => [
        'badge' => 'Temporarily unavailable',
        'title' => 'We couldn’t reach the letter register',
        'lede'  => 'Please try again in a moment, or contact us if it keeps happening.',
    ],
];
?>
<section class="verify-page">
    <div class="container verify-shell">

        <?php if ($verify_state === null): ?>

            <article class="verify-card">
                <header class="verify-head">
                    <img class="verify-logo" src="/assets/images/logo-b-transparent.png" alt="">
                    <div class="verify-identity">
                        <p class="verify-company"><?= sanitize($companyName) ?></p>
                        <p class="verify-company-am" lang="am"><?= sanitize($companyAm) ?></p>
                    </div>
                    <p class="verify-tagline"><?= sanitize($tagline) ?></p>
                </header>

                <div class="verify-body">
                    <p class="verify-eyebrow">Letter register</p>
                    <h1 class="verify-title verify-title--entry">Verify a Bareeda letter</h1>
                    <p class="verify-lede">
                        Letters issued by Bareeda can be checked against our letter register.
                        Scan the QR code on the letter, or enter its code below.
                    </p>

                    <form class="verify-form" method="get" action="/verify">
                        <label class="verify-label" for="token">Letter code</label>
                        <div class="verify-form-row">
                            <input
                                class="verify-input"
                                id="token"
                                name="token"
                                type="text"
                                inputmode="text"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                maxlength="44"
                                placeholder="Enter the code from your letter"
                                required
                            >
                            <button class="btn btn-primary verify-submit" type="submit">Verify</button>
                        </div>
                        <p class="verify-hint">
                            The code is 20–44 characters and is printed alongside the QR code.
                        </p>
                    </form>

                    <p class="verify-meta">
                        <?= sanitize($companyName) ?> · <?= sanitize($businessCity) ?>, Ethiopia
                    </p>
                </div>
            </article>

        <?php else: ?>

            <?php
            $state = isset($copy[$verify_state]) ? $verify_state : 'unknown';
            $text  = $copy[$state];
            ?>

            <article class="verify-card">
                <header class="verify-head">
                    <img class="verify-logo" src="/assets/images/logo-b-transparent.png" alt="">
                    <div class="verify-identity">
                        <p class="verify-company"><?= sanitize($companyName) ?></p>
                        <p class="verify-company-am" lang="am"><?= sanitize($companyAm) ?></p>
                    </div>
                    <p class="verify-tagline"><?= sanitize($tagline) ?></p>
                </header>

                <div class="verify-body">
                    <span class="verify-badge verify-badge--<?= sanitize($state) ?>">
                        <span class="verify-dot" aria-hidden="true"></span>
                        <?= sanitize($text['badge']) ?>
                    </span>

                    <h1 class="verify-title"><?= sanitize($text['title']) ?></h1>
                    <p class="verify-lede"><?= sanitize($text['lede']) ?></p>

                    <?php if ($state === 'active' || $state === 'revoked' || $state === 'altered'): ?>
                        <dl class="verify-grid">
                            <?php if ($verify['reference'] !== ''): ?>
                                <dt>Reference</dt>
                                <dd><?= sanitize($verify['reference']) ?></dd>
                            <?php endif; ?>

                            <?php if (($verify['checkCode'] ?? '') !== ''): ?>
                                <dt>Check code</dt>
                                <dd class="verify-checkcode"><?= sanitize($verify['checkCode']) ?></dd>
                            <?php endif; ?>

                            <?php if (!empty($verify['sealed'])): ?>
                                <dt>Security seal</dt>
                                <dd>
                                    Verified cryptographically
                                    <?php if (($verify['keyId'] ?? '') !== ''): ?>
                                        (key <?= sanitize($verify['keyId']) ?>)
                                    <?php endif; ?>
                                    <span class="verify-seal" aria-hidden="true">✓</span>
                                </dd>
                            <?php endif; ?>

                            <?php if ($verify['letter_date'] !== ''): ?>
                                <dt>Letter date</dt>
                                <dd><?= sanitize($verify['letter_date']) ?></dd>
                            <?php endif; ?>

                            <?php if ($verify['subject'] !== ''): ?>
                                <dt>Subject</dt>
                                <dd><?= sanitize($verify['subject']) ?></dd>
                            <?php endif; ?>

                            <?php if ($verify['issued_to'] !== ''): ?>
                                <dt>Issued to</dt>
                                <dd><?= sanitize($verify['issued_to']) ?></dd>
                            <?php endif; ?>

                            <?php if ($verify['signed_by'] !== ''): ?>
                                <dt>Signed by</dt>
                                <dd><?= sanitize($verify['signed_by']) ?></dd>
                            <?php endif; ?>

                            <?php if ($verify['recorded'] !== ''): ?>
                                <dt>Recorded</dt>
                                <dd><?= sanitize($verify['recorded']) ?></dd>
                            <?php endif; ?>

                            <?php if ($state === 'revoked' && $verify['cancelled'] !== ''): ?>
                                <dt>Cancelled</dt>
                                <dd>
                                    <?= sanitize($verify['cancelled']) ?>
                                    <?php if ($verify['revoked_reason'] !== ''): ?>
                                        <span class="verify-reason">— <?= sanitize($verify['revoked_reason']) ?></span>
                                    <?php endif; ?>
                                </dd>
                            <?php endif; ?>
                        </dl>

                        <?php if (!empty($verify['withheld'])): ?>
                            <section class="verify-encl">
                                <h2>Contents</h2>
                                <p>This letter is genuine, but its contents are confidential.
                                The details are withheld from the public page on purpose —
                                contact us to confirm specifics.</p>
                            </section>
                        <?php elseif (!empty($verify['attachments'])): ?>
                            <section class="verify-encl">
                                <h2>Documents enclosed</h2>
                                <ul>
                                    <?php foreach ($verify['attachments'] as $attachment): ?>
                                        <?php
                                        $label  = (string) ($attachment['label'] ?? '');
                                        $number = (string) ($attachment['number'] ?? '');
                                        ?>
                                        <li>
                                            <span class="verify-encl-label"><?= sanitize($label) ?></span>
                                            <?php if ($number !== '' && strpos($label, $number) === false): ?>
                                                <span class="verify-encl-number">— <?= sanitize($number) ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($state === 'active' || $state === 'revoked' || $state === 'altered'): ?>
                        <p class="verify-note">
                            This page confirms what is recorded in our letter register. It does not
                            replace the signed paper in your hands: compare the reference above with
                            the letter you received. Questions? Write to
                            <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a>.
                        </p>
                    <?php else: ?>
                        <p class="verify-note">
                            If you believe this is wrong, write to
                            <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a>
                            and tell us the code from your letter.
                        </p>
                    <?php endif; ?>

                    <div class="verify-actions">
                        <?php if ($state === 'unavailable'): ?>
                            <a class="btn btn-primary" href="/verify/l/<?= rawurlencode($verify_token) ?>">Try again</a>
                            <a class="verify-link" href="/">Back to bareeda-injera.com</a>
                        <?php elseif ($state === 'unknown'): ?>
                            <a class="btn btn-primary" href="/verify">Try another code</a>
                            <a class="verify-link" href="/">Back to bareeda-injera.com</a>
                        <?php else: ?>
                            <a class="btn btn-primary" href="/">Back to bareeda-injera.com</a>
                            <a class="verify-link" href="/verify">Verify another letter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

        <?php endif; ?>

    </div>
</section>
