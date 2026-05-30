<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROJECT M - Financial Hub</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page === 'login.php' || $current_page === 'register.php') {
        echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/auth.css">';
    } elseif ($current_page === 'index.php' || $current_page === 'about.php' || $current_page === 'dev.php') {
        echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/index.css">';
    } elseif ($current_page === 'dashboard.php') {
        echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/dashboard.css">';
    }
    ?>
</head>
<body>

<nav class="navbar">
    <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">PROJECT<span>M</span></a>
    <div class="nav-links">
        <a href="<?= BASE_URL ?>/index.php" class="nav-link">Home</a>
        <a href="<?= BASE_URL ?>/about.php" class="nav-link">About</a>
        <a href="<?= BASE_URL ?>/dev.php" class="nav-link">Dev</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link btn btn-glass">Dashboard</a>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="nav-link">Login</a>
            <a href="<?= BASE_URL ?>/register.php" class="nav-link btn btn-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>

<main class="container">
