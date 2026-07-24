<?php require_once __DIR__ . '/config.php'; require_once __DIR__ . '/session.php'; require_once __DIR__ . '/functions.php'; $settings = getSetting(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizeInput($pageTitle ?? 'Welcome') ?> - <?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?></title>
    <meta name="description" content="<?= sanitizeInput($metaDesc ?? 'Almas Hospital - Providing quality healthcare services') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="emergency-bar">
    <span><i class="fas fa-phone-alt"></i> Emergency: <?= sanitizeInput($settings['phone'] ?? '+91 1234567890') ?></span>
    <span><i class="fas fa-clock"></i> 24/7 Service Available</span>
</div>
<div class="top-bar">
    <div class="container">
        <span><i class="fas fa-hospital"></i> Welcome to <?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?></span>
        <div>
            <a href="mailto:<?= sanitizeInput($settings['email'] ?? '') ?>"><i class="fas fa-envelope"></i> <?= sanitizeInput($settings['email'] ?? '') ?></a>
            <a href="tel:<?= sanitizeInput($settings['phone'] ?? '') ?>"><i class="fas fa-phone-alt"></i> <?= sanitizeInput($settings['phone'] ?? '') ?></a>
        </div>
    </div>
</div>
<header class="header" id="siteHeader">
    <div class="container">
        <a href="<?= SITE_URL ?>" class="logo">
            <?php if (!empty($settings['logo'])): ?>
            <img src="<?= SITE_URL . '/' . sanitizeInput($settings['logo']) ?>" alt="<?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?>" class="logo-img">
            <?php endif; ?>
            <span class="logo-text"><span>ALMAS</span><span>HOSPITAL</span></span>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">☰</button>
        <nav class="nav" id="mainNav">
            <a href="<?= SITE_URL ?>" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="<?= SITE_URL ?>/about.php" class="<?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>">About</a>
            <a href="<?= SITE_URL ?>/departments.php" class="<?= basename($_SERVER['PHP_SELF']) == 'departments.php' || basename($_SERVER['PHP_SELF']) == 'department.php' ? 'active' : '' ?>">Departments</a>
            <a href="<?= SITE_URL ?>/doctors.php" class="<?= basename($_SERVER['PHP_SELF']) == 'doctors.php' || basename($_SERVER['PHP_SELF']) == 'doctor.php' ? 'active' : '' ?>">Doctors</a>
            <a href="<?= SITE_URL ?>/homecare.php" class="<?= basename($_SERVER['PHP_SELF']) == 'homecare.php' ? 'active' : '' ?>">Home Care</a>
            
            <a href="<?= SITE_URL ?>/blogs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'blogs.php' ? 'active' : '' ?>">Blogs</a>
            <a href="<?= SITE_URL ?>/careers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'careers.php' ? 'active' : '' ?>">Careers</a>
            <a href="<?= SITE_URL ?>/branches.php" class="<?= basename($_SERVER['PHP_SELF']) == 'branches.php' ? 'active' : '' ?>">Branches</a>
            <a href="<?= SITE_URL ?>/appointment.php" class="btn-nav <?= basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Book Appointment</a>
            <a href="<?= SITE_URL ?>/contact.php" class="<?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>">Contact</a>
        </nav>
    </div>
</header>
<main>
