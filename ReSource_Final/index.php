<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ReSource | TIP Campus Marketplace</title>

<meta name="description" content="ReSource is a campus marketplace for TIP students to buy, sell, save, and connect.">
<meta name="theme-color" content="#f6b429">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="style.css">
</head>

<body>

<!-- =========================
     PRELOADER
========================= -->

<div id="preloader" class="preloader">
    <div class="loader-content">
        <div class="loader-logo">
            <img src="tiplogo.png" alt="TIP Logo">
        </div>

        <div class="loader-line"></div>

        <p>Loading ReSource...</p>
    </div>
</div>

<!-- =========================
     TOP HEADER
========================= -->

<header class="site-header">

<button class="menu-toggle-btn header-control-btn" id="menu-toggle-btn" type="button" aria-label="Open navigation menu" aria-controls="site-sidebar" aria-expanded="false">
    <span class="menu-toggle-icon" aria-hidden="true">
        <i></i>
        <i></i>
        <i></i>
    </span>
    <span class="menu-toggle-label">Menu</span>
</button>

<div class="header-brand" data-goto="dashboard">

    <div class="header-logo">
        <img src="tiplogo.png" alt="TIP Logo">
    </div>

    <div class="header-brand-text">
        <div class="header-title">
            <span>RE</span>SOURCE
        </div>
        <div class="header-subtitle">
            TIP CAMPUS MARKETPLACE
        </div>
    </div>

</div>




<div id="top-header-actions" class="top-header-actions">

    <button class="header-login-btn header-control-btn" id="top-login-btn">
        Log In
    </button>

    <button class="header-signup-btn header-control-btn" id="top-signup-btn">
        Sign Up
    </button>

    <button class="header-profile-btn header-control-btn" id="top-profile-btn" type="button">
        <span class="profile-button-avatar" aria-hidden="true">👤</span>
        <span>Profile</span>
    </button>

</div>


</header>

<!-- =========================
     APPLICATION
========================= -->

<div class="app">


<!-- =========================
     SIDEBAR
========================= -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="sidebar" id="site-sidebar" aria-label="Main navigation">

    <div class="sidebar-menu-header">
        <div>
            <span class="sidebar-menu-eyebrow">RESOURCE</span>
            <strong>Navigation Menu</strong>
        </div>
        <button class="sidebar-close-btn" id="sidebar-close-btn" type="button" aria-label="Close navigation menu">×</button>
    </div>

   

    <nav class="nav">

        <a href="#"
           class="nav-item"
           data-page="dashboard">
            <span class="nav-icon" aria-hidden="true">🏠</span>
            Homepage / Dashboard
        </a>


        <a href="#"
           class="nav-item"
           data-page="browse">
            <span class="nav-icon" aria-hidden="true">🔎</span>
            Browse / Search
        </a>

        <a href="#"
           class="nav-item"
           data-page="saved">
            <span class="nav-icon" aria-hidden="true">🔖</span>
            Saved Items
            <span class="nav-count hidden" id="nav-saved-count">0</span>
        </a>

        <a href="#"
           class="nav-item"
           data-page="listing">
            <span class="nav-icon" aria-hidden="true">🏷️</span>
            Listings
        </a>

        <a href="#"
           class="nav-item"
           data-page="create">
            <span class="nav-icon" aria-hidden="true">➕</span>
            Create Listing
        </a>

        <a href="index.php?page=messaging"
           class="nav-item"
           data-page="messaging">
            <span class="nav-icon" aria-hidden="true">💬</span>
            Messaging
        </a>

    </nav>


    <div class="sidebar-info">

        <div class="sidebar-info-title">
            TIP STUDENTS ONLY
        </div>

        <p>
            A safer and simpler way for TIPians
            to buy, sell, and exchange campus resources.
        </p>

    </div>


    <div class="sidebar-decoration" aria-hidden="true">
        <div class="tri tri-gray"></div>
        <div class="tri tri-yellow"></div>
        <div class="tri tri-black"></div>
    </div>

</aside>



<!-- =========================
     MAIN CONTENT
========================= -->
<main class="main">


    <!-- =========================
         DASHBOARD
    ========================= -->
<section class="page" id="page-dashboard">
        <div class="breadcrumb">
            HOMEPAGE / DASHBOARD
        </div>


        <div class="card dashboard-card">

            <div class="topbar">

                <div class="pill-badge">
                    RESOURCE
                </div>

                <div class="searchbar">
                    <span class="search-icon">⌕</span>
                    <input
                        type="text"
                        id="dashboard-search-input"
                        placeholder="Search books, electronics, uniforms, tools..."
                    >
                </div>

            </div>


            <hr class="divider">


            <div class="hero-section">

                <div class="hero-copy">

                    <div class="hero-eyebrow">
                        TIP CAMPUS MARKETPLACE
                    </div>

                    <h1 class="welcome-title" id="welcome-title">
                        Welcome to ReSource!
                    </h1>

                    <p class="welcome-sub" id="welcome-sub">
                        Find what you need, list what you have,
                        and connect with fellow TIPians.
                    </p>


                    <div class="btn-row">

                        <button
                            class="btn btn-dark"
                            data-goto="browse">
                            Browse Products
                        </button>

                        <button
                            class="btn btn-outline"
                            data-goto="create">
                            Sell an Item
                        </button>

                    </div>

                </div>


                <div class="hero-visual">

                    <div class="hero-card hero-card-back"></div>

                    <div class="hero-card hero-card-main">

                        <div class="hero-card-top">
                            <span>RESOURCE</span>
                            <span>TIP</span>
                        </div>

                        <div class="hero-card-icon">
                            ↗
                        </div>

                        <div class="hero-card-title">
                            Buy. Sell.
                            <br>
                            Connect.
                        </div>

                        <div class="hero-card-footer">
                            Built for TIPians.
                        </div>

                    </div>

                </div>

            </div>


            <h2 class="section-title">
                Categories
            </h2>


            <div class="categories">

                <div class="category-item"
                     data-category="Books"
                     data-goto="browse">

                    <div class="cat-icon">📖</div>

                    <div class="cat-text">
                        <div class="cat-name">Books</div>
                        <div class="cat-sub">
                            Textbooks & References
                        </div>
                    </div>

                    <span class="chevron">›</span>

                </div>


                <div class="category-item"
                     data-category="Electronics"
                     data-goto="browse">

                    <div class="cat-icon">💻</div>

                    <div class="cat-text">
                        <div class="cat-name">Electronics</div>
                        <div class="cat-sub">
                            Devices & Accessories
                        </div>
                    </div>

                    <span class="chevron">›</span>

                </div>


                <div class="category-item"
                     data-category="Laboratory"
                     data-goto="browse">

                    <div class="cat-icon">🔧</div>

                    <div class="cat-text">
                        <div class="cat-name">Laboratory</div>
                        <div class="cat-sub">
                            Equipment & Tools
                        </div>
                    </div>

                    <span class="chevron">›</span>

                </div>


                <div class="category-item"
                     data-category="Uniforms"
                     data-goto="browse">

                    <div class="cat-icon">👕</div>

                    <div class="cat-text">
                        <div class="cat-name">Uniforms</div>
                        <div class="cat-sub">
                            Uniforms & PE
                        </div>
                    </div>

                    <span class="chevron">›</span>

                </div>

            </div>


            <div class="section-heading-row">

                <div>
                    <h2 class="section-title">
                        Recently Listed
                    </h2>

                    <p class="section-description">
                        Fresh listings from the ReSource community.
                    </p>
                </div>

                <button
                    class="text-button"
                    data-goto="browse">
                    View all →
                </button>

            </div>


            <div
                class="item-grid"
                id="recently-listed-grid">
            </div>

        </div>

    </section>



    <!-- =========================
         LOGIN / SIGNUP
    ========================= -->
<section class="page active" id="page-login">

        <div class="breadcrumb">
            ACCOUNT / LOG-IN & SIGN-UP
        </div>


        <div class="card split-card">

            <div class="split-left">

               
                <h1 class="brand-lockup">
                    <span class="brand-re">RE</span><span class="brand-source">SOURCE</span>
                </h1>

                <div class="brand-bar bar-black"></div>
                <div class="brand-bar bar-yellow"></div>

                <p class="tagline">
                    Your campus marketplace.
                </p>

                <p class="auth-description">
                    A dedicated marketplace for TIP students
                    to discover useful resources, sell pre-loved
                    items, and connect with fellow students.
                </p>


                <div class="auth-benefits">

                    <div>
                        <span>✓</span>
                        TIP student-focused marketplace
                    </div>

                    <div>
                        <span>✓</span>
                        Search and browse listings
                    </div>

                    <div>
                        <span>✓</span>
                        Message other students
                    </div>

                </div>

            </div>


            <div class="split-right">

                <div class="brand-lockup-box">

                    <div class="auth-form-eyebrow">
                        WELCOME TO RESOURCE
                    </div>

                    <h2>
                        Access your account
                    </h2>

                    <p>
                        Log in or create your TIP student account.
                    </p>

                </div>


                <div class="toggle-row">

                    <button
                        class="toggle-btn active"
                        id="tab-login">
                        Log In
                    </button>

                    <button
                        class="toggle-btn"
                        id="tab-signup">
                        Sign Up
                    </button>

                </div>



                <!-- LOGIN FORM -->
                <form id="login-form">

                    <label class="field-label" for="login-role">
                        Account Type <span>*</span>
                    </label>

                    <div class="input-with-icon">
                        <span class="icon" aria-hidden="true">◇</span>
                        <select id="login-role" required>
                            <option value="student">Student</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <label class="field-label">
                        Institutional Email <span>*</span>
                    </label>

                    <div class="input-with-icon">
                        <span class="icon">✉</span>

                        <input
                            type="email"
                            id="login-email"
                            placeholder="you@tip.edu.ph"
                            autocomplete="email"
                            required>
                    </div>


                    <label class="field-label">
                        Password <span>*</span>
                    </label>

                    <div class="input-with-icon">

                        <span class="icon">🔒</span>

                        <input
                            type="password"
                            id="login-password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required>

                        <button
                            type="button"
                            class="icon-toggle"
                            id="toggle-password">
                            👁
                        </button>

                    </div>


                    <a
                        href="#"
                        class="forgot-link"
                        id="forgot-password-link">
                        Forgot Password?
                    </a>


                    <button
                        type="submit"
                        class="btn btn-block btn-black">
                        Log In
                    </button>


                    <p class="fineprint">
                        Only TIP institutional email addresses are accepted.
                    </p>

                </form>



                <!-- SIGNUP FORM -->
                <form
                    id="signup-form"
                    class="hidden">

                    <div class="form-section-heading">
                        Personal Information
                    </div>


                    <div class="field-row">

                        <div class="field-col">

                            <label class="field-label">
                                First Name <span>*</span>
                            </label>

                            <input
                                class="text-input"
                                id="signup-first-name"
                                type="text"
                                placeholder="Juan"
                                autocomplete="given-name"
                                required>

                        </div>


                        <div class="field-col">

                            <label class="field-label">
                                Middle Name <span class="optional-label">(Optional)</span>
                            </label>

                            <input
                                class="text-input"
                                id="signup-middle-name"
                                type="text"
                                placeholder="Dela"
                                autocomplete="additional-name">

                        </div>

                    </div>


                    <label class="field-label">
                        Last Name <span>*</span>
                    </label>

                    <input
                        class="text-input"
                        id="signup-last-name"
                        type="text"
                        placeholder="Cruz"
                        autocomplete="family-name"
                        required>


                    <label class="field-label">
                        Student ID <span>*</span>
                    </label>

                    <input
                        class="text-input"
                        id="signup-student-id"
                        type="text"
                        placeholder="Enter your TIP Student ID"
                        autocomplete="off"
                        minlength="5"
                        maxlength="20"
                        required>

                    <p class="input-help">
                        Enter the student ID assigned to you by TIP.
                    </p>


                    <div class="form-section-heading account-heading">
                        Account Information
                    </div>


                    <label class="field-label">
                        Institutional Email <span>*</span>
                    </label>

                    <div class="input-with-icon">

                        <span class="icon">✉</span>

                        <input
                            id="signup-email"
                            class="text-input"
                            type="email"
                            placeholder="you@tip.edu.ph"
                            autocomplete="email"
                            required>

                    </div>


                    <label class="field-label">
                        Password <span>*</span>
                    </label>

                    <div class="input-with-icon">

                        <span class="icon">🔒</span>

                        <input
                            id="signup-password"
                            class="text-input"
                            type="password"
                            placeholder="Create a password"
                            autocomplete="new-password"
                            minlength="6"
                            required>

                    </div>


                    <label class="field-label">
                        Confirm Password <span>*</span>
                    </label>

                    <div class="input-with-icon">

                        <span class="icon">🔒</span>

                        <input
                            id="signup-password2"
                            class="text-input"
                            type="password"
                            placeholder="Confirm your password"
                            autocomplete="new-password"
                            required>

                    </div>


                    <div class="form-section-heading">
                        Student Information
                    </div>


                    <label class="field-label">
                        Course / Department <span>*</span>
                    </label>

                    <input
                        id="signup-course"
                        class="text-input"
                        type="text"
                        placeholder="e.g. BSIT"
                        required>


                    <label class="field-label">
                        Campus <span>*</span>
                    </label>

                    <select
                        id="signup-campus"
                        class="text-input"
                        required>

                        <option value="">
                            Select your campus
                        </option>

                        <option value="Manila">
                            Manila
                        </option>

                        <option value="Quezon City">
                            Quezon City
                        </option>

                    </select>


                    <label class="terms-check">

                        <input
                            type="checkbox"
                            id="signup-terms"
                            required>

                        <span>
                            I confirm that the information provided is accurate, that I am a TIP student, and that I agree to the
                            <button type="button" class="terms-link" id="open-terms-btn">Terms and Conditions</button>.
                        </span>

                    </label>


                    <button
                        type="submit"
                        class="btn btn-block btn-black">
                        Create Account
                    </button>


                    <p class="fineprint">
                        All fields marked with * are required.
                    </p>

                </form>

            </div>

        </div>

    </section>



    <!-- =========================
         BROWSE
    ========================= -->
    <section class="page" id="page-browse">

        <div class="breadcrumb">
            MARKETPLACE / BROWSE & SEARCH
        </div>


        <div class="card">

            <div class="topbar">

                <div class="pill-badge">
                    MARKETPLACE
                </div>

                <div class="searchbar">

                    <span class="search-icon">⌕</span>

                    <input
                        type="text"
                        id="browse-search-input"
                        placeholder="Search for books, courses, tools...">

                </div>

                <button class="filter-toggle-btn" id="toggle-filters-btn" type="button" aria-controls="listing-filters" aria-expanded="false">
                    <span aria-hidden="true">⚙</span>
                    Filters
                </button>

            </div>


            <hr class="divider">


            <div class="browse-layout">

                <aside class="filter-panel is-collapsed" id="listing-filters">

                    <div class="filter-header">
                        <div>
                            <span class="filter-eyebrow">
                                DISCOVER
                            </span>

                            <h3 class="filter-title">
                                Filter Listings
                            </h3>
                        </div>

                        <span class="filter-icon">
                            ☷
                        </span>
                    </div>


                    <label class="field-label">
                        Campus
                    </label>

                    <select
                        class="filter-input"
                        id="filter-campus">

                        <option value="">Any</option>
                        <option>Manila</option>
                        <option>Quezon City</option>

                    </select>


                    <label class="field-label">
                        Department
                    </label>

                    <select
                        class="filter-input"
                        id="filter-department">

                        <option value="">Any</option>
                        <option>BSIT</option>
                        <option>BSCpE</option>
                        <option>BSCE</option>
                        <option>BSBA</option>
                        <option>Other</option>

                    </select>


                    <label class="field-label">
                        Course Code
                    </label>

                    <input
                        class="filter-input"
                        id="filter-coursecode"
                        type="text"
                        placeholder="e.g. IT21S2">


                    <label class="field-label">
                        Category
                    </label>

                    <select
                        class="filter-input"
                        id="filter-category">

                        <option value="">Any</option>
                        <option>Books</option>
                        <option>Electronics</option>
                        <option>Laboratory</option>
                        <option>Uniforms</option>
                        <option>Other</option>

                    </select>


                    <label class="field-label">
                        Condition
                    </label>

                    <select
                        class="filter-input"
                        id="filter-condition">

                        <option value="">Any</option>
                        <option>New</option>
                        <option>Used - Good</option>
                        <option>Used - Fair</option>

                    </select>


                    <label class="field-label">
                        Price Range
                    </label>

                    <div class="price-range-row">

                        <input
                            class="filter-input"
                            id="filter-min"
                            type="number"
                            min="0"
                            placeholder="Min">

                        <input
                            class="filter-input"
                            id="filter-max"
                            type="number"
                            min="0"
                            placeholder="Max">

                    </div>


                    <label class="field-label">
                        Sort
                    </label>

                    <select
                        class="filter-input"
                        id="filter-sort">

                        <option value="newest">
                            Newest
                        </option>

                        <option value="price-low">
                            Price: Low to High
                        </option>

                        <option value="price-high">
                            Price: High to Low
                        </option>

                        <option value="title">
                            Title: A-Z
                        </option>

                    </select>


                    <button
                        class="btn btn-yellow btn-block"
                        id="apply-filter-btn">
                        Apply Filter
                    </button>

                    <button
                        class="btn btn-gray btn-block filter-reset-btn"
                        id="reset-filter-btn">
                        Reset Filters
                    </button>

                </aside>


                <div class="results-panel">

                    <div class="results-header">

                        <div>
                            <span class="results-eyebrow">
                                CAMPUS MARKETPLACE
                            </span>

                            <p class="results-count">
                                <span id="results-count-num">0</span>
                                Listings found
                            </p>
                        </div>

                    </div>


                    <div
                        class="item-grid highlight-grid"
                        id="browse-grid">
                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- =========================
         SAVED ITEMS
    ========================= -->
    <section class="page" id="page-saved">

        <div class="breadcrumb">
            MARKETPLACE / SAVED ITEMS
        </div>

        <div class="card">

            <div class="topbar">
                <div class="pill-badge">SAVED ITEMS</div>

                <button class="btn btn-pill-gray right-btn" data-goto="browse">
                    ← Continue Browsing
                </button>
            </div>

            <hr class="divider">

            <div id="saved-login-warning" class="notice-box hidden">
                <strong>Log in to see your saved items.</strong>
                <p>Saved listings are available only in this browser.</p>
                <button class="btn btn-black" data-goto="login">Go to Log In</button>
            </div>

            <div id="saved-content" class="hidden">
                <p class="saved-intro">Keep listings here to revisit them later.</p>
                <div class="item-grid four-col saved-items-grid" id="saved-items-grid"></div>
            </div>

        </div>

    </section>


    <!-- =========================
         LISTINGS
    ========================= -->
    <section class="page" id="page-listing">

        <div class="breadcrumb">
            MARKETPLACE / LISTINGS
        </div>


        <div class="card">

            <div class="topbar">

                <div class="pill-badge">
                    LISTING
                </div>

                <button
                    class="btn btn-pill-gray right-btn"
                    data-goto="browse">
                    ← Back to Browse
                </button>

            </div>


            <hr class="divider">


            <div
                id="listing-detail-empty"
                class="empty-state large-empty hidden">

                <div class="empty-icon">
                    ◌
                </div>

                <h3>
                    No listing selected
                </h3>

                <p>
                    Choose a listing from Browse / Search first.
                </p>

                <button
                    class="btn btn-yellow"
                    data-goto="browse">
                    Browse Listings
                </button>

            </div>


            <div
                class="listing-layout hidden"
                id="listing-detail-content">

                <div class="listing-media">

                    <div class="listing-photo-main">

                        <img
                            id="detail-image"
                            src=""
                            alt="Listing photo">

                    </div>

                    <div
                        class="listing-thumbs"
                        id="listing-thumbs">
                    </div>

                </div>


                <div class="listing-info">

                    <div
                        class="tag-row"
                        id="detail-tags">
                    </div>

                    <h2
                        id="detail-title"
                        class="detail-title">
                    </h2>

                    <div
                        class="listing-price"
                        id="detail-price">
                    </div>


                    <div
                        class="seller-box"
                        id="detail-seller-box">

                        <div
                            class="avatar"
                            id="detail-seller-avatar">
                            👤
                        </div>

                        <div class="seller-meta">

                            <div
                                class="seller-name"
                                id="detail-seller-name">
                            </div>

                            <div
                                class="seller-active"
                                id="detail-seller-active">
                            </div>

                            <div class="stars" id="detail-seller-rating">
                            </div>

                        </div>

                        <span class="seller-arrow">
                            →
                        </span>

                    </div>


                    <h3 class="desc-title">
                        Description
                    </h3>

                    <p
                        class="desc-text"
                        id="detail-description">
                    </p>


                    <div class="listing-meta-grid">

                        <div>
                            <strong>Campus</strong>
                            <span id="detail-campus"></span>
                        </div>

                        <div>
                            <strong>Course</strong>
                            <span id="detail-course"></span>
                        </div>

                        <div>
                            <strong>Posted</strong>
                            <span id="detail-date"></span>
                        </div>

                    </div>


                    <div class="btn-row">

                        <button
                            class="btn btn-yellow"
                            id="message-seller-btn">
                            Message Seller
                        </button>

                        <button
                            class="btn btn-gray"
                            id="save-item-btn">
                            Save Item
                        </button>

                        <button
                            class="btn btn-yellow hidden"
                            id="mark-sold-btn">
                            Mark as Sold
                        </button>

                        <button
                            class="btn btn-outline hidden"
                            id="delete-listing-btn">
                            Delete Listing
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- =========================
         CREATE LISTING
    ========================= -->
    <section class="page" id="page-create">

        <div class="breadcrumb">
            SELL / CREATE LISTING
        </div>


        <div class="card">

            <div class="topbar">

                <div class="pill-badge">
                    SELL AN ITEM
                </div>

                <button
                    class="btn btn-pill-gray right-btn"
                    id="cancel-create-btn">
                    Cancel
                </button>

            </div>


            <hr class="divider">


            <div
                id="create-login-warning"
                class="notice-box hidden">

                <strong>
                    You need to log in first.
                </strong>

                <p>
                    Sign in to your TIP account before creating a listing.
                </p>

                <button
                    class="btn btn-black"
                    data-goto="login">
                    Go to Log In
                </button>

            </div>


            <form
                class="create-layout"
                id="create-listing-form">

                <div class="photos-col">

                    <label class="field-label">
                        Photos
                    </label>

                    <div class="photo-upload-row">

                        <label class="photo-slot">

                            <input
                                type="file"
                                accept="image/*"
                                class="photo-input"
                                hidden>

                            <span class="plus">+</span>

                        </label>


                        <label class="photo-slot">

                            <input
                                type="file"
                                accept="image/*"
                                class="photo-input"
                                hidden>

                            <span class="plus">+</span>

                        </label>


                        <label class="photo-slot">

                            <input
                                type="file"
                                accept="image/*"
                                class="photo-input"
                                hidden>

                            <span class="plus">+</span>

                        </label>

                    </div>


                    <p class="upload-help">
                        Add up to 3 clear photos of the item.
                        Images are saved securely with your listing.
                    </p>


                    <div class="selling-tip">

                        <strong>
                            Selling tip
                        </strong>

                        <p>
                            Clear photos, accurate descriptions,
                            and fair prices help your listing get noticed.
                        </p>

                    </div>

                </div>


                <div class="fields-col">

                    <div class="form-section-heading">
                        Item Information
                    </div>


                    <label class="field-label">
                        Title <span>*</span>
                    </label>

                    <input
                        class="text-input"
                        id="create-title"
                        type="text"
                        placeholder="e.g. Data Structures Textbook 2nd Ed."
                        required>


                    <div class="field-row">

                        <div class="field-col">

                            <label class="field-label">
                                Course Code
                            </label>

                            <input
                                class="text-input"
                                id="create-coursecode"
                                type="text"
                                placeholder="e.g. IT21S2">

                        </div>


                        <div class="field-col">

                            <label class="field-label">
                                Department
                            </label>

                            <input
                                class="text-input"
                                id="create-department"
                                type="text"
                                placeholder="e.g. BSIT">

                        </div>

                    </div>


                    <div class="field-row">

                        <div class="field-col">

                            <label class="field-label">
                                Category <span>*</span>
                            </label>

                            <select
                                class="text-input"
                                id="create-category"
                                required>

                                <option value="">
                                    Select category
                                </option>

                                <option>Books</option>
                                <option>Electronics</option>
                                <option>Laboratory</option>
                                <option>Uniforms</option>
                                <option>Other</option>

                            </select>

                        </div>


                        <div class="field-col">

                            <label class="field-label">
                                Condition <span>*</span>
                            </label>

                            <select
                                class="text-input"
                                id="create-condition"
                                required>

                                <option value="">
                                    Select condition
                                </option>

                                <option>New</option>
                                <option>Used - Good</option>
                                <option>Used - Fair</option>

                            </select>

                        </div>


                        <div class="field-col">

                            <label class="field-label">
                                Price (₱) <span>*</span>
                            </label>

                            <input
                                class="text-input"
                                id="create-price"
                                type="number"
                                min="1"
                                placeholder="e.g. 250"
                                required>

                        </div>

                    </div>


                    <label class="field-label">
                        Description <span>*</span>
                    </label>

                    <textarea
                        class="text-input textarea"
                        id="create-description"
                        placeholder="Add details, highlights, included materials, defects, etc."
                        required></textarea>


                    <label class="field-label">
                        Campus <span>*</span>
                    </label>

                    <select
                        class="text-input"
                        id="create-campus"
                        required>

                        <option value="Manila">
                            Manila
                        </option>

                        <option value="Quezon City">
                            Quezon City
                        </option>

                    </select>


                    <div class="btn-row create-submit-actions">

                        <button
                            type="submit"
                            class="btn btn-yellow">
                            Publish Listing
                        </button>

                        <button
                            type="button"
                            class="btn btn-gray"
                            id="save-draft-btn">
                            Save as Draft
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </section>



    <!-- =========================
         PROFILE
    ========================= -->
    <section class="page" id="page-profile">

        <div class="breadcrumb">
            ACCOUNT / USER PROFILE
        </div>


        <div class="card">

            <div class="topbar">

                <div class="pill-badge">
                    PROFILE
                </div>

                <div class="profile-top-actions right-btn">

                    <button
                        class="btn btn-pill-gray hidden"
                        id="logout-btn">
                        Log Out
                    </button>

                    <button
                        class="btn btn-pill-gray"
                        data-goto="browse">
                        Back to Browse
                    </button>

                </div>

            </div>


            <hr class="divider">


            <div
                id="profile-login-warning"
                class="notice-box hidden">

                <strong>
                    No user is currently logged in.
                </strong>

                <p>
                    Log in or create an account to see a real profile.
                </p>

                <button
                    class="btn btn-black"
                    data-goto="login">
                    Go to Log In
                </button>

            </div>


            <div
                id="profile-content"
                class="hidden">

                <div class="profile-header">

                    <div class="profile-avatar-wrap">
                        <div class="avatar large profile-avatar" id="profile-avatar">
                            👤
                        </div>
                        <label class="change-photo-label" for="profile-photo-input">
                            Change photo
                        </label>
                        <input
                            type="file"
                            id="profile-photo-input"
                            accept="image/*"
                            hidden>
                    </div>

                    <div class="profile-meta">

                        <h2
                            class="profile-name"
                            id="profile-name">
                        </h2>

                        <p
                            class="profile-role"
                            id="profile-role">
                        </p>

                        <p
                            class="profile-course"
                            id="profile-course">
                        </p>

                        <div class="profile-tags">

                            <span
                                class="tag"
                                id="profile-campus">
                            </span>

                            <span
                                class="tag"
                                id="profile-student-id">
                            </span>

                            <span
                                class="tag"
                                id="profile-member-since">
                            </span>

                            <span class="tag" id="profile-seller-rating">
                            </span>

                            <span class="tag" id="profile-buyer-rating">
                            </span>

                        </div>

                    </div>

                </div>


                <div class="profile-tabs">

                    <button
                        class="ptab active"
                        data-tab="listings">
                        My Listings
                    </button>

                    <button
                        class="ptab"
                        data-tab="history">
                        Purchase History
                    </button>

                    <button
                        class="ptab"
                        data-tab="sold">
                        Sold Items
                    </button>

                    <button
                        class="ptab"
                        data-tab="settings">
                        Settings
                    </button>

                </div>


                <div
                    class="profile-panel"
                    id="panel-listings">

                    <div
                        class="item-grid four-col"
                        id="profile-listings-grid">
                    </div>

                </div>


                <div
                    class="profile-panel hidden"
                    id="panel-sold">

                    <div
                        class="item-grid four-col"
                        id="profile-sold-grid">
                    </div>

                </div>


                <div
                    class="profile-panel hidden"
                    id="panel-history">

                    <div id="purchase-history-list" class="purchase-history-list"></div>

                </div>


                <div
                    class="profile-panel hidden"
                    id="panel-settings">

                    <div class="settings-grid">

                        <form class="settings-card" id="course-update-form">
                            <span class="results-eyebrow">ACADEMIC DETAILS</span>
                            <h3>Update your course</h3>
                            <p>Keep the course shown on your ReSource profile current.</p>
                            <label for="profile-course-input">Course / Program</label>
                            <input
                                type="text"
                                id="profile-course-input"
                                placeholder="e.g. BSIT">
                            <button class="btn btn-yellow" type="submit">Save Course</button>
                        </form>

                        <form class="settings-card" id="password-update-form">
                            <span class="results-eyebrow">ACCOUNT SECURITY</span>
                            <h3>Change password</h3>
                            <p>Use at least 6 characters for your new password.</p>
                            <label for="current-password-input">Current password</label>
                            <input type="password" id="current-password-input" autocomplete="current-password">
                            <label for="new-password-input">New password</label>
                            <input type="password" id="new-password-input" autocomplete="new-password">
                            <label for="confirm-password-input">Confirm new password</label>
                            <input type="password" id="confirm-password-input" autocomplete="new-password">
                            <button class="btn btn-black" type="submit">Update Password</button>
                        </form>

                        <form class="settings-card" id="notification-settings-form">
                            <span class="results-eyebrow">NOTIFICATIONS</span>
                            <h3>Notification settings</h3>
                            <p>Choose which browser notifications you would like to receive.</p>
                            <label class="setting-check"><input type="checkbox" id="notify-messages"> New messages</label>
                            <label class="setting-check"><input type="checkbox" id="notify-listings"> Listing activity</label>
                            <button class="btn btn-gray" type="submit">Save Notifications</button>
                        </form>

                        <form class="settings-card" id="privacy-settings-form">
                            <span class="results-eyebrow">PRIVACY</span>
                            <h3>Privacy settings</h3>
                            <p>Control which profile details other TIPians can see.</p>
                            <label class="setting-check"><input type="checkbox" id="privacy-photo"> Show my profile photo</label>
                            <label class="setting-check"><input type="checkbox" id="privacy-course"> Show my course</label>
                            <button class="btn btn-gray" type="submit">Save Privacy</button>
                        </form>

                    </div>

                </div>

            </div>

        </div>

    </section>



    <!-- =========================
         MESSAGING
    ========================= -->
    <section class="page" id="page-messaging">

        <div class="breadcrumb">
            COMMUNITY / MESSAGING
        </div>


        <div class="card">

            <div class="topbar">

                <div class="pill-badge">
                    MESSAGES
                </div>

                <button
                    class="btn btn-pill-gray right-btn"
                    data-goto="dashboard">
                    Cancel
                </button>

            </div>


            <hr class="divider">


            <div
                id="message-login-warning"
                class="notice-box hidden">

                <strong>
                    You need to log in first.
                </strong>

                <p>
                    Messaging only works for accounts created in this browser.
                </p>

                <button
                    class="btn btn-black"
                    data-goto="login">
                    Go to Log In
                </button>

            </div>


            <div
                class="messaging-layout"
                id="messaging-content">

                <aside class="conversation-list">

                    <div class="convo-search">

                        <input
                            type="text"
                            id="conversation-search"
                            placeholder="🔍 Search conversations">

                    </div>

                    <div id="conversation-items"></div>

                </aside>


                <div class="chat-panel">

                    <div class="chat-header">

                        <div
                            class="avatar"
                            id="chat-partner-avatar">
                            👤
                        </div>

                        <div>

                            <div
                                class="chat-name"
                                id="chat-partner-name">
                                Select a conversation
                            </div>

                            <div
                                class="chat-status"
                                id="chat-partner-status">
                            </div>

                        </div>

                    </div>


                    <div
                        class="chat-body"
                        id="chat-body">

                        <div class="empty-state">
                            Select a conversation to start messaging.
                        </div>

                    </div>


                    <form
                        class="chat-input-row"
                        id="chat-form">

                        <button
                            type="button"
                            class="icon-btn"
                            id="attach-image-btn"
                            title="Attach image">
                            🖼
                        </button>

                        <input
                            type="text"
                            id="chat-input"
                            placeholder="Type a message..."
                            autocomplete="off">

                        <button
                            type="submit"
                            class="btn btn-yellow">
                            Send
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </section>

</main>


</div>

<!-- =========================
     FOOTER
========================= -->

<footer class="site-footer">


<div class="footer-main">

    <div class="footer-brand">

        <div class="footer-logo">
            <span>RE</span>SOURCE
        </div>

        <p>
            A web-based campus marketplace designed
            to help TIP students buy, sell, exchange,
            and connect with fellow TIPians.
        </p>

        <div class="footer-campus-badge">
            TIP STUDENT COMMUNITY
        </div>

    </div>


    <div class="footer-column">

        <h3>
            Marketplace
        </h3>

        <button data-goto="dashboard">
            Home
        </button>

        <button data-goto="browse">
            Browse Listings
        </button>

        <button data-goto="create">
            Sell an Item
        </button>

        <button type="button" data-goto="messaging">
            Messages
        </button>

    </div>


    <div class="footer-column">

        <h3>
            Account
        </h3>

        <button data-goto="login">
            Log In
        </button>

        <button
            data-goto="login"
            data-auth-tab="signup">
            Create Account
        </button>

        <button data-goto="profile">
            My Profile
        </button>

    </div>



<div class="footer-bottom">

    <span>
        © 2026 ReSource. All rights reserved.
    </span>

    <span>
        Made for TIPians • A Secure Student Marketplace
    </span>

</div>


</footer>

<div class="terms-modal hidden" id="terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-title">
    <div class="terms-modal-card">
        <div class="terms-modal-header">
            <h2 id="terms-title">ReSource Terms and Conditions</h2>
            <button type="button" class="terms-close-btn" id="close-terms-btn" aria-label="Close terms and conditions">×</button>
        </div>
        <div class="terms-modal-body">
            <p>By creating an account, you confirm that you are a currently enrolled TIP student and that the information you provide is accurate.</p>
            <p>Use ReSource only for lawful, campus-related buying, selling, exchanging, and communication. Listings must be truthful and must not contain prohibited, unsafe, or misleading items or content.</p>
            <p>Users are responsible for communicating respectfully, verifying listing details, and arranging transactions safely. ReSource is a student marketplace and does not guarantee the quality, availability, or condition of items offered by users.</p>
            <p>Accounts may be restricted for misuse, false information, harassment, scams, or violations of these terms.</p>
        </div>
        <button type="button" class="btn btn-black terms-done-btn" id="terms-done-btn">I Understand</button>
    </div>
</div>

<div
    class="image-lightbox hidden"
    id="image-lightbox"
    role="dialog"
    aria-modal="true"
    aria-label="Image preview">
    <div class="image-lightbox-content">
        <button
            type="button"
            class="image-lightbox-close"
            id="image-lightbox-close"
            aria-label="Close image preview">
            ×
        </button>
        <img id="image-lightbox-image" src="" alt="">
        <p id="image-lightbox-caption"></p>
    </div>
</div>

<input
 type="file"
 id="hidden-attach-input"
 accept="image/*"
 hidden>

<div
    id="toast-container"
    class="toast-container">
</div>

<script src="script.js"></script>

  <script src="backend-integration.js"></script>
</body>
</html>