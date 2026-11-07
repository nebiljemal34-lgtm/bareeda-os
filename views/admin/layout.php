<?php
/**
 * Bareeda Injera - Admin Layout
 *
 * Dark-themed admin shell with sidebar navigation.
 * Each admin view sets $pageTitle and includes its content via $contentView.
 */

$_adminUser = Auth::user();
$_currentPath = current_path();
$_flashSuccess = flash('success');
$_flashError   = flash('error');
$_flashWarning = flash('warning');
$_flashInfo    = flash('info');

/**
 * Helper: check if a path prefix matches the current request.
 */
function _nav_active(string $prefix, string $current): string {
    return (strpos($current, $prefix) === 0) ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | Bareeda Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
    <style>
        /* ── Admin Layout ────────────────────────────────── */
        :root {
            --adm-bg:       #070d18;
            --adm-sidebar:  #0b1a2b;
            --adm-card:     #0f2236;
            --adm-border:   rgba(198,183,155,0.10);
            --adm-gold:     #c6b79b;
            --adm-gold-dk:  #a89878;
            --adm-text:     #e8e4dc;
            --adm-muted:    #6b7a8d;
            --adm-success:  #34d399;
            --adm-danger:   #f87171;
            --adm-warning:  #fbbf24;
            --adm-info:     #60a5fa;
            --adm-purple:   #a78bfa;
            --adm-orange:   #fb923c;
            --adm-sidebar-w: 248px;
            --adm-topbar-h:  58px;
        }

        body {
            background: var(--adm-bg);
            color: var(--adm-text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 13.5px;
            line-height: 1.5;
            margin: 0;
            min-height: 100vh;
        }

        a { color: var(--adm-gold); text-decoration: none; }
        a:hover { color: var(--adm-gold-dk); }

        /* ── Sidebar ─────────────────────────────────────── */
        .adm-sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--adm-sidebar-w);
            height: 100vh;
            background: var(--adm-sidebar);
            border-right: 1px solid var(--adm-border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }

        .adm-sidebar-brand {
            padding: 18px 20px;
            border-bottom: 1px solid var(--adm-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .adm-sidebar-brand .brand-icon {
            width: 32px; height: 32px;
            background: var(--adm-gold);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            color: var(--adm-sidebar);
            flex-shrink: 0;
        }

        .adm-sidebar-brand span {
            font-weight: 700;
            font-size: 15px;
            color: var(--adm-text);
            letter-spacing: -0.3px;
        }

        .adm-sidebar-brand small {
            display: block;
            font-size: 10px;
            color: var(--adm-muted);
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .adm-nav { padding: 12px 10px; flex: 1; }

        .adm-nav-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--adm-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 12px 6px;
        }

        .adm-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--adm-muted);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s;
            margin-bottom: 2px;
        }

        .adm-nav-item:hover {
            background: rgba(198,183,155,0.07);
            color: var(--adm-text);
        }

        .adm-nav-item.active {
            background: rgba(198,183,155,0.12);
            color: var(--adm-gold);
        }

        .adm-nav-item svg {
            width: 17px; height: 17px;
            flex-shrink: 0;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── Topbar ──────────────────────────────────────── */
        .adm-topbar {
            margin-left: var(--adm-sidebar-w);
            height: var(--adm-topbar-h);
            background: var(--adm-sidebar);
            border-bottom: 1px solid var(--adm-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .adm-topbar-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--adm-text);
        }

        .adm-topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .adm-topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--adm-muted);
        }

        .adm-topbar-user .avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--adm-gold);
            color: var(--adm-sidebar);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
        }

        .adm-logout {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--adm-danger);
            border: 1px solid rgba(248,113,113,0.25);
            background: transparent;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }

        .adm-logout:hover {
            background: rgba(248,113,113,0.1);
            color: var(--adm-danger);
        }

        /* ── Main Content ────────────────────────────────── */
        .adm-main {
            margin-left: var(--adm-sidebar-w);
            padding: 24px;
            min-height: calc(100vh - var(--adm-topbar-h));
        }

        /* ── Flash Messages ──────────────────────────────── */
        .adm-flash { margin-bottom: 16px; }

        .adm-alert {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid;
        }

        .adm-alert-success {
            background: rgba(52,211,153,0.08);
            border-color: rgba(52,211,153,0.25);
            color: var(--adm-success);
        }

        .adm-alert-error {
            background: rgba(248,113,113,0.08);
            border-color: rgba(248,113,113,0.25);
            color: var(--adm-danger);
        }

        .adm-alert-warning {
            background: rgba(251,191,36,0.08);
            border-color: rgba(251,191,36,0.25);
            color: var(--adm-warning);
        }

        .adm-alert-info {
            background: rgba(96,165,250,0.08);
            border-color: rgba(96,165,250,0.25);
            color: var(--adm-info);
        }

        /* ── Cards ───────────────────────────────────────── */
        .adm-card {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: 10px;
            padding: 20px;
        }

        .adm-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--adm-border);
        }

        .adm-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--adm-text);
        }

        /* ── Stat Cards ──────────────────────────────────── */
        .adm-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .adm-stat-card {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: 10px;
            padding: 18px;
        }

        .adm-stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--adm-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .adm-stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--adm-text);
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .adm-stat-trend {
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }

        .adm-stat-trend.up { color: var(--adm-success); }
        .adm-stat-trend.down { color: var(--adm-danger); }

        /* ── Tables ──────────────────────────────────────── */
        .adm-table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--adm-border);
        }

        .adm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .adm-table thead th {
            background: rgba(198,183,155,0.05);
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: var(--adm-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--adm-border);
            white-space: nowrap;
        }

        .adm-table tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--adm-border);
            color: var(--adm-text);
            vertical-align: middle;
        }

        .adm-table tbody tr:last-child td { border-bottom: none; }

        .adm-table tbody tr:hover { background: rgba(198,183,155,0.03); }

        /* ── Badges ──────────────────────────────────────── */
        .adm-badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .adm-badge-pending        { background: rgba(251,191,36,0.15); color: var(--adm-warning); }
        .adm-badge-confirmed      { background: rgba(96,165,250,0.15); color: var(--adm-info); }
        .adm-badge-preparing      { background: rgba(251,146,60,0.15); color: var(--adm-orange); }
        .adm-badge-out_for_delivery { background: rgba(167,139,250,0.15); color: var(--adm-purple); }
        .adm-badge-delivered      { background: rgba(52,211,153,0.15); color: var(--adm-success); }
        .adm-badge-cancelled      { background: rgba(248,113,113,0.15); color: var(--adm-danger); }
        .adm-badge-paid           { background: rgba(52,211,153,0.15); color: var(--adm-success); }
        .adm-badge-active         { background: rgba(52,211,153,0.15); color: var(--adm-success); }
        .adm-badge-paused         { background: rgba(251,191,36,0.15); color: var(--adm-warning); }
        .adm-badge-admin          { background: rgba(198,183,155,0.2); color: var(--adm-gold); }
        .adm-badge-customer       { background: rgba(96,165,250,0.15); color: var(--adm-info); }
        .adm-badge-inactive       { background: rgba(107,122,141,0.2); color: var(--adm-muted); }
        .adm-badge-low            { background: rgba(248,113,113,0.15); color: var(--adm-danger); }

        /* ── Buttons ─────────────────────────────────────── */
        .adm-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            line-height: 1.4;
        }

        .adm-btn-gold {
            background: var(--adm-gold);
            color: var(--adm-sidebar);
            border-color: var(--adm-gold);
        }
        .adm-btn-gold:hover {
            background: #d4c9b3;
            color: var(--adm-sidebar);
        }

        .adm-btn-outline {
            background: transparent;
            color: var(--adm-gold);
            border-color: rgba(198,183,155,0.3);
        }
        .adm-btn-outline:hover {
            background: rgba(198,183,155,0.08);
            color: var(--adm-gold);
        }

        .adm-btn-danger {
            background: transparent;
            color: var(--adm-danger);
            border-color: rgba(248,113,113,0.3);
        }
        .adm-btn-danger:hover {
            background: rgba(248,113,113,0.1);
        }

        .adm-btn-success {
            background: transparent;
            color: var(--adm-success);
            border-color: rgba(52,211,153,0.3);
        }
        .adm-btn-success:hover {
            background: rgba(52,211,153,0.1);
        }

        .adm-btn-sm {
            padding: 4px 10px;
            font-size: 11.5px;
            border-radius: 5px;
        }

        .adm-btn svg {
            width: 14px; height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── Forms ───────────────────────────────────────── */
        .adm-form-group { margin-bottom: 16px; }

        .adm-form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--adm-muted);
            margin-bottom: 5px;
        }

        .adm-form-input,
        .adm-form-select,
        .adm-form-textarea {
            width: 100%;
            padding: 8px 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--adm-border);
            border-radius: 7px;
            color: var(--adm-text);
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.15s;
        }

        .adm-form-input:focus,
        .adm-form-select:focus,
        .adm-form-textarea:focus {
            outline: none;
            border-color: var(--adm-gold);
            background: rgba(255,255,255,0.06);
        }

        .adm-form-textarea { resize: vertical; min-height: 80px; }

        .adm-form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7a8d' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 32px;
        }

        .adm-form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .adm-form-check input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--adm-gold);
        }

        .adm-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ── Pagination ──────────────────────────────────── */
        .adm-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 20px;
        }

        .adm-pagination a,
        .adm-pagination span {
            padding: 6px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--adm-border);
            color: var(--adm-muted);
            transition: all 0.15s;
        }

        .adm-pagination a:hover {
            background: rgba(198,183,155,0.08);
            color: var(--adm-text);
            border-color: rgba(198,183,155,0.2);
        }

        .adm-pagination .active {
            background: var(--adm-gold);
            color: var(--adm-sidebar);
            border-color: var(--adm-gold);
        }

        .adm-pagination .disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* ── Page Header ─────────────────────────────────── */
        .adm-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .adm-page-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--adm-text);
            letter-spacing: -0.3px;
        }

        .adm-page-subtitle {
            font-size: 12.5px;
            color: var(--adm-muted);
            margin-top: 2px;
        }

        /* ── Filter Bar ──────────────────────────────────── */
        .adm-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .adm-filter-bar .adm-form-input,
        .adm-filter-bar .adm-form-select {
            width: auto;
            min-width: 140px;
        }

        /* ── Grid helpers ────────────────────────────────── */
        .adm-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .adm-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        .adm-mb-16 { margin-bottom: 16px; }
        .adm-mb-20 { margin-bottom: 20px; }
        .adm-mb-24 { margin-bottom: 24px; }
        .adm-mt-16 { margin-top: 16px; }
        .adm-mt-20 { margin-top: 20px; }
        .adm-text-right { text-align: right; }
        .adm-text-center { text-align: center; }
        .adm-text-muted { color: var(--adm-muted); }
        .adm-text-gold { color: var(--adm-gold); }
        .adm-text-sm { font-size: 12px; }
        .adm-fw-600 { font-weight: 600; }
        .adm-fw-700 { font-weight: 700; }
        .adm-d-flex { display: flex; }
        .adm-gap-8 { gap: 8px; }
        .adm-gap-12 { gap: 12px; }
        .adm-ai-center { align-items: center; }
        .adm-jc-between { justify-content: space-between; }

        /* ── Thumbnail ───────────────────────────────────── */
        .adm-thumb {
            width: 38px; height: 38px;
            border-radius: 6px;
            object-fit: cover;
            background: rgba(198,183,155,0.08);
            border: 1px solid var(--adm-border);
        }

        /* ── Info List ───────────────────────────────────── */
        .adm-info-list { list-style: none; padding: 0; margin: 0; }
        .adm-info-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--adm-border);
            font-size: 13px;
        }
        .adm-info-list li:last-child { border-bottom: none; }
        .adm-info-list .info-label { color: var(--adm-muted); font-weight: 500; }
        .adm-info-list .info-value { color: var(--adm-text); font-weight: 600; }

        /* ── Empty State ─────────────────────────────────── */
        .adm-empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--adm-muted);
        }
        .adm-empty svg {
            width: 48px; height: 48px;
            stroke: var(--adm-muted);
            fill: none;
            stroke-width: 1.2;
            margin-bottom: 12px;
            opacity: 0.5;
        }
        .adm-empty p { font-size: 14px; margin: 0; }

        /* ── Footer ──────────────────────────────────────── */
        .adm-footer {
            margin-left: var(--adm-sidebar-w);
            padding: 16px 24px;
            border-top: 1px solid var(--adm-border);
            text-align: center;
            font-size: 11px;
            color: var(--adm-muted);
        }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 1024px) {
            .adm-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .adm-grid-2, .adm-grid-3 { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .adm-sidebar { display: none; }
            .adm-topbar, .adm-main, .adm-footer { margin-left: 0; }
            .adm-stats-grid { grid-template-columns: 1fr; }
            .adm-form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────── -->
<aside class="adm-sidebar">
    <div class="adm-sidebar-brand">
        <div class="brand-icon">B</div>
        <div>
            <span>Bareeda</span>
            <small>Admin Panel</small>
        </div>
    </div>

    <nav class="adm-nav">
        <div class="adm-nav-label">Main</div>

        <a href="/admin/dashboard" class="adm-nav-item<?= _nav_active('/admin/dashboard', $_currentPath) ?><?php if ($_currentPath === '/admin') echo ' active'; ?>">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>

        <a href="/admin/products" class="adm-nav-item<?= _nav_active('/admin/products', $_currentPath) ?>">
            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            Products
        </a>

        <a href="/admin/orders" class="adm-nav-item<?= _nav_active('/admin/orders', $_currentPath) ?>">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Orders
        </a>

        <a href="/admin/subscriptions" class="adm-nav-item<?= _nav_active('/admin/subscriptions', $_currentPath) ?>">
            <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
            Subscriptions
        </a>

        <div class="adm-nav-label" style="margin-top:8px;">Manage</div>

        <a href="/admin/users" class="adm-nav-item<?= _nav_active('/admin/users', $_currentPath) ?>">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            Customers
        </a>

        <a href="/admin/reports/sales" class="adm-nav-item<?= _nav_active('/admin/reports', $_currentPath) ?>">
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Reports
        </a>

        <a href="#" class="adm-nav-item" onclick="return false;" style="opacity:0.45;cursor:default;">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Settings
        </a>
    </nav>
</aside>

<!-- ── Topbar ───────────────────────────────────────────── -->
<header class="adm-topbar">
    <div class="adm-topbar-title"><?= sanitize($pageTitle) ?></div>
    <div class="adm-topbar-right">
        <div class="adm-topbar-user">
            <div class="avatar"><?= strtoupper(substr($_adminUser['first_name'] ?? 'A', 0, 1)) ?></div>
            <span><?= sanitize(($_adminUser['first_name'] ?? 'Admin') . ' ' . ($_adminUser['last_name'] ?? '')) ?></span>
        </div>
        <a href="/logout" class="adm-logout">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</header>

<!-- ── Main Content ─────────────────────────────────────── -->
<main class="adm-main">

    <!-- Flash Messages -->
    <?php if ($_flashSuccess || $_flashError || $_flashWarning || $_flashInfo): ?>
    <div class="adm-flash">
        <?php if ($_flashSuccess): ?><div class="adm-alert adm-alert-success"><?= sanitize($_flashSuccess) ?></div><?php endif; ?>
        <?php if ($_flashError):   ?><div class="adm-alert adm-alert-error"><?= sanitize($_flashError) ?></div><?php endif; ?>
        <?php if ($_flashWarning): ?><div class="adm-alert adm-alert-warning"><?= sanitize($_flashWarning) ?></div><?php endif; ?>
        <?php if ($_flashInfo):    ?><div class="adm-alert adm-alert-info"><?= sanitize($_flashInfo) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <?php if (isset($contentFile) && file_exists($contentFile)): ?>
        <?php require $contentFile; ?>
    <?php else: ?>
        <div class="adm-empty">
            <p>View file not found.</p>
        </div>
    <?php endif; ?>

</main>

<!-- ── Footer ───────────────────────────────────────────── -->
<footer class="adm-footer">
    &copy; <?= date('Y') ?> Bareeda Injera. All rights reserved. &nbsp;|&nbsp; v<?= APP_VERSION ?>
</footer>

</body>
</html>
