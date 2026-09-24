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

$stats = [
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'draft'")->fetchColumn(),
    'approved' => (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
    'reported' => (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

$pending = $pdo->query(
        "SELECT l.id, l.title, l.price, l.created_at,
            (SELECT image_path FROM listing_images li
             WHERE li.listing_id = l.id
             ORDER BY li.sort_order, li.id
             LIMIT 1) AS image_path,
            CONCAT_WS(' ', u.first_name, u.last_name) AS seller_name
     FROM listings l
     JOIN users u ON u.id = l.seller_id
     WHERE l.status = 'draft'
     ORDER BY l.created_at DESC
     LIMIT 6"
)->fetchAll();

function dashboardDate(string $date): string {
    return date('M j, Y', strtotime($date));
}

function dashboardMoney(string|float $price): string {
    return 'PHP ' . number_format((float) $price, 2);
}

$adminInitials = strtoupper(substr((string) $adminUser['first_name'], 0, 1) . substr((string) $adminUser['last_name'], 0, 1));
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
            --black: #151515;
            --black-soft: #222222;
            --white: #ffffff;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #dddddd;
            --gray-400: #bdbdbd;
            --gray-500: #858585;
            --text-dark: #181818;
            --text-muted: #666666;
            --green: #2e7d32;
            --red: #b3261e;
            --radius-lg: 22px;
            --radius-md: 14px;
            --radius-sm: 9px;
            --shadow-sm: 0 4px 14px rgba(0, 0, 0, .06);
            --shadow-md: 0 12px 35px rgba(0, 0, 0, .09);
            --shadow-lg: 0 25px 70px rgba(0, 0, 0, .13);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at 10% 0%, rgba(246, 180, 41, .10), transparent 25%), linear-gradient(180deg, #f7f7f5 0%, #eef0f2 100%);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        button, input, select, textarea { font: inherit; }
        button { cursor: pointer; -webkit-tap-highlight-color: transparent; }
        a { text-decoration: none; }

        .admin-shell {
            display: block;
            min-height: calc(100vh - 76px);
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 30px;
            background: #1c1c1c;
            border-bottom: 1px solid #303030;
            box-shadow: 0 4px 25px rgba(0, 0, 0, .18);
        }

        .header-control-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid #555;
            background: transparent;
            color: #fff;
            border-radius: 999px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: transform .2s ease, background .2s ease, border-color .2s ease;
        }

        .header-control-btn:hover {
            background: #292929;
            border-color: #666;
            transform: translateY(-1px);
        }

        .menu-toggle-btn {
            min-width: 92px;
        }

        .menu-toggle-icon {
            width: 14px;
            height: 12px;
            display: inline-flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .menu-toggle-icon i {
            display: block;
            height: 2px;
            width: 100%;
            border-radius: 999px;
            background: #fff;
        }

        .menu-toggle-label {
            line-height: 1;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 230px;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        .header-logo {
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 9px;
            background: #252525;
            border: 1px solid #3a3a3a;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .header-brand-text { line-height: 1; }

        .header-title {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .8px;
            color: #ffffff;
        }

        .header-title span { color: var(--yellow); }

        .header-subtitle {
            margin-top: 5px;
            color: #999;
            font-size: 8px;
            letter-spacing: 1.2px;
            font-weight: 700;
        }

        .top-title {
            flex: 1;
            min-width: 195px;
            color: #fff;
        }

        .top-title small {
            display: block;
            color: #bbb;
            font-size: 9px;
            font-weight: 700;
        }

        .top-title h1 {
            margin: 4px 0 0;
            font: 700 20px 'Merriweather', serif;
        }

        .search {
            width: min(100%, 350px);
            height: 38px;
            border: 1px solid #555;
            border-radius: 999px;
            background: #292929;
            padding: 0 14px;
            color: #fff;
            outline: none;
        }

        .search::placeholder { color: #b3b3b3; }

        .admin-account { margin-left: auto; }

        .admin-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 5px 13px 5px 6px;
            border: 1px solid #5a5a5a;
            border-radius: 999px;
            background: linear-gradient(135deg, #343434, #202020);
            color: #fff;
            text-decoration: none;
            transition: border-color .2s ease, background .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .admin-profile:hover {
            border-color: var(--yellow);
            background: linear-gradient(135deg, #3c3c3c, #272727);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(246, 180, 41, .12);
        }

        .admin-profile-avatar {
            width: 29px;
            height: 29px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--yellow);
            color: #151515;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .3px;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08);
        }

        .account-name {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.35;
            color: #fff;
        }

        .account-name span {
            display: block;
            font-size: 9px;
            font-weight: 400;
            color: #bbb;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 76px 0 0 0;
            z-index: 910;
            background: rgba(0, 0, 0, .48);
            opacity: 0;
            visibility: hidden;
            transition: opacity .25s ease, visibility .25s ease;
        }

        .sidebar {
            position: fixed;
            top: 76px;
            bottom: 0;
            left: 0;
            z-index: 920;
            width: 310px;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #ffffff 0%, #f7f7f5 100%);
            border-right: 4px solid var(--yellow);
            box-shadow: 18px 0 50px rgba(0, 0, 0, .24);
            transform: translateX(-105%);
            visibility: hidden;
            transition: transform .25s ease, visibility .25s ease;
            overflow-y: auto;
        }

        body.menu-open { overflow: hidden; }
        body.menu-open .sidebar { transform: translateX(0); visibility: visible; }
        body.menu-open .sidebar-overlay { opacity: 1; visibility: visible; }

        .sidebar-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            min-height: 88px;
            padding: 20px 18px;
            border-bottom: 4px solid var(--yellow);
            background: linear-gradient(135deg, #161616, #2b2b2b);
            color: #fff;
        }

        .sidebar-menu-header strong {
            display: block;
            margin-top: 3px;
            font-size: 18px;
            letter-spacing: -.2px;
            color: #fff;
        }

        .sidebar-menu-eyebrow {
            display: block;
            color: var(--yellow);
            font-size: 9px;
            letter-spacing: 1.2px;
            font-weight: 800;
        }

        .sidebar-close-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #555;
            border-radius: 50%;
            background: #303030;
            color: var(--yellow);
            font-size: 25px;
            line-height: 1;
            cursor: pointer;
        }

        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 9px;
            padding: 20px 15px 14px;
        }

        .side-nav a,
        .logout {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 54px;
            padding: 9px 14px 9px 10px;
            border: 1px solid #dfdfdf;
            border-radius: 999px;
            background: #fff;
            color: #2d2d2d;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 13px rgba(0, 0, 0, .05);
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .side-nav a span,
        .logout span {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: #fff1c8;
            color: #1d1d1d;
            font-size: 17px;
        }

        .side-nav a:hover,
        .logout:hover {
            border-color: var(--yellow);
            background: #fffaf0;
            box-shadow: 0 7px 18px rgba(246, 180, 41, .14);
        }

        .side-nav a.active,
        .side-nav a.active:hover {
            border-color: var(--yellow);
            background: var(--yellow);
            box-shadow: 0 8px 22px rgba(246, 180, 41, .24);
            color: #111;
        }

        .side-nav a.active span {
            background: #171717;
            color: #fff;
        }

        .side-label {
            color: #929292;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: .08em;
            margin: 8px 12px 0;
            text-transform: uppercase;
        }

        .logout {
            margin: 8px 15px 0;
        }

        .sidebar-art {
            position: relative;
            height: 110px;
            margin-top: auto;
            background: #b7b7b7;
            clip-path: polygon(0 100%, 100% 0, 100% 100%);
        }

        .sidebar-art::after {
            content: '';
            position: absolute;
            inset: 32% 0 0 32%;
            background: var(--yellow);
            clip-path: polygon(0 100%, 100% 0, 100% 100%);
        }

        .sidebar-info {
            margin: 12px;
            padding: 14px;
            border-radius: 16px;
            background: linear-gradient(135deg, #171717, #282828);
            color: #fff;
        }

        .sidebar-info strong {
            display: block;
            color: var(--yellow);
            font-size: 9px;
            letter-spacing: 1px;
            font-weight: 800;
        }

        .sidebar-info p {
            margin: 7px 0 0;
            color: #d0d0d0;
            font-size: 10px;
            line-height: 1.55;
        }

        main {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px 34px 50px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat {
            min-height: 82px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border: 1px solid #ebebeb;
            border-radius: var(--radius-lg);
            background: var(--white);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #111;
            color: #fff;
            font-size: 22px;
            flex-shrink: 0;
        }

        .stat:nth-child(2) .stat-icon { background: var(--green); }
        .stat:nth-child(3) .stat-icon { background: var(--red); border-radius: 10px; }

        .stat h2 {
            margin: 0 0 3px;
            font-size: 13px;
            color: var(--text-dark);
        }

        .stat p {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: var(--black);
        }

        .admin-section {
            display: none;
            animation: sectionIn .25s ease both;
        }

        .admin-section.active { display: block; }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin: 0 8px 16px;
        }

        .section-heading h2,
        .section-intro h2 {
            margin: 0;
            font-family: 'Merriweather', serif;
            color: var(--text-dark);
        }

        .section-heading h2 { font-size: 18px; }

        .section-heading a {
            color: #bc8500;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .section-intro {
            margin: 0 0 18px;
        }

        .section-intro h2 { font-size: 22px; }

        .section-intro p {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        .table-wrap {
            overflow: hidden;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 13px 16px;
            text-align: left;
            border-bottom: 1px solid #f1f1f1;
            font-size: 12px;
            color: var(--text-dark);
        }

        th {
            padding-top: 14px;
            color: var(--text-muted);
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: rgba(245, 245, 245, .7);
        }

        tr:last-child td { border-bottom: 0; }

        .item-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 190px;
        }

        .item-thumb {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            display: inline-block;
            border-radius: 10px;
            background: #0d0d0d;
            object-fit: cover;
        }

        .item-title {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }

        .item-price {
            margin-top: 2px;
            font-size: 10px;
            color: var(--text-muted);
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action {
            border: 0;
            border-radius: 999px;
            padding: 7px 13px;
            font-size: 11px;
            font-weight: 700;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .action.approve {
            color: var(--green);
            background: #edf7eb;
        }

        .action.reject {
            color: var(--red);
            background: #fdf0f0;
        }

        .action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }

        .status {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: capitalize;
            background: #f1f1f1;
            color: var(--text-dark);
        }

        .status.active { color: #397d00; background: #eef8e7; }
        .status.draft,
        .status.pending { color: #9a6800; background: #fff4d3; }
        .status.sold { color: #555; background: #ededed; }
        .status.hidden,
        .status.dismissed { color: #a52a2a; background: #fcecec; }

        .table-search {
            width: min(100%, 330px);
            height: 38px;
            margin-bottom: 12px;
            padding: 0 16px;
            border: 1px solid #e7e7e7;
            border-radius: 999px;
            background: #fff;
            outline: none;
            box-shadow: var(--shadow-sm);
        }

        .empty {
            padding: 28px;
            color: var(--text-muted);
            text-align: center;
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: #222;
            color: #fff;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 12px;
            opacity: 0;
            transform: translateY(10px);
            transition: .2s ease;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes sectionIn {
            from { opacity: 0; transform: translateY(7px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 800px) {
            .site-header {
                height: auto;
                min-height: 76px;
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .header-brand { min-width: 0; flex: 1; }
            .header-brand-text, .top-title { display: none; }
            .admin-account { margin-left: 0; }
            .account-name { display: none; }
            .search {
                order: 3;
                flex-basis: 100%;
                width: 100%;
                max-width: none;
            }

            .sidebar { width: min(88vw, 310px); }
            main { padding: 24px 16px 35px; }
            .stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        }

        @media (max-width: 440px) {
            .stats { grid-template-columns: 1fr; }
            .account-name { display: none; }
            .section-heading {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<header class="site-header">
    <button class="menu-toggle-btn header-control-btn" type="button" id="menu-toggle" aria-label="Toggle admin navigation" aria-expanded="false">
        <span class="menu-toggle-icon" aria-hidden="true"><i></i><i></i><i></i></span>
        <span class="menu-toggle-label">Menu</span>
    </button>

    <a class="header-brand" href="index.php">
        <div class="header-logo"><img src="tiplogo.png" alt="TIP Logo"></div>
        <div class="header-brand-text"><div class="header-title"><span>RE</span>SOURCE</div><div class="header-subtitle">TIP CAMPUS MARKETPLACE</div></div>
    </a>

    <div class="top-title"><small>TIP CAMPUS MARKETPLACE</small><h1 id="admin-page-title">Admin Dashboard</h1></div>

    <input class="search" type="search" placeholder="Search books, courses, tools..." aria-label="Search dashboard">

    <div class="admin-account">
        <a class="admin-profile" href="index.php?page=profile" aria-label="Open administrator profile">
            <span class="admin-profile-avatar" aria-hidden="true"><?= htmlspecialchars($adminInitials, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="account-name"><?= htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name'], ENT_QUOTES, 'UTF-8') ?><span>Administrator</span></span>
        </a>
    </div>
</header>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-menu-header"><div><span class="sidebar-menu-eyebrow">RESOURCE</span><strong>Navigation Menu</strong></div><button class="sidebar-close-btn" type="button" id="sidebar-close" aria-label="Close navigation menu">&times;</button></div>
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
            <a href="#reports" data-section="reports"><span aria-hidden="true">&#128737;</span>Trust and Safety</a>
            <a href="index.php?page=profile"><span aria-hidden="true">&#128100;</span>User Profile</a>
        </nav>
        <div class="sidebar-info"><strong>TIP STUDENTS ONLY</strong><p>A safer and simpler way for TIPians to buy, sell, and exchange campus resources.</p></div>
        <a class="logout" href="php/auth/logout.php"><span aria-hidden="true">&#8594;</span>Log-out</a>
        <div class="sidebar-art" aria-hidden="true"></div>
    </aside>

    <main>
        <section class="stats" aria-label="Dashboard statistics">
            <article class="stat"><div class="stat-icon">&#9203;</div><div><h2>Pending Listing</h2><p><?= $stats['pending'] ?></p></div></article>
            <article class="stat"><div class="stat-icon">&#10003;</div><div><h2>Approved Listing</h2><p><?= $stats['approved'] ?></p></div></article>
            <article class="stat"><div class="stat-icon">!</div><div><h2>Reported Items</h2><p><?= $stats['reported'] ?></p></div></article>
            <article class="stat"><div class="stat-icon">&#128100;</div><div><h2>Total Users</h2><p><?= $stats['users'] ?></p></div></article>
        </section>

        <section class="admin-section active" id="dashboard">
            <div class="section-heading"><h2>Pending Listing</h2><a href="#listings" data-section="listings">View all listings</a></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Items</th><th>Seller</th><th>Submitted</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (!$pending): ?>
                        <tr><td colspan="4" class="empty">No pending listings right now.</td></tr>
                    <?php else: foreach ($pending as $listing): ?>
                        <tr data-listing-id="<?= (int) $listing['id'] ?>">
                            <td><div class="item-cell"><?php if (!empty($listing['image_path'])): ?><img class="item-thumb" src="<?= htmlspecialchars($listing['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><span class="item-thumb" aria-hidden="true"></span><?php endif; ?><span class="item-title"><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?><span class="item-price"><?= dashboardMoney($listing['price']) ?></span></span></div></td>
                            <td><?= htmlspecialchars($listing['seller_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= dashboardDate($listing['created_at']) ?></td>
                            <td><div class="actions"><button class="action approve" type="button" data-action="approve">Approve</button><button class="action reject" type="button" data-action="reject">Reject</button></div></td>
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
            <div class="section-intro"><h2>Reports &amp; Trust and Safety</h2><p>Monitor reports submitted by students and review the affected listings.</p></div>
            <input class="table-search" type="search" data-filter-target="reports-table" placeholder="Search reports..." aria-label="Search reports">
            <div class="table-wrap"><table id="reports-table"><thead><tr><th>Listing</th><th>Reporter</th><th>Reason</th><th>Description</th><th>Status</th><th>Submitted</th></tr></thead><tbody><tr><td colspan="6" class="empty">Loading reports...</td></tr></tbody></table></div>
        </section>

        <section class="admin-section" id="users">
            <div class="section-intro"><h2>Users</h2><p>View registered TIP marketplace accounts and roles.</p></div>
            <input class="table-search" type="search" data-filter-target="users-table" placeholder="Search users..." aria-label="Search users">
            <div class="table-wrap"><table id="users-table"><thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Course</th><th>Campus</th><th>Role</th></tr></thead><tbody><tr><td colspan="6" class="empty">Loading users...</td></tr></tbody></table></div>
        </section>

    </main>
</div>
<div class="toast" id="toast" role="status"></div>
<script>
const toast = document.getElementById('toast');
function showToast(message) {
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

document.getElementById('menu-toggle').addEventListener('click', () => {
    const isOpen = document.body.classList.toggle('menu-open');
    document.getElementById('menu-toggle').setAttribute('aria-expanded', String(isOpen));
});

document.getElementById('sidebar-close').addEventListener('click', () => {
    document.body.classList.remove('menu-open');
    document.getElementById('menu-toggle').setAttribute('aria-expanded', 'false');
});

document.getElementById('sidebar-overlay').addEventListener('click', () => {
    document.body.classList.remove('menu-open');
    document.getElementById('menu-toggle').setAttribute('aria-expanded', 'false');
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
    return loadAdminData('php/admin/listings.php', 'listings-table', listing => `<tr>
        <td>${escapeHtml(listing.title)}</td>
        <td>${escapeHtml(listing.seller_name)}</td>
        <td>${escapeHtml(listing.category)}</td>
        <td>${escapeHtml(Number(listing.price).toFixed(2))}</td>
        <td>${statusBadge(listing.status)}</td>
        <td>${formatDate(listing.created_at)}</td>
    </tr>`, 'No listings found.', 6);
}

function loadReports() {
    return loadAdminData('php/admin/reports.php', 'reports-table', report => `<tr>
        <td>${escapeHtml(report.title)}</td>
        <td>${escapeHtml(report.reporter_name)}</td>
        <td>${escapeHtml(report.reason)}</td>
        <td>${escapeHtml(report.description || 'No description')}</td>
        <td>${statusBadge(report.status)}</td>
        <td>${formatDate(report.created_at)}</td>
    </tr>`, 'No reports found.', 6);
}

function loadUsers() {
    return loadAdminData('php/admin/users.php', 'users-table', user => `<tr>
        <td>${escapeHtml([user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' '))}</td>
        <td>${escapeHtml(user.student_id)}</td>
        <td>${escapeHtml(user.email)}</td>
        <td>${escapeHtml(user.course)}</td>
        <td>${escapeHtml(user.campus)}</td>
        <td>${statusBadge(user.role)}</td>
    </tr>`, 'No users found.', 6);
}

const loaders = { listings: loadListings, reports: loadReports, users: loadUsers };
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

document.querySelectorAll('[data-filter-target]').forEach(input => input.addEventListener('input', () => {
    const query = input.value.trim().toLowerCase();
    document.querySelectorAll(`#${input.dataset.filterTarget} tbody tr`).forEach(row => {
        row.hidden = !row.textContent.toLowerCase().includes(query);
    });
}));

document.querySelector('.search').addEventListener('input', event => {
    const query = event.target.value.trim().toLowerCase();
    const activeTable = document.querySelector('.admin-section.active table');
    if (!activeTable) return;
    activeTable.querySelectorAll('tbody tr').forEach(row => {
        row.hidden = Boolean(query) && !row.textContent.toLowerCase().includes(query);
    });
});

document.querySelectorAll('[data-action]').forEach((button) => {
    button.addEventListener('click', async () => {
        const row = button.closest('tr');
        const listingId = row.dataset.listingId;
        if (button.dataset.action === 'reject' && !window.confirm('Remove this listing?')) return;
        const isApprove = button.dataset.action === 'approve';
        const endpoint = isApprove ? 'php/admin/update-listing-status.php' : 'php/admin/delete-listing.php';
        const body = new URLSearchParams({ listing_id: listingId });
        if (isApprove) body.append('status', 'active');
        button.disabled = true;
        try {
            const response = await fetch(endpoint, { method: 'POST', body });
            const result = await response.json();
            if (!response.ok || !result.success) { showToast(result.message || 'Action failed.'); return; }
            row.remove();
            showToast(isApprove ? 'Listing approved.' : 'Listing rejected and removed.');
        } catch (error) {
            showToast('Could not connect to the server.');
        } finally {
            button.disabled = false;
        }
    });
});

showSection(window.location.hash.slice(1) || 'dashboard');
</script>
</body>
</html>
