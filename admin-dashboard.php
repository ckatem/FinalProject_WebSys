<?php
declare(strict_types=1);

require_once __DIR__ . '/php/config/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$admin = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$admin->execute([(int) $_SESSION['user_id']]);
$adminUser = $admin->fetch();

if (!$adminUser || $adminUser['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_csrf_token'];

$stats = [
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'draft'")->fetchColumn(),
    'approved' => (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
    'reported' => (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];
 
$pending = $pdo->query(
    "SELECT l.id, l.title, l.price, l.status, l.rejection_reason, l.created_at,
            (SELECT image_path FROM listing_images li
             WHERE li.listing_id = l.id
             ORDER BY li.sort_order, li.id
             LIMIT 1) AS image_path,
            CONCAT_WS(' ', u.first_name, u.last_name) AS seller_name
     FROM listings l
     JOIN users u ON u.id = l.seller_id
     WHERE l.status IN ('draft', 'active', 'hidden')
     ORDER BY CASE l.status WHEN 'draft' THEN 0 WHEN 'active' THEN 1 ELSE 2 END, l.created_at DESC"
)->fetchAll();

function dashboardDate(string $date): string {
    return date('M j, Y', strtotime($date));
}

function dashboardMoney(string|float $price): string {
    return 'PHP ' . number_format((float) $price, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ReSource</title>
    <meta name="theme-color" content="#f6b429">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --yellow: #f6b429;
            --yellow-dark: #d9950b;
            --ink: #171717;
            --muted: #6a6a6a;
            --line: #d8d8d8;
            --paper: #eef0f2;
            --white: #fff;
            --green: #68bd00;
            --red: #ed3340;
            --shadow: 0 12px 35px rgba(0, 0, 0, .08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: Inter, sans-serif;
            background: radial-gradient(circle at 10% 0%, rgba(246, 180, 41, .12), transparent 25%), linear-gradient(180deg, #f7f7f5 0%, var(--paper) 100%);
        }
        button, input, select, textarea { font: inherit; }
        button { cursor: pointer; }
        .admin-shell { min-height: calc(100vh - 76px); display: flex; }
        .sidebar {
            background: var(--white);
            color: var(--ink);
            display: flex;
            flex-direction: column;
            width: 230px;
            flex-shrink: 0;
            padding: 0 10px;
            border-right: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #080808;
            background: transparent;
            padding: 20px 8px 16px;
            border-radius: 0;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .7px;
            margin-bottom: 0;
            text-decoration: none;
            border-bottom: 1px solid #eee;
        }
        .brand img { width: 49px; height: 40px; object-fit: contain; border-radius: 0; border: 0; }
        .sidebar-menu-header { padding: 18px 12px 8px; color: #888; font-size: 10px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; }
        .side-nav { display: flex; flex-direction: column; gap: 3px; padding: 13px 0; }
        .side-nav a, .logout { color: #4d4d4d; text-decoration: none; font-size: 12px; line-height: 1.3; font-weight: 600; padding: 11px 12px; border-radius: 9px; transition: .2s; }
        .side-nav a span, .logout span { display: inline-block; width: 20px; margin-right: 8px; text-align: center; font-size: 15px; color: #888; }
        .side-nav a:hover, .side-nav a.active, .logout:hover { color: #111; background: var(--yellow); box-shadow: 0 5px 15px rgba(246, 180, 41, .18); }
        .side-label { color: #999; font-size: 9px; margin: 26px 6px 5px; }
        .logout { margin-top: auto; }
        .sidebar-art { height: 110px; margin: auto -10px 0; overflow: hidden; position: relative; background: #b7b7b7; clip-path: polygon(0 100%, 100% 0, 100% 100%); }
        .sidebar-art::after { content: ''; position: absolute; inset: 32% 0 0 32%; background: var(--yellow); clip-path: polygon(0 100%, 100% 0, 100% 100%); }
        .sidebar-info { margin: 12px 2px; padding: 14px; border-radius: 12px; background: linear-gradient(135deg, #191919, #292929); color: white; position: relative; z-index: 2; }
        .sidebar-info strong { display: block; color: var(--yellow); font-size: 9px; letter-spacing: 1px; }
        .sidebar-info p { margin: 7px 0 0; color: #cfcfcf; font-size: 10px; line-height: 1.55; }
        .site-header { height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 0 30px; background: #1c1c1c; border-bottom: 1px solid #303030; box-shadow: 0 4px 25px rgba(0,0,0,.18); }
        .menu-toggle-btn { border: 1px solid #555; background: transparent; color: #fff; border-radius: 999px; padding: 9px 13px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .header-brand { display: flex; align-items: center; gap: 11px; min-width: 230px; color: #fff; text-decoration: none; }
        .header-logo { width: 43px; height: 43px; display: grid; place-items: center; overflow: hidden; border-radius: 9px; background: #252525; border: 1px solid #3a3a3a; }
        .header-logo img { width: 100%; height: 100%; object-fit: contain; }
        .header-brand-text { line-height: 1; }
        .header-title { font-size: 17px; font-weight: 800; letter-spacing: .8px; }
        .header-title span { color: var(--yellow); }
        .header-subtitle { margin-top: 5px; color: #999; font-size: 8px; letter-spacing: 1.2px; font-weight: 700; }
        .top-title { flex: 1; min-width: 195px; color: #fff; }
        .top-title small { display: block; color: #bbb; font-size: 9px; font-weight: 700; }
        h1 { font: 700 20px Merriweather, serif; margin: 4px 0 0; }
        .search { width: min(100%, 350px); height: 38px; border: 1px solid #555; border-radius: 999px; background: #292929; padding: 0 14px; color: #fff; outline: none; }
        .admin-account { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .account-icon { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; background: var(--yellow); color: #111; font-size: 18px; }
        .account-name { font-size: 11px; font-weight: 700; line-height: 1.35; }
        .account-name span { display: block; font-size: 9px; font-weight: 400; color: #bbb; }
        main { flex: 1; min-width: 0; padding: 28px 34px 50px; max-width: none; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; margin-bottom: 32px; }
        .stat { min-height: 65px; border: 1px solid #e1e1e1; border-radius: 19px; background: white; display: flex; align-items: center; gap: 10px; padding: 10px 14px; box-shadow: var(--shadow); }
        .stat-icon { width: 32px; height: 32px; flex: 0 0 32px; display: grid; place-items: center; border-radius: 50%; background: #050505; color: white; font-size: 17px; }
        .stat:nth-child(2) .stat-icon { background: var(--green); }
        .stat:nth-child(3) .stat-icon { background: var(--red); border-radius: 4px; }
        .stat h2 { font-size: 13px; margin: 0 0 3px; }
        .stat p { font-size: 10px; margin: 0; color: #333; }
        .section-heading { display: flex; align-items: end; justify-content: space-between; gap: 12px; margin: 0 8px 12px; }
        .section-heading h2 { font-size: 14px; margin: 0; }
        .section-heading a { color: #bc8500; font-size: 10px; font-weight: 700; text-decoration: none; }
        .section-heading a:hover { color: #111; }
        .table-wrap { overflow-x: auto; border: 1px solid #e1e1e1; border-radius: 16px; background: white; box-shadow: var(--shadow); }
        table { border-collapse: collapse; width: 100%; min-width: 700px; }
        th, td { text-align: left; padding: 10px 14px; font-size: 11px; border-bottom: 1px solid #eee; }
        th { font-size: 12px; padding-top: 14px; }
        tr:last-child td { border-bottom: 0; }
        .item-cell { display: flex; align-items: center; gap: 10px; min-width: 190px; }
        .item-thumb { width: 32px; height: 32px; flex: 0 0 32px; background: #050505; object-fit: cover; }
        .item-title { line-height: 1.2; }
        .item-price { font-size: 10px; margin-top: 2px; }
        .actions { display: flex; gap: 20px; }
        .action { border: 0; border-radius: 5px; padding: 4px 11px; font-size: 11px; }
        .action.approve { color: #579800; background: #eef2eb; }
        .action.reject { color: var(--red); background: #f5eeee; }
        .empty { color: var(--muted); text-align: center; padding: 28px; }
        .admin-section { display: none; animation: sectionIn .25s ease both; }
        .admin-section.active { display: block; }
        .section-intro { margin: 0 0 18px; }
        .section-intro h2 { margin: 0 0 5px; font: 700 23px Merriweather, serif; }
        .section-intro p { margin: 0; color: var(--muted); font-size: 12px; }
        .status { display: inline-block; padding: 5px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: capitalize; background: #f1f1f1; }
        .status.active { color: #397d00; background: #eef8e7; }
        .status.draft, .status.pending { color: #9a6800; background: #fff4d3; }
        .status.sold { color: #555; background: #ededed; }
        .status.hidden, .status.dismissed, .status.banned { color: #a52a2a; background: #fcecec; }
        .status.suspended { color: #9a6800; background: #fff4d3; }
        .table-search { width: min(100%, 330px); height: 38px; margin-bottom: 12px; padding: 0 12px; border: 1px solid var(--line); border-radius: 10px; background: #fff; outline: none; }
        .table-controls { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; }
        .table-controls .table-search { margin: 0; }
        .table-filter { min-width: 155px; height: 38px; padding: 0 10px; border: 1px solid var(--line); border-radius: 10px; background: #fff; }
        .action.view { color: #333; background: #eee; }
        .action.delete { color: #a52a2a; background: #fcecec; }
        .action.review { color: #805b00; background: #fff4d3; }
        .action.resolve { color: #397d00; background: #eef8e7; }
        .action.dismiss { color: #a52a2a; background: #fcecec; }
        .action:disabled { cursor: wait; opacity: .55; }
        dialog { width: min(680px, calc(100% - 28px)); max-height: 88vh; padding: 0; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 20px 70px rgba(0,0,0,.28); }
        dialog::backdrop { background: rgba(0,0,0,.55); }
        .modal-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px 20px; border-bottom: 1px solid #eee; }
        .modal-head h2 { margin: 0; font-size: 17px; }
        .modal-body { padding: 18px 20px; max-height: calc(88vh - 130px); overflow: auto; }
        .modal-close { border: 0; background: transparent; font-size: 22px; line-height: 1; }
        .modal-images { display: flex; gap: 8px; overflow-x: auto; margin-bottom: 16px; }
        .modal-images img { width: 140px; height: 110px; flex: 0 0 auto; object-fit: cover; background: #eee; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; }
        .detail-grid div { min-width: 0; }
        .detail-grid strong, .detail-label { display: block; margin-bottom: 4px; color: var(--muted); font-size: 10px; text-transform: uppercase; }
        .detail-grid p, .report-description { margin: 0; font-size: 12px; line-height: 1.5; overflow-wrap: anywhere; white-space: pre-wrap; }
        .modal-field { width: 100%; min-height: 90px; resize: vertical; padding: 10px; border: 1px solid var(--line); border-radius: 8px; }
        .modal-actions { display: flex; flex-wrap: wrap; gap: 8px; padding: 14px 20px; border-top: 1px solid #eee; }
        .report-preview { display: flex; align-items: center; gap: 12px; padding: 12px; margin-top: 15px; border: 1px solid #eee; border-radius: 8px; }
        .report-preview img { width: 72px; height: 72px; object-fit: cover; background: #eee; }
        .report-preview p { margin: 4px 0 0; color: var(--muted); font-size: 11px; }
        @keyframes sectionIn { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: translateY(0); } }
        .toast { position: fixed; right: 20px; bottom: 20px; background: #222; color: white; padding: 12px 15px; border-radius: 6px; font-size: 12px; opacity: 0; transform: translateY(10px); transition: .2s; pointer-events: none; }
        .toast.show { opacity: 1; transform: translateY(0); }
        @media (max-width: 800px) {
            .site-header { height: auto; min-height: 76px; padding: 12px 16px; flex-wrap: wrap; gap: 10px; }
            .menu-toggle-btn { order: 0; }
            .header-brand { min-width: 0; flex: 1; }
            .header-brand-text, .top-title { display: none; }
            .admin-account { margin-left: 0; }
            .account-name { display: none; }
            .search { order: 3; flex-basis: 100%; width: 100%; max-width: none; }
            .admin-shell { display: block; min-height: calc(100vh - 76px); }
            .sidebar { position: fixed; z-index: 20; top: 0; bottom: 0; left: -240px; transition: left .2s ease; box-shadow: 12px 0 35px rgba(0,0,0,.15); }
            body.menu-open .sidebar { left: 0; }
            main { padding: 24px 16px 35px; }
            .stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        }
        @media (max-width: 440px) { .stats { grid-template-columns: 1fr; } .account-name { display: none; } .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header class="site-header">
    <button class="menu-toggle-btn" type="button" id="menu-toggle" aria-label="Toggle admin navigation">Menu</button>
    <a class="header-brand" href="index.php">
        <div class="header-logo"><img src="tiplogo.png" alt="TIP Logo"></div>
        <div class="header-brand-text"><div class="header-title"><span>RE</span>SOURCE</div><div class="header-subtitle">TIP CAMPUS MARKETPLACE</div></div>
    </a>
    <div class="top-title"><small>TIP CAMPUS MARKETPLACE</small><h1 id="admin-page-title">Admin Dashboard</h1></div>
    <input class="search" type="search" placeholder="Search books, courses, tools..." aria-label="Search dashboard">
    <div class="admin-account">
        <div class="account-icon" aria-hidden="true">&#128276;</div>
        <div class="account-icon" aria-hidden="true">&#128100;</div>
        <div class="account-name"><?= htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name'], ENT_QUOTES, 'UTF-8') ?><span>Administrator</span></div>
    </div>
</header>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-menu-header"><strong>Navigation Menu</strong></div>
        <nav class="side-nav" aria-label="Admin navigation">
            <a href="#dashboard" data-section="dashboard"><span aria-hidden="true">&#127968;</span>Homepage / Dashboard</a>
            <a href="index.php?page=browse"><span aria-hidden="true">&#128269;</span>Browse / Search</a>
            <a href="index.php?page=saved"><span aria-hidden="true">&#128278;</span>Saved Items</a>
            <a href="#listings" data-section="listings"><span aria-hidden="true">&#127991;</span>Listings</a>
            <a href="index.php?page=create"><span aria-hidden="true">&#10133;</span>Create Listing</a>
            <a href="index.php?page=messaging"><span aria-hidden="true">&#128172;</span>Messaging</a>
        </nav>
        <div class="side-label">Admin tools</div>
        <nav class="side-nav">
            <a href="#reports" data-section="reports"><span aria-hidden="true">&#128680;</span>Reports</a>
            <a href="#users" data-section="users"><span aria-hidden="true">&#128101;</span>Users</a>
            <a href="#audit" data-section="audit"><span aria-hidden="true">&#128467;</span>Audit Log</a>
            <a href="#profile" data-section="profile"><span aria-hidden="true">&#128100;</span>User Profile</a>
        </nav>
        <div class="sidebar-info"><strong>TIP STUDENTS ONLY</strong><p>A safer and simpler way for TIPians to buy, sell, and exchange campus resources.</p></div>
       <a
    class="logout"
    href="#"
    id="admin-logout-btn">

    <span aria-hidden="true">&#8594;</span>
    Log-out

</a>
        <div class="sidebar-art" aria-hidden="true"></div>
    </aside>

    <main>
        <section class="stats" aria-label="Dashboard statistics">
            <article class="stat"><div class="stat-icon">&#9203;</div><div><h2>Pending Listing</h2><p id="pending-count"><?= $stats['pending'] ?></p></div></article>
            <article class="stat"><div class="stat-icon">&#10003;</div><div><h2>Approved Listing</h2><p id="approved-count"><?= $stats['approved'] ?></p></div></article>
            <article class="stat"><div class="stat-icon">!</div><div><h2>Reported Items</h2><p id="reported-count"><?= $stats['reported'] ?></p></div></article>
            <article class="stat"><div class="stat-icon">&#128100;</div><div><h2>Total Users</h2><p><?= $stats['users'] ?></p></div></article>
        </section>

        <section class="admin-section active" id="dashboard">
            <div class="section-heading"><h2>Pending Listing</h2><a href="#listings" data-section="listings">View all listings</a></div>
            <div class="table-controls">
                <input class="table-search" id="queue-search" type="search" placeholder="Search pending queue..." aria-label="Search pending queue">
                <select class="table-filter" id="queue-status-filter" aria-label="Filter pending queue by status">
                    <option value="all">All statuses</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="table-wrap">
                <table id="pending-queue-table">
                    <thead><tr><th>Items</th><th>Seller</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (!$pending): ?>
                        <tr><td colspan="5" class="empty">No listings in this queue.</td></tr>
                    <?php else: foreach ($pending as $listing): ?>
                        <?php $queueStatus = $listing['status'] === 'draft' ? 'pending' : ($listing['status'] === 'active' ? 'approved' : 'rejected'); ?>
                        <tr data-listing-id="<?= (int) $listing['id'] ?>" data-queue-status="<?= $queueStatus ?>">
                            <td><div class="item-cell"><?php if (!empty($listing['image_path'])): ?><img class="item-thumb" src="<?= htmlspecialchars($listing['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><span class="item-thumb" aria-hidden="true"></span><?php endif; ?><span class="item-title"><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?><span class="item-price"><?= dashboardMoney($listing['price']) ?></span></span></div></td>
                            <td><?= htmlspecialchars($listing['seller_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= dashboardDate($listing['created_at']) ?></td>
                            <td><span class="status <?= htmlspecialchars($listing['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($listing['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><div class="actions"><button class="action view" type="button" data-listing-view="<?= (int) $listing['id'] ?>">View</button><?php if ($queueStatus === 'pending'): ?><button class="action approve" type="button" data-listing-action="approve">Approve</button><button class="action reject" type="button" data-listing-action="reject">Reject</button><button class="action delete" type="button" data-listing-action="delete">Delete permanently</button><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-section" id="listings">
            <div class="section-intro"><h2>Listings</h2><p>Review every item published on the marketplace.</p></div>
            <input class="table-search" type="search" data-filter-target="listings-table" placeholder="Search listings..." aria-label="Search listings">
            <div class="table-wrap"><table id="listings-table"><thead><tr><th>Title</th><th>Seller</th><th>Category</th><th>Price</th><th>Status</th><th>Submitted</th></tr></thead><tbody><tr><td colspan="6" class="empty">Loading listings...</td></tr></tbody></table></div>
        </section>

        <section class="admin-section" id="reports">
            <div class="section-intro"><h2>Reports</h2><p>Monitor reports submitted by students and review the affected listings.</p></div>
            <div class="table-controls"><input class="table-search" type="search" data-filter-target="reports-table" placeholder="Search reports..." aria-label="Search reports"><select class="table-filter" id="report-status-filter" aria-label="Filter reports by status"><option value="all">All statuses</option><option value="pending">Pending</option><option value="reviewed">Reviewed</option><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option></select></div>
            <div class="table-wrap"><table id="reports-table"><thead><tr><th>Listing</th><th>Reporter</th><th>Reason</th><th>Description</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead><tbody><tr><td colspan="7" class="empty">Loading reports...</td></tr></tbody></table></div>
        </section>

        <section class="admin-section" id="users">
            <div class="section-intro"><h2>Users</h2><p>View registered TIP marketplace accounts and enforce account status.</p></div>
            <input class="table-search" type="search" data-filter-target="users-table" placeholder="Search users..." aria-label="Search users">
            <div class="table-wrap"><table id="users-table"><thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Course</th><th>Campus</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr><td colspan="7" class="empty">Loading users...</td></tr></tbody></table></div>
        </section>

        <section class="admin-section" id="audit">
            <div class="section-intro"><h2>Audit Log</h2><p>Review who approved, rejected, or restricted actions in the marketplace.</p></div>
            <div class="table-wrap"><table id="audit-table"><thead><tr><th>Admin</th><th>Action</th><th>Entity</th><th>Target</th><th>Reason</th><th>Date</th></tr></thead><tbody><tr><td colspan="6" class="empty">Loading audit log...</td></tr></tbody></table></div>
        </section>

        <section class="admin-section" id="profile">
            <div class="section-intro"><h2>Profile</h2><p>Review the administrator account currently signed in.</p></div>
            <div class="table-wrap">
                <div style="padding: 20px;">
                    <div class="detail-grid">
                        <div><strong>Full Name</strong><p><?= htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
                        <div><strong>Role</strong><p><?= htmlspecialchars($adminUser['role'], ENT_QUOTES, 'UTF-8') ?></p></div>
                        <div><strong>Access</strong><p>Administrator</p></div>
                        <div><strong>Dashboard</strong><p>Marketplace oversight</p></div>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>
<dialog id="listing-modal" aria-labelledby="listing-modal-title">
    <div class="modal-head"><h2 id="listing-modal-title">Listing details</h2><button class="modal-close" type="button" data-close-modal aria-label="Close">&times;</button></div>
    <div class="modal-body" id="listing-modal-body"></div>
</dialog>
<dialog id="reject-modal" aria-labelledby="reject-modal-title">
    <form id="reject-form">
        <div class="modal-head"><h2 id="reject-modal-title">Reject listing</h2><button class="modal-close" type="button" data-close-modal aria-label="Close">&times;</button></div>
        <div class="modal-body">
            <label class="detail-label" for="rejection-reason-preset">Saved reasons</label>
            <select class="table-filter" id="rejection-reason-preset" aria-label="Choose a rejection reason">
                <option value="">Custom reason</option>
                <option value="Policy violation: item does not match the listing description.">Policy violation: item does not match the listing description.</option>
                <option value="Missing required details: image, price, or item information is incomplete.">Missing required details: image, price, or item information is incomplete.</option>
                <option value="Prohibited listing: the item is not allowed for the marketplace.">Prohibited listing: the item is not allowed for the marketplace.</option>
                <option value="Quality issue: the listing is blurry, low quality, or misleading.">Quality issue: the listing is blurry, low quality, or misleading.</option>
            </select>
            <label class="detail-label" for="rejection-reason" style="margin-top:12px">Rejection reason</label>
            <textarea class="modal-field" id="rejection-reason" maxlength="255" required></textarea>
            <small id="reject-listing-label"></small>
        </div>
        <div class="modal-actions"><button class="action reject" type="submit" id="reject-submit">Reject listing</button><button class="action view" type="button" data-close-modal>Cancel</button></div>
    </form>
</dialog>
<dialog id="report-modal" aria-labelledby="report-modal-title">
    <div class="modal-head"><h2 id="report-modal-title">Report details</h2><button class="modal-close" type="button" data-close-modal aria-label="Close">&times;</button></div>
    <div class="modal-body" id="report-modal-body"></div>
    <div class="modal-actions" id="report-modal-actions"></div>
</dialog>
<div class="toast" id="toast" role="status"></div>
<script>
const csrfToken = <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const toast = document.getElementById('toast');
let reportsById = new Map();
let activeReportId = null;
let rejectingRow = null;

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

function updateCount(id, difference) {
    const element = document.getElementById(id);
    element.textContent = String(Math.max(0, Number(element.textContent) + difference));
}

async function postAdmin(endpoint, fields) {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
        body: new URLSearchParams(fields)
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Action failed.');
    return result;
}

document.getElementById('menu-toggle').addEventListener('click', () => {
    document.body.classList.toggle('menu-open');
});

const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
}[character]));

const formatDate = (value) => {
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? escapeHtml(value) : date.toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const statusBadge = (value) => `<span class="status ${escapeHtml(value)}">${escapeHtml(value)}</span>`;

async function loadAdminData(endpoint, tableId, renderRow, emptyMessage, columnCount) {
    const body = document.querySelector(`#${tableId} tbody`);
    try {
        const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load data.');
        const key = tableId.replace('-table', '');
        const rows = result[key] || [];
        body.innerHTML = rows.length ? rows.map(renderRow).join('') : `<tr><td colspan="${columnCount}" class="empty">${emptyMessage}</td></tr>`;
    } catch (error) {
        body.innerHTML = `<tr><td colspan="${columnCount}" class="empty">${escapeHtml(error.message)}</td></tr>`;
    }
}

function loadListings() {
    return loadAdminData('php/admin/listings.php', 'listings-table', listing => `<tr data-listing-id="${escapeHtml(listing.id)}">
        <td>${escapeHtml(listing.title)}</td>
        <td>${escapeHtml(listing.seller_name)}</td>
        <td>${escapeHtml(listing.category)}</td>
        <td>${escapeHtml(Number(listing.price).toFixed(2))}</td>
        <td>${statusBadge(listing.status)}</td>
        <td>${formatDate(listing.created_at)}</td>
        <td>
            <div class="actions">
                <button class="action view" type="button" data-listing-view="${escapeHtml(listing.id)}">View</button>
                ${listing.status !== 'hidden' ? '<button class="action dismiss" type="button" data-listing-action="hide">Hide</button>' : ''}
                <button class="action delete" type="button" data-listing-action="delete">Delete</button>
            </div>
        </td>
    </tr>`, 'No listings found.', 7);
}

function loadReports() {
    const body = document.querySelector('#reports-table tbody');
    return fetch('php/admin/reports.php', { headers: { Accept: 'application/json' } })
        .then(async response => {
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load reports.');
            reportsById = new Map((result.reports || []).map(report => [String(report.id), report]));
            const reports = [...reportsById.values()];
            body.innerHTML = reports.length ? reports.map(report => {
                const id = escapeHtml(report.id);
                const pendingActions = report.status === 'pending'
                    ? `<button class="action dismiss" type="button" data-report-action="dismiss">Dismiss</button><button class="action review" type="button" data-report-action="review">Mark Reviewed</button><button class="action resolve" type="button" data-report-action="resolve_hide">Resolve &amp; Hide</button>`
                    : '';
                return `<tr data-report-id="${id}" data-listing-id="${escapeHtml(report.listing_id)}" data-report-status="${escapeHtml(report.status)}">
                    <td>${escapeHtml(report.title)}</td><td>${escapeHtml(report.reporter_name)}</td>
                    <td>${escapeHtml(report.reason)}</td><td>${escapeHtml(report.description || 'No description')}</td>
                    <td data-report-status-cell>${statusBadge(report.status)}</td><td>${formatDate(report.created_at)}</td>
                    <td><div class="actions"><button class="action view" type="button" data-report-view="${id}">View</button>${pendingActions}</div></td>
                </tr>`;
            }).join('') : '<tr><td colspan="7" class="empty">No reports found.</td></tr>';
            applyReportFilters();
        })
        .catch(error => {
            body.innerHTML = `<tr><td colspan="7" class="empty">${escapeHtml(error.message)}</td></tr>`;
            showToast(error.message || 'Unable to load reports.');
        });
}

function loadUsers() {
    return loadAdminData('php/admin/users.php', 'users-table', user => `<tr data-user-id="${escapeHtml(user.id)}">
        <td>${escapeHtml([user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' '))}</td>
        <td>${escapeHtml(user.student_id)}</td>
        <td>${escapeHtml(user.email)}</td>
        <td>${escapeHtml(user.course)}</td>
        <td>${escapeHtml(user.campus)}</td>
        <td>${statusBadge(user.status || 'active')}</td>
        <td><div class="actions">
            ${user.status === 'banned' ? '<button class="action view" type="button" data-user-action="activate">Reactivate</button>' : ''}
            ${user.status !== 'suspended' && user.status !== 'banned' ? '<button class="action review" type="button" data-user-action="suspend">Suspend</button>' : ''}
            ${user.status !== 'banned' ? '<button class="action dismiss" type="button" data-user-action="ban">Ban</button>' : ''}
        </div></td>
    </tr>`, 'No users found.', 7);
}

async function loadAuditLogs() {
    const body = document.querySelector('#audit-table tbody');
    try {
        const response = await fetch('php/admin/audit-logs.php', { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load the audit log.');
        const rows = result.audit_logs || [];
        body.innerHTML = rows.length ? rows.map(log => `
            <tr>
                <td>${escapeHtml(log.admin_name || 'System')}</td>
                <td>${escapeHtml(log.action || 'Unknown')}</td>
                <td>${escapeHtml(log.entity_type || 'N/A')}</td>
                <td>${escapeHtml(log.target_name || `#${log.target_id || 'n/a'}`)}</td>
                <td>${escapeHtml(log.reason || log.details || '—')}</td>
                <td>${formatDate(log.created_at)}</td>
            </tr>
        `).join('') : '<tr><td colspan="6" class="empty">No audit entries found.</td></tr>';
    } catch (error) {
        body.innerHTML = `<tr><td colspan="6" class="empty">${escapeHtml(error.message)}</td></tr>`;
    }
}

const loaders = { listings: loadListings, reports: loadReports, users: loadUsers, audit: loadAuditLogs };
function showSection(section) {
    const target = document.getElementById(section) ? section : 'dashboard';
    document.querySelector('.stats').hidden = target !== 'dashboard';
    document.querySelectorAll('.admin-section').forEach(item => item.classList.toggle('active', item.id === target));
    document.querySelectorAll('[data-section]').forEach(item => item.classList.toggle('active', item.dataset.section === target));
    document.getElementById('admin-page-title').textContent = target === 'dashboard'
        ? 'Admin Dashboard'
        : target.charAt(0).toUpperCase() + target.slice(1);
    if (loaders[target]) loaders[target]();
    if (window.location.hash !== `#${target}`) history.replaceState(null, '', `#${target}`);
}

document.querySelectorAll('[data-section]').forEach(link => link.addEventListener('click', event => {
    event.preventDefault();
    document.body.classList.remove('menu-open');
    showSection(link.dataset.section);
}));

function applyQueueFilters() {
    const query = document.getElementById('queue-search').value.trim().toLowerCase();
    const status = document.getElementById('queue-status-filter').value;
    document.querySelectorAll('#pending-queue-table tbody tr[data-listing-id]').forEach(row => {
        row.hidden = !(row.textContent.toLowerCase().includes(query) && (status === 'all' || row.dataset.queueStatus === status));
    });
}

function applyReportFilters() {
    const query = document.querySelector('[data-filter-target="reports-table"]').value.trim().toLowerCase();
    const status = document.getElementById('report-status-filter').value;
    document.querySelectorAll('#reports-table tbody tr[data-report-id]').forEach(row => {
        row.hidden = !(row.textContent.toLowerCase().includes(query) && (status === 'all' || row.dataset.reportStatus === status));
    });
}

document.getElementById('queue-search').addEventListener('input', applyQueueFilters);
document.getElementById('queue-status-filter').addEventListener('change', applyQueueFilters);
document.getElementById('report-status-filter').addEventListener('change', applyReportFilters);
document.querySelectorAll('[data-filter-target]').forEach(input => input.addEventListener('input', () => {
    if (input.dataset.filterTarget === 'reports-table') applyReportFilters();
    else {
        const query = input.value.trim().toLowerCase();
        document.querySelectorAll(`#${input.dataset.filterTarget} tbody tr`).forEach(row => {
            row.hidden = !row.textContent.toLowerCase().includes(query);
        });
    }
}));

document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => {
    button.closest('dialog').close();
}));

const applyReasonPreset = (presetValue) => {
    const reasonField = document.getElementById('rejection-reason');
    if (!presetValue) {
        reasonField.value = reasonField.value || '';
        return;
    }
    reasonField.value = presetValue;
};
document.getElementById('rejection-reason-preset').addEventListener('change', (event) => {
    applyReasonPreset(event.target.value);
});

async function handleUserAction(userId, action) {
    const reason = action === 'activate' ? '' : prompt(action === 'suspend' ? 'Provide a suspension reason:' : 'Provide a ban reason:');
    if (action !== 'activate' && (!reason || !reason.trim())) {
        showToast('A reason is required for this action.');
        return;
    }
    try {
        const result = await postAdmin('php/admin/update-user-status.php', { user_id: userId, action, reason: reason || '' });
        showToast(result.message);
        loadUsers();
    } catch (error) {
        showToast(error.message || 'Unable to update the user.');
    }
}

document.getElementById('users-table').addEventListener('click', async event => {
    const actionButton = event.target.closest('[data-user-action]');
    if (!actionButton) return;
    const row = actionButton.closest('tr');
    const userId = row?.dataset.userId || actionButton.dataset.userId;
    if (!userId) return;
    await handleUserAction(userId, actionButton.dataset.userAction);
});

document.querySelector('#pending-queue-table tbody').addEventListener('click', async event => {
    const viewButton = event.target.closest('[data-listing-view]');
    if (viewButton) {
        viewButton.disabled = true;
        try {
            const response = await fetch(`php/listings/detail.php?id=${encodeURIComponent(viewButton.dataset.listingView)}`, {
                headers: { Accept: 'application/json' }
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load listing.');
            const listing = result.listing;
            const images = (listing.images || []).map(path => `<img src="${escapeHtml(path)}" alt="${escapeHtml(listing.title)}">`).join('');
            document.getElementById('listing-modal-body').innerHTML = `
                <div class="modal-images">${images || '<p>No images provided.</p>'}</div>
                <div class="detail-grid">
                    <div><strong>Title</strong><p>${escapeHtml(listing.title)}</p></div>
                    <div><strong>Price</strong><p>PHP ${escapeHtml(Number(listing.price).toFixed(2))}</p></div>
                    <div><strong>Category</strong><p>${escapeHtml(listing.category)}</p></div>
                    <div><strong>Condition</strong><p>${escapeHtml(listing.item_condition)}</p></div>
                    <div><strong>Course code</strong><p>${escapeHtml(listing.course_code || 'Not provided')}</p></div>
                    <div><strong>Department</strong><p>${escapeHtml(listing.department || 'Not provided')}</p></div>
                    <div><strong>Campus</strong><p>${escapeHtml(listing.campus)}</p></div>
                    <div><strong>Seller</strong><p>${escapeHtml(listing.seller_name)}</p></div>
                    <div><strong>Description</strong><p>${escapeHtml(listing.description)}</p></div>
                </div>`;
            document.getElementById('listing-modal').showModal();
        } catch (error) {
            showToast(error.message || 'Unable to load listing.');
        } finally {
            viewButton.disabled = false;
        }
        return;
    }

    const actionButton = event.target.closest('[data-listing-action]');
    if (!actionButton) return;
    const row = actionButton.closest('tr');
    if (actionButton.dataset.listingAction === 'reject') {
        rejectingRow = row;
        document.getElementById('reject-listing-label').textContent = row.cells[0].textContent.trim();
        document.getElementById('rejection-reason-preset').value = '';
        document.getElementById('rejection-reason').value = '';
        document.getElementById('reject-modal').showModal();
        return;
    }
    if (actionButton.dataset.listingAction === 'delete' && !window.confirm('Permanently delete this listing and its images?')) return;
    if (actionButton.dataset.listingAction === 'hide' && !window.confirm('Hide this active listing from the marketplace?')) return;

    const buttons = [...row.querySelectorAll('button')];
    buttons.forEach(button => { button.disabled = true; });
    try {
        const action = actionButton.dataset.listingAction;
        const approve = action === 'approve';
        const hidden = action === 'hide';
        const result = await postAdmin(
            approve ? 'php/admin/update-listing-status.php' : (hidden ? 'php/admin/update-listing-status.php' : 'php/admin/delete-listing.php'),
            approve ? { listing_id: row.dataset.listingId, status: 'active' } : (hidden ? { listing_id: row.dataset.listingId, status: 'hidden', rejection_reason: 'Hidden by moderator after review.' } : { listing_id: row.dataset.listingId })
        );
        row.remove();
        if (approve) updateCount('approved-count', 1);
        if (hidden) updateCount('approved-count', -1);
        updateCount('pending-count', -1);
        applyQueueFilters();
        showToast(result.message);
    } catch (error) {
        showToast(error.message || 'Action failed.');
    } finally {
        buttons.forEach(button => { button.disabled = false; });
    }
});

document.getElementById('reject-form').addEventListener('submit', async event => {
    event.preventDefault();
    if (!rejectingRow) return;
    if (!window.confirm('Reject this listing and hide it from the marketplace?')) return;
    const reason = document.getElementById('rejection-reason').value.trim();
    if (!reason) {
        showToast('Please provide a rejection reason.');
        return;
    }
    const buttons = [...rejectingRow.querySelectorAll('button')];
    const submit = document.getElementById('reject-submit');
    buttons.forEach(button => { button.disabled = true; });
    submit.disabled = true;
    try {
        const result = await postAdmin('php/admin/update-listing-status.php', {
            listing_id: rejectingRow.dataset.listingId,
            status: 'hidden',
            rejection_reason: reason
        });
        rejectingRow.remove();
        updateCount('pending-count', -1);
        applyQueueFilters();
        document.getElementById('reject-modal').close();
        showToast(result.message);
        rejectingRow = null;
    } catch (error) {
        showToast(error.message || 'Action failed.');
    } finally {
        buttons.forEach(button => { button.disabled = false; });
        submit.disabled = false;
    }
});

function openReport(reportId) {
    const report = reportsById.get(String(reportId));
    if (!report) return;
    activeReportId = String(report.id);
    const previewImage = report.listing_image
        ? `<img src="${escapeHtml(report.listing_image)}" alt="${escapeHtml(report.title)}">`
        : '<span class="item-thumb" aria-hidden="true"></span>';
    document.getElementById('report-modal-body').innerHTML = `
        <div class="detail-grid">
            <div><strong>Reason</strong><p>${escapeHtml(report.reason)}</p></div>
            <div><strong>Reporter</strong><p>${escapeHtml(report.reporter_name)}</p></div>
            <div><strong>Submitted</strong><p>${formatDate(report.created_at)}</p></div>
            <div><strong>Report status</strong><p>${statusBadge(report.status)}</p></div>
        </div>
        <p class="detail-label" style="margin-top:14px">Description</p><p class="report-description">${escapeHtml(report.description || 'No description provided.')}</p>
        <div class="report-preview">${previewImage}<div><strong>${escapeHtml(report.title)}</strong><p>Seller: ${escapeHtml(report.seller_name)}</p><p>Current listing status: ${statusBadge(report.listing_status)}</p></div></div>
        <label class="detail-label" for="report-admin-note" style="margin-top:14px">Admin note (optional)</label><textarea class="modal-field" id="report-admin-note" maxlength="5000"></textarea>`;
    const actions = document.getElementById('report-modal-actions');
    actions.innerHTML = report.status === 'pending'
        ? `<button class="action dismiss" type="button" data-report-id="${escapeHtml(report.id)}" data-report-action="dismiss">Dismiss</button><button class="action review" type="button" data-report-id="${escapeHtml(report.id)}" data-report-action="review">Mark Reviewed</button><button class="action resolve" type="button" data-report-id="${escapeHtml(report.id)}" data-report-action="resolve_hide">Resolve &amp; Hide Listing</button>`
        : '<button class="action view" type="button" data-close-modal>Close</button>';
    actions.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => document.getElementById('report-modal').close()));
    document.getElementById('report-modal').showModal();
}

document.querySelector('#reports-table tbody').addEventListener('click', event => {
    const view = event.target.closest('[data-report-view]');
    if (view) openReport(view.dataset.reportView);
});

document.querySelector('#listings-table tbody').addEventListener('click', async event => {
    const viewButton = event.target.closest('[data-listing-view]');
    const actionButton = event.target.closest('[data-listing-action]');
    if (viewButton) {
        const response = await fetch(`php/listings/detail.php?id=${encodeURIComponent(viewButton.dataset.listingView)}`, { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) {
            showToast(result.message || 'Unable to open listing.');
            return;
        }
        const listing = result.listing;
        const images = (listing.images || []).map(path => `<img src="${escapeHtml(path)}" alt="${escapeHtml(listing.title)}">`).join('');
        document.getElementById('listing-modal-body').innerHTML = `
            <div class="modal-images">${images || '<p>No images provided.</p>'}</div>
            <div class="detail-grid">
                <div><strong>Title</strong><p>${escapeHtml(listing.title)}</p></div>
                <div><strong>Price</strong><p>PHP ${escapeHtml(Number(listing.price).toFixed(2))}</p></div>
                <div><strong>Seller</strong><p>${escapeHtml(listing.seller_name)}</p></div>
                <div><strong>Category</strong><p>${escapeHtml(listing.category)}</p></div>
                <div><strong>Status</strong><p>${statusBadge(listing.status)}</p></div>
                <div><strong>Campus</strong><p>${escapeHtml(listing.campus)}</p></div>
                <div><strong>Description</strong><p>${escapeHtml(listing.description)}</p></div>
            </div>
        `;
        document.getElementById('listing-modal').showModal();
        return;
    }
    if (!actionButton) return;
    const row = actionButton.closest('tr');
    if (actionButton.dataset.listingAction === 'hide') {
        if (!window.confirm('Hide this active listing from the marketplace?')) return;
        try {
            const result = await postAdmin('php/admin/update-listing-status.php', {
                listing_id: row.dataset.listingId,
                status: 'hidden',
                rejection_reason: 'Hidden by administrator after review.'
            });
            row.remove();
            showToast(result.message);
        } catch (error) {
            showToast(error.message || 'Action failed.');
        }
    }
    if (actionButton.dataset.listingAction === 'delete') {
        if (!window.confirm('Permanently delete this listing and its images?')) return;
        try {
            const result = await postAdmin('php/admin/delete-listing.php', { listing_id: row.dataset.listingId });
            row.remove();
            showToast(result.message);
        } catch (error) {
            showToast(error.message || 'Action failed.');
        }
    }
});

async function handleReportAction(button) {
    const reportId = String(button.dataset.reportId || button.closest('tr')?.dataset.reportId || '');
    const row = button.closest('tr') || document.querySelector(`#reports-table tbody tr[data-report-id="${CSS.escape(reportId)}"]`);
    if (!row || !reportId) return;
    const action = button.dataset.reportAction;
    if ((action === 'dismiss' || action === 'resolve_hide') && !window.confirm(
        action === 'dismiss' ? 'Dismiss this report as invalid?' : 'Resolve this report and hide the listing?'
    )) return;

    const note = document.getElementById('report-modal').open && activeReportId === reportId
        ? (document.getElementById('report-admin-note')?.value || '').trim()
        : '';
    const buttons = [...row.querySelectorAll('button'), ...document.querySelectorAll('#report-modal-actions button')];
    buttons.forEach(item => { item.disabled = true; });
    try {
        const result = await postAdmin('php/admin/update-report-status.php', {
            report_id: reportId, action, admin_note: note
        });
        const affectedRows = action === 'resolve_hide'
            ? [...document.querySelectorAll(`#reports-table tbody tr[data-listing-id="${CSS.escape(String(result.listing_id))}"]`)]
            : [row];
        affectedRows.forEach(item => {
            item.dataset.reportStatus = result.status;
            item.querySelector('[data-report-status-cell]').innerHTML = statusBadge(result.status);
            const viewButton = item.querySelector('[data-report-view]');
            item.querySelector('td:last-child .actions').innerHTML = viewButton ? viewButton.outerHTML : '';
            const report = reportsById.get(String(item.dataset.reportId));
            if (report) {
                report.status = result.status;
                if (action === 'resolve_hide') report.listing_status = 'hidden';
            }
        });
        updateCount('reported-count', -Number(result.affected_reports || 1));
        if (action === 'resolve_hide') {
            if (result.listing_previous_status === 'active') updateCount('approved-count', -1);
            if (result.listing_previous_status === 'draft') {
                updateCount('pending-count', -1);
                document.querySelector(`#pending-queue-table tbody tr[data-listing-id="${CSS.escape(String(result.listing_id))}"]`)?.remove();
                applyQueueFilters();
            }
        }
        applyReportFilters();
        if (activeReportId === reportId) {
            document.getElementById('report-modal').close();
            activeReportId = null;
        }
        showToast(result.message);
    } catch (error) {
        showToast(error.message || 'Action failed.');
    } finally {
        buttons.forEach(item => { item.disabled = false; });
    }
}

document.querySelector('#reports-table tbody').addEventListener('click', event => {
    const button = event.target.closest('[data-report-action]');
    if (button) handleReportAction(button);
});
document.getElementById('report-modal-actions').addEventListener('click', event => {
    const button = event.target.closest('[data-report-action]');
    if (button) handleReportAction(button);
});

showSection(window.location.hash.slice(1) || 'dashboard');
</script>
</main>

<script>
document
    .getElementById("admin-logout-btn")
    .addEventListener("click", async function (event) {

        event.preventDefault();

        try {

            const response = await fetch(
                "php/auth/logout.php",
                {
                    method: "POST",
                    headers: { "X-CSRF-Token": csrfToken }
                }
            );

            const result = await response.json();

            if (result.success) {

                window.location.href = "index.php";

            } else {

                alert(
                    result.message ||
                    "Unable to log out."
                );

            }

        } catch (error) {

            alert(
                "The server is unavailable."
            );

        }

    });
</script>

</body>
</html>
</body>
</html>
