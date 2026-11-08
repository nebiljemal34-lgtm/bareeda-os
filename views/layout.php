<?php
/**
 * Bareeda Injera - Master Layout
 *
 * Variables available: $page_title, $content_view, $cart_count
 */

$_pageTitle  = $page_title ?? 'Bareeda Injera';
$_cartCount  = $cart_count ?? 0;
$_isLoggedIn = isLoggedIn();
$_user       = $_isLoggedIn ? $GLOBALS['auth']->user() : null;
$_isAdmin    = isAdmin();
$_flashHtml  = display_flash_messages();
$_siteName   = setting('site_name', APP_NAME);
$_announcement = trim(setting('announcement'));
$_contactPhone = setting('contact_phone', APP_PHONE);
$_contactEmail = setting('contact_email', APP_EMAIL);
$_businessCity = setting('business_city', 'Dire Dawa');
$_businessAddress = setting('business_address', $_businessCity . ', Ethiopia');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_pageTitle) ?> | <?= sanitize($_siteName) ?></title>
    <meta name="description" content="Fresh teff injera delivered daily in <?= sanitize($_businessCity) ?>. 100% natural, traditionally made.">
    <link rel="icon" type="image/png" href="/assets/images/logo-front.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Sans+Ethiopic:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <style>
        /* ── Navigation ─────────────────────────────── */
        .navbar {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(11,26,43,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(198,183,155,0.1);
            padding: 0 24px;
        }
        .navbar-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            height: 72px;
        }
        .navbar-brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: var(--gold);
        }
        .navbar-brand img { height: 48px; width: auto; }
        .navbar-brand-text {
            display: flex; flex-direction: column; gap: 3px; line-height: 1.1;
        }
        .navbar-brand-am {
            font-family: 'Noto Sans Ethiopic', sans-serif;
            font-size: 1.1rem; font-weight: 700; color: var(--gold);
        }
        .navbar-brand-en {
            font-size: 0.72rem; font-weight: 700; color: var(--text);
            letter-spacing: 1.6px; text-transform: uppercase;
        }
        .navbar-links {
            display: flex; align-items: center; gap: 8px;
            list-style: none;
        }
        .navbar-links a {
            color: var(--text); text-decoration: none;
            padding: 8px 16px; border-radius: 8px;
            font-size: 0.9rem; font-weight: 600;
            transition: all 0.2s;
        }
        .navbar-links a:hover, .navbar-links a.active {
            color: var(--gold); background: rgba(198,183,155,0.08);
        }
        .nav-cart {
            position: relative; display: flex; align-items: center;
            color: var(--gold); text-decoration: none;
            padding: 8px 14px; border-radius: 8px;
            font-weight: 600; font-size: 0.9rem;
            transition: all 0.2s;
        }
        .nav-cart:hover { background: rgba(198,183,155,0.08); }
        .nav-cart svg { width: 22px; height: 22px; }
        .cart-badge {
            position: absolute; top: 2px; right: 4px;
            background: var(--gold); color: var(--navy);
            font-size: 0.65rem; font-weight: 800;
            width: 18px; height: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .cart-badge:empty { display: none; }
        .nav-auth { display: flex; align-items: center; gap: 8px; }
        .mobile-toggle {
            display: none; background: none; border: none;
            color: var(--gold); cursor: pointer; padding: 8px;
        }
        .mobile-toggle svg { width: 26px; height: 26px; }

        @media (max-width: 768px) {
            .mobile-toggle { display: block; }
            .navbar-links, .nav-auth { display: none; }
            .navbar-links.open, .nav-auth.open {
                display: flex; flex-direction: column;
                position: absolute; top: 72px; left: 0; right: 0;
                background: var(--navy);
                border-bottom: 1px solid rgba(198,183,155,0.1);
                padding: 16px 24px; gap: 4px;
            }
            .nav-auth.open {
                top: auto; border-top: 1px solid rgba(198,183,155,0.1);
                padding-top: 8px;
            }
        }

        /* ── Hero Section ───────────────────────────── */
        .hero {
            position: relative; min-height: 80vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 80px 24px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 50%, var(--navy-dark) 100%);
            overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(198,183,155,0.06) 0%, transparent 60%),
                        radial-gradient(ellipse at 70% 30%, rgba(198,183,155,0.04) 0%, transparent 50%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 700px; }
        .hero h1 {
            font-size: 3.2rem; line-height: 1.15;
            color: var(--text); margin-bottom: 16px;
        }
        .hero h1 em {
            font-style: normal; color: var(--gold);
            display: block;
        }
        .hero .subtitle {
            font-size: 1.15rem; color: var(--text-muted);
            margin-bottom: 8px;
        }
        .hero .subtitle-am {
            font-size: 1.15rem; color: var(--gold); font-weight: 700;
            opacity: 1; margin-bottom: 32px;
        }
        .hero-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

        @media (max-width: 768px) {
            .hero { min-height: 60vh; padding: 60px 20px; }
            .hero h1 { font-size: 2.2rem; }
        }

        /* ── Product Card ───────────────────────────── */
        .product-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(198,183,155,0.08);
            border-radius: var(--radius);
            overflow: hidden; transition: all 0.3s;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(198,183,155,0.2);
        }
        .product-card-img {
            width: 100%; aspect-ratio: 2/1;
            object-fit: cover; display: block;
            background: rgba(198,183,155,0.05);
        }
        .product-card-img-placeholder {
            width: 100%; aspect-ratio: 4/3;
            background: linear-gradient(135deg, rgba(198,183,155,0.08), rgba(198,183,155,0.03));
            display: flex; align-items: center; justify-content: center;
            color: var(--gold); font-size: 2.5rem;
        }
        .product-card-body { padding: 20px; }
        .product-card-name {
            font-size: 1.05rem; font-weight: 700;
            color: var(--text); margin-bottom: 4px;
        }
        .product-card-name-am {
            font-size: 0.85rem; color: var(--gold);
            opacity: 0.6; margin-bottom: 12px;
        }
        .product-card-price {
            font-size: 1.2rem; font-weight: 800;
            color: var(--gold); margin-bottom: 16px;
        }
        .product-card-actions { display: flex; gap: 8px; }
        .product-card-actions .btn { flex: 1; padding: 10px 16px; font-size: 0.82rem; }

        /* ── Pack Size / Per-Piece Price ────────────── */
        .pack-meta {
            display: flex; align-items: center; gap: 10px;
            flex-wrap: wrap; margin-bottom: 12px;
        }
        .pack-chip {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.78rem; font-weight: 600; color: var(--gold);
            background: rgba(198,183,155,0.1);
            border: 1px solid rgba(198,183,155,0.18);
            padding: 4px 10px; border-radius: 999px;
            white-space: nowrap;
        }
        .pack-chip svg { width: 14px; height: 14px; flex-shrink: 0; }
        .pack-each {
            font-size: 0.78rem; color: var(--text-muted);
            font-weight: 600; white-space: nowrap;
        }
        .pack-each strong { color: var(--text); }
        .pack-meta-detail { gap: 12px; margin: 4px 0 20px; }
        .pack-tile {
            flex: 1; min-width: 130px;
            background: rgba(198,183,155,0.06);
            border: 1px solid rgba(198,183,155,0.14);
            border-radius: 12px; padding: 14px 16px;
            display: flex; flex-direction: column; gap: 4px;
        }
        .pack-tile-value { font-size: 1.25rem; font-weight: 800; color: var(--gold); }
        .pack-tile-label {
            font-size: 0.72rem; text-transform: uppercase;
            letter-spacing: 0.6px; color: var(--text-muted);
        }

        /* ── Section Styles ─────────────────────────── */
        .section-light {
            background: var(--navy-light);
        }
        .section-dark {
            background: var(--navy-dark);
        }

        /* ── Feature Cards ──────────────────────────── */
        .feature-card {
            text-align: center; padding: 40px 24px;
        }
        .feature-icon {
            width: 64px; height: 64px; margin: 0 auto 20px;
            background: rgba(198,183,155,0.1); border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }
        .feature-card h3 {
            font-size: 1.15rem; margin-bottom: 12px; color: var(--gold);
        }
        .feature-card p { color: var(--text-muted); font-size: 0.9rem; }

        /* ── Testimonials ───────────────────────────── */
        .testimonial-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(198,183,155,0.08);
            border-radius: var(--radius); padding: 32px;
        }
        .testimonial-stars { color: var(--gold); margin-bottom: 16px; font-size: 1.1rem; }
        .testimonial-text { color: var(--text); font-style: italic; margin-bottom: 20px; line-height: 1.7; }
        .testimonial-author { font-weight: 700; color: var(--gold); font-size: 0.9rem; }
        .testimonial-location { color: var(--text-muted); font-size: 0.8rem; }

        /* ── Newsletter ─────────────────────────────── */
        .newsletter {
            text-align: center; padding: 80px 24px;
            background: linear-gradient(135deg, rgba(198,183,155,0.06), rgba(198,183,155,0.02));
            border-top: 1px solid rgba(198,183,155,0.08);
            border-bottom: 1px solid rgba(198,183,155,0.08);
        }
        .newsletter h2 { color: var(--gold); margin-bottom: 12px; }
        .newsletter p { color: var(--text-muted); margin-bottom: 32px; max-width: 500px; margin-left: auto; margin-right: auto; }
        .newsletter-form {
            display: flex; gap: 12px; max-width: 460px; margin: 0 auto;
        }
        .newsletter-form input {
            flex: 1; padding: 14px 20px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(198,183,155,0.15);
            border-radius: 50px; color: var(--text); font-size: 0.95rem;
        }
        .newsletter-form input:focus { outline: none; border-color: var(--gold); }
        @media (max-width: 480px) {
            .newsletter-form { flex-direction: column; }
        }

        /* ── Footer ─────────────────────────────────── */
        .footer {
            background: var(--navy-dark);
            border-top: 1px solid rgba(198,183,155,0.08);
            padding: 60px 24px 30px;
        }
        .footer-inner {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
        }
        .footer-brand img { height: 48px; margin-bottom: 16px; }
        .footer-brand p { color: var(--text-muted); font-size: 0.85rem; line-height: 1.7; }
        .footer-brand .amharic { color: var(--gold); opacity: 0.6; font-size: 0.9rem; margin-top: 8px; }
        .footer h4 {
            color: var(--gold); font-size: 0.85rem;
            text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 16px;
        }
        .footer ul { list-style: none; }
        .footer ul li { margin-bottom: 10px; }
        .footer ul a {
            color: var(--text-muted); text-decoration: none;
            font-size: 0.88rem; transition: color 0.2s;
        }
        .footer ul a:hover { color: var(--gold); }
        .footer-contact p {
            color: var(--text-muted); font-size: 0.88rem;
            margin-bottom: 8px;
        }
        .footer-social {
            display: flex; gap: 12px; margin-top: 16px;
        }
        .footer-social a {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(198,183,155,0.08);
            display: flex; align-items: center; justify-content: center;
            color: var(--gold); text-decoration: none;
            transition: all 0.2s; font-size: 1.1rem;
        }
        .footer-social a:hover {
            background: var(--gold); color: var(--navy);
        }
        .footer-bottom {
            max-width: 1200px; margin: 40px auto 0;
            padding-top: 24px;
            border-top: 1px solid rgba(198,183,155,0.06);
            text-align: center; color: var(--text-muted);
            font-size: 0.8rem;
        }
        @media (max-width: 768px) {
            .footer-inner { grid-template-columns: 1fr; gap: 32px; }
        }

        /* ── Page Header ────────────────────────────── */
        .page-header {
            padding: 60px 24px 40px; text-align: center;
            background: linear-gradient(180deg, var(--navy-light), var(--navy));
        }
        .page-header h1 { color: var(--gold); margin-bottom: 8px; }
        .page-header .amharic-subtitle { color: var(--text-muted); font-size: 1rem; }

        /* ── Pagination ─────────────────────────────── */
        .pagination {
            display: flex; gap: 8px; justify-content: center;
            padding: 40px 0; flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 10px 16px; border-radius: 8px;
            text-decoration: none; font-weight: 600;
            font-size: 0.85rem; transition: all 0.2s;
            background: rgba(198,183,155,0.06);
            color: var(--text-muted);
            border: 1px solid rgba(198,183,155,0.1);
        }
        .pagination a:hover { background: rgba(198,183,155,0.12); color: var(--gold); }
        .pagination .active {
            background: var(--gold); color: var(--navy);
            border-color: var(--gold);
        }

        /* ── Status Badges ──────────────────────────── */
        .status-pending { background: rgba(255,152,0,0.15); color: var(--warning); }
        .status-confirmed { background: rgba(33,150,243,0.15); color: #2196f3; }
        .status-preparing { background: rgba(156,39,176,0.15); color: #ce93d8; }
        .status-out_for_delivery { background: rgba(0,150,136,0.15); color: #4db6ac; }
        .status-delivered { background: rgba(76,175,80,0.15); color: var(--success); }
        .status-cancelled { background: rgba(244,67,54,0.15); color: var(--danger); }
        .status-active { background: rgba(76,175,80,0.15); color: var(--success); }
        .status-paused { background: rgba(255,152,0,0.15); color: var(--warning); }

        /* ── Tables ─────────────────────────────────── */
        .data-table {
            width: 100%; border-collapse: collapse;
        }
        .data-table th {
            text-align: left; padding: 14px 16px;
            font-size: 0.78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(198,183,155,0.1);
        }
        .data-table td {
            padding: 16px; vertical-align: middle;
            border-bottom: 1px solid rgba(198,183,155,0.05);
            font-size: 0.9rem;
        }
        .data-table tr:hover td { background: rgba(198,183,155,0.03); }

        /* ── Sidebar Layout ─────────────────────────── */
        .shop-layout {
            display: grid; grid-template-columns: 240px 1fr;
            gap: 40px; max-width: 1200px; margin: 0 auto;
            padding: 40px 24px;
        }
        .shop-sidebar { position: sticky; top: 90px; align-self: start; }
        .shop-sidebar h3 {
            font-size: 0.85rem; text-transform: uppercase;
            letter-spacing: 1px; color: var(--gold);
            margin-bottom: 16px;
        }
        .shop-sidebar ul { list-style: none; }
        .shop-sidebar li { margin-bottom: 4px; }
        .shop-sidebar a {
            display: block; padding: 10px 14px;
            color: var(--text-muted); text-decoration: none;
            border-radius: 8px; font-size: 0.9rem;
            transition: all 0.2s;
        }
        .shop-sidebar a:hover, .shop-sidebar a.active {
            background: rgba(198,183,155,0.08); color: var(--gold);
        }
        @media (max-width: 768px) {
            .shop-layout { grid-template-columns: 1fr; }
            .shop-sidebar { position: static; }
        }

        /* ── Account Layout ─────────────────────────── */
        .account-layout {
            display: grid; grid-template-columns: 240px 1fr;
            gap: 40px; max-width: 1200px; margin: 0 auto;
            padding: 40px 24px;
        }
        .account-sidebar h3 {
            font-size: 0.85rem; text-transform: uppercase;
            letter-spacing: 1px; color: var(--gold);
            margin-bottom: 16px;
        }
        .account-sidebar ul { list-style: none; }
        .account-sidebar li { margin-bottom: 4px; }
        .account-sidebar a {
            display: block; padding: 10px 14px;
            color: var(--text-muted); text-decoration: none;
            border-radius: 8px; font-size: 0.9rem;
            transition: all 0.2s;
        }
        .account-sidebar a:hover, .account-sidebar a.active {
            background: rgba(198,183,155,0.08); color: var(--gold);
        }
        @media (max-width: 768px) {
            .account-layout { grid-template-columns: 1fr; }
        }

        /* ── Stat Cards ─────────────────────────────── */
        .stat-card {
            background: rgba(198,183,155,0.06);
            border: 1px solid rgba(198,183,155,0.1);
            border-radius: var(--radius); padding: 24px;
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 2rem; font-weight: 800; color: var(--gold);
        }
        .stat-card .stat-label {
            font-size: 0.8rem; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* ── Form Layout ────────────────────────────── */
        .form-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(198,183,155,0.08);
            border-radius: var(--radius); padding: 32px;
            max-width: 560px; margin: 0 auto;
        }
        .form-card h2 { color: var(--gold); margin-bottom: 24px; text-align: center; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
        .form-check {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px;
        }
        .form-check input[type="checkbox"] {
            width: 18px; height: 18px; accent-color: var(--gold);
        }
        .form-check label { font-size: 0.9rem; color: var(--text-muted); cursor: pointer; }
        .form-footer { text-align: center; margin-top: 24px; }
        .form-footer a { color: var(--gold); text-decoration: none; font-size: 0.9rem; }
        .form-footer a:hover { text-decoration: underline; }

        /* ── Checkout ───────────────────────────────── */
        .checkout-layout {
            display: grid; grid-template-columns: 1fr 380px;
            gap: 40px; max-width: 1200px; margin: 0 auto;
            padding: 40px 24px;
        }
        .checkout-step {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(198,183,155,0.08);
            border-radius: var(--radius); padding: 32px;
            margin-bottom: 24px;
        }
        .checkout-step h3 {
            color: var(--gold); margin-bottom: 24px;
            display: flex; align-items: center; gap: 12px;
        }
        .step-number {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--gold); color: var(--navy);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.85rem;
        }
        .order-summary-box {
            position: sticky; top: 90px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(198,183,155,0.08);
            border-radius: var(--radius); padding: 32px;
            align-self: start;
        }
        .order-summary-box h3 { color: var(--gold); margin-bottom: 20px; }
        .summary-line {
            display: flex; justify-content: space-between;
            padding: 10px 0; font-size: 0.9rem;
            color: var(--text-muted);
        }
        .summary-line.total {
            border-top: 1px solid rgba(198,183,155,0.15);
            margin-top: 8px; padding-top: 16px;
            font-size: 1.1rem; font-weight: 800;
            color: var(--gold);
        }
        .payment-option {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 16px; border-radius: 10px;
            border: 1px solid rgba(198,183,155,0.1);
            margin-bottom: 12px; cursor: pointer;
            transition: all 0.2s;
        }
        .payment-option:hover, .payment-option.selected {
            border-color: var(--gold);
            background: rgba(198,183,155,0.05);
        }
        .payment-option input { margin-top: 3px; accent-color: var(--gold); }
        .payment-option-label { font-weight: 700; color: var(--text); }
        .payment-option-desc { font-size: 0.82rem; color: var(--text-muted); margin-top: 4px; }
        @media (max-width: 768px) {
            .checkout-layout { grid-template-columns: 1fr; }
            .order-summary-box { position: static; }
        }

        /* ── Cart ───────────────────────────────────── */
        .cart-table-wrap { overflow-x: auto; }
        .cart-item-img {
            width: 64px; height: 64px; border-radius: 8px;
            object-fit: contain; background: rgba(198,183,155,0.05);
        }
        .qty-control {
            display: flex; align-items: center; gap: 0;
            border: 1px solid rgba(198,183,155,0.15); border-radius: 8px;
            overflow: hidden; width: fit-content;
        }
        .qty-control button {
            width: 36px; height: 36px; border: none;
            background: rgba(198,183,155,0.08); color: var(--gold);
            cursor: pointer; font-size: 1.1rem; font-weight: 700;
            transition: background 0.2s;
        }
        .qty-control button:hover { background: rgba(198,183,155,0.15); }
        .qty-control input {
            width: 48px; height: 36px; border: none;
            background: transparent; color: var(--text);
            text-align: center; font-weight: 700; font-size: 0.9rem;
        }
        .qty-control input:focus { outline: none; }
        .cart-summary {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(198,183,155,0.08);
            border-radius: var(--radius); padding: 32px;
            max-width: 400px; margin-left: auto;
        }

        /* ── Empty State ────────────────────────────── */
        .empty-state {
            text-align: center; padding: 80px 24px;
        }
        .empty-state-icon { font-size: 4rem; margin-bottom: 20px; opacity: 0.3; }
        .empty-state h2 { color: var(--text-muted); margin-bottom: 12px; }
        .empty-state p { color: var(--text-muted); opacity: 0.7; margin-bottom: 24px; }

        /* ── Success Page ───────────────────────────── */
        .success-icon {
            width: 80px; height: 80px; border-radius: 50%;
            background: rgba(76,175,80,0.15); color: var(--success);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; margin: 0 auto 24px;
        }

        /* ── About Page ─────────────────────────────── */
        .about-hero {
            padding: 80px 24px; text-align: center;
            background: linear-gradient(135deg, var(--navy-light), var(--navy));
        }
        .about-hero h1 { color: var(--gold); font-size: 2.5rem; margin-bottom: 16px; }
        .about-hero p { color: var(--text-muted); max-width: 600px; margin: 0 auto; font-size: 1.05rem; line-height: 1.8; }
        .value-card {
            text-align: center; padding: 32px 24px;
        }
        .value-icon {
            font-size: 2.5rem; margin-bottom: 16px;
        }
        .value-card h3 { color: var(--gold); margin-bottom: 12px; }
        .value-card p { color: var(--text-muted); font-size: 0.9rem; }

        /* ── Contact ────────────────────────────────── */
        .contact-info-card {
            display: flex; gap: 16px; align-items: flex-start;
            padding: 20px; border-radius: 10px;
            background: rgba(198,183,155,0.04);
            border: 1px solid rgba(198,183,155,0.08);
            margin-bottom: 12px;
        }
        .contact-info-icon {
            width: 44px; height: 44px; border-radius: 10px;
            background: rgba(198,183,155,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .contact-info-text h4 { font-size: 0.9rem; color: var(--text); margin-bottom: 4px; }
        .contact-info-text p { color: var(--text-muted); font-size: 0.85rem; }

        /* ── Product Detail ─────────────────────────── */
        .product-detail {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 48px; max-width: 1200px; margin: 0 auto;
            padding: 40px 24px;
        }
        .product-detail-img {
            width: 100%; aspect-ratio: 2/1; border-radius: var(--radius);
            object-fit: cover; background: rgba(198,183,155,0.05);
        }
        .product-detail-img-placeholder {
            width: 100%; aspect-ratio: 1; border-radius: var(--radius);
            background: linear-gradient(135deg, rgba(198,183,155,0.08), rgba(198,183,155,0.03));
            display: flex; align-items: center; justify-content: center;
            font-size: 5rem; color: var(--gold); opacity: 0.3;
        }
        .product-detail-info h1 { font-size: 2rem; margin-bottom: 8px; }
        .product-detail-info .name-am { color: var(--gold); opacity: 0.6; font-size: 1.1rem; margin-bottom: 16px; }
        .product-detail-info .price { font-size: 1.8rem; font-weight: 800; color: var(--gold); margin-bottom: 20px; }
        .product-detail-info .description { color: var(--text-muted); line-height: 1.8; margin-bottom: 24px; }
        .stock-status { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; margin-bottom: 24px; }
        .stock-dot { width: 8px; height: 8px; border-radius: 50%; }
        .stock-in { background: var(--success); }
        .stock-low { background: var(--warning); }
        .stock-out { background: var(--danger); }
        @media (max-width: 768px) {
            .product-detail { grid-template-columns: 1fr; gap: 24px; }
        }

        /* ── Utility ────────────────────────────────── */
        .text-center { text-align: center; }
        .text-gold { color: var(--gold); }
        .text-muted { color: var(--text-muted); }
        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .mt-4 { margin-top: 32px; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .mb-4 { margin-bottom: 32px; }
        .flex { display: flex; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .gap-1 { gap: 8px; }
        .gap-2 { gap: 16px; }
        .inline-form { display: inline; }
    </style>
</head>
<body>

<?php if ($_announcement !== ''): ?>
<div role="status" style="background:var(--gold);color:var(--navy);padding:9px 24px;text-align:center;font-size:.86rem;font-weight:600;"><?= sanitize($_announcement) ?></div>
<?php endif; ?>

<!-- ═══ Navigation ═══ -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="navbar-brand">
            <img src="/assets/images/logo-b-transparent.png" alt="Bareeda Injera Logo">
            <span class="navbar-brand-text">
                <span class="navbar-brand-am">በሬዳ እንጀራ</span>
                <span class="navbar-brand-en">Bareeda Injera</span>
            </span>
        </a>

        <button class="mobile-toggle" onclick="document.querySelector('.navbar-links').classList.toggle('open');document.querySelector('.nav-auth').classList.toggle('open')" aria-label="Menu">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <ul class="navbar-links">
            <li><a href="/" class="<?= ($_pageTitle ?? '') === 'Home' ? 'active' : '' ?>">Home</a></li>
            <li><a href="/products" class="<?= in_array($_pageTitle ?? '', ['Our Products', 'Search']) ? 'active' : '' ?>">Shop</a></li>
            <li><a href="/about" class="<?= ($_pageTitle ?? '') === 'About Us' ? 'active' : '' ?>">About</a></li>
            <li><a href="/contact" class="<?= ($_pageTitle ?? '') === 'Contact Us' ? 'active' : '' ?>">Contact</a></li>
        </ul>

        <div class="nav-auth">
            <a href="/cart" class="nav-cart" title="Shopping Cart">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                <span class="cart-badge"><?= $_cartCount > 0 ? $_cartCount : '' ?></span>
            </a>

            <?php if ($_isLoggedIn): ?>
                <a href="/account" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.82rem;">
                    <?= htmlspecialchars($_user['first_name'] ?? 'Account') ?>
                </a>
                <?php if ($_isAdmin): ?>
                    <a href="/admin" style="color: var(--gold); font-size: 0.82rem; padding: 8px 12px;">Admin</a>
                <?php endif; ?>
                <a href="/logout" style="color: var(--text-muted); font-size: 0.82rem; padding: 8px 12px;">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ═══ Flash Messages ═══ -->
<?php if ($_flashHtml): ?>
<div class="container" style="padding-top: 20px;">
    <?= $_flashHtml ?>
</div>
<?php endif; ?>

<!-- ═══ Page Content ═══ -->
<?php require $content_view; ?>

<!-- ═══ Footer ═══ -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="/assets/images/logo-front.png" alt="Bareeda Injera">
            <p>Premium teff injera, baked fresh daily and delivered to your door across <?= sanitize($_businessCity) ?>.</p>
            <p class="amharic">በሬዳ እንጀራ &mdash; ንጹህ ጤፍ፣ ትኩስ እንጀራ</p>
            <div class="footer-social">
                <?php if (setting('telegram_url') !== ''): ?><a href="<?= sanitize(setting('telegram_url')) ?>" aria-label="Telegram" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></a><?php endif; ?>
                <?php if (setting('facebook_url') !== ''): ?><a href="<?= sanitize(setting('facebook_url')) ?>" aria-label="Facebook" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3Z"/></svg></a><?php endif; ?>
                <?php if (setting('instagram_url') !== ''): ?><a href="<?= sanitize(setting('instagram_url')) ?>" aria-label="Instagram" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg></a><?php endif; ?>
                <a href="tel:<?= sanitize(preg_replace('/[^0-9+]/', '', $_contactPhone)) ?>" aria-label="Call Bareeda"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.56 2.81.69A2 2 0 0 1 22 16.92Z"/></svg></a>
            </div>
        </div>

        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/products">Shop</a></li>
                <li><a href="/about">About Us</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </div>

        <div>
            <h4><?= $_isLoggedIn ? 'Account' : 'Shopping' ?></h4>
            <ul>
                <?php if ($_isLoggedIn): ?>
                    <li><a href="/account">Dashboard</a></li>
                    <li><a href="/orders">My Orders</a></li>
                    <li><a href="/subscriptions">Subscriptions</a></li>
                <?php else: ?>
                    <li><a href="/products">Browse Products</a></li>
                    <li><a href="/checkout">Checkout</a></li>
                <?php endif; ?>
                <li><a href="/cart">Shopping Cart</a></li>
            </ul>
        </div>

        <div class="footer-contact">
            <h4>Contact</h4>
            <p><strong>Bareeda Injera and Foods PLC</strong></p>
            <p><?= sanitize($_contactPhone) ?></p>
            <p><?= sanitize($_contactEmail) ?></p>
            <p><?= sanitize($_businessAddress) ?></p>
            <p style="margin-top: 12px; font-size: 0.82rem;">Mon&ndash;Sat: 6:00 AM &ndash; 6:00 PM</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Bareeda Injera and Foods PLC. All rights reserved. | <a href="/privacy" style="color: var(--text-muted);">Privacy</a> | <a href="/terms" style="color: var(--text-muted);">Terms</a></p>
    </div>
</footer>

</body>
</html>
