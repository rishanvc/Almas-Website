<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$settings = getSetting();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizeInput($pageTitle ?? 'Dashboard') ?> - Admin | <?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo"><a href="<?= SITE_URL ?>/admin/index.php"><?= sanitizeInput($settings['website_name'] ?? 'Admin') ?></a></div>
    <nav class="sidebar-nav">
        <a href="<?= SITE_URL ?>/admin/index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">Dashboard</a>
        <?php if (hasRole('Administrator')): ?>
        <a href="<?= SITE_URL ?>/admin/users.php" class="<?= $currentPage == 'users.php' ? 'active' : '' ?>">Manage Users</a>
        <?php endif; ?>
        <?php if (hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])): ?>
        <a href="<?= SITE_URL ?>/admin/content.php" class="<?= $currentPage == 'content.php' ? 'active' : '' ?>">Website Content</a>
        <a href="<?= SITE_URL ?>/admin/departments.php" class="<?= $currentPage == 'departments.php' ? 'active' : '' ?>">Departments</a>
        <a href="<?= SITE_URL ?>/admin/doctors.php" class="<?= $currentPage == 'doctors.php' ? 'active' : '' ?>">Doctors</a>
        <a href="<?= SITE_URL ?>/admin/appointments.php" class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>">Appointments</a>
        <a href="<?= SITE_URL ?>/admin/packages.php" class="<?= $currentPage == 'packages.php' ? 'active' : '' ?>">Packages</a>
        <a href="<?= SITE_URL ?>/admin/careers.php" class="<?= $currentPage == 'careers.php' ? 'active' : '' ?>">Careers</a>
        <a href="<?= SITE_URL ?>/admin/gallery.php" class="<?= $currentPage == 'gallery.php' ? 'active' : '' ?>">Gallery</a>
        <a href="<?= SITE_URL ?>/admin/branches.php" class="<?= $currentPage == 'branches.php' ? 'active' : '' ?>">Branches</a>
        <a href="<?= SITE_URL ?>/admin/enquiries.php" class="<?= $currentPage == 'enquiries.php' ? 'active' : '' ?>">Enquiries</a>
        <?php endif; ?>
        <?php if (hasAnyRole(['Administrator', 'Content Approver'])): ?>
        <a href="<?= SITE_URL ?>/admin/approvals.php" class="<?= $currentPage == 'approvals.php' ? 'active' : '' ?>">Approvals</a>
        <?php endif; ?>
        <?php if (hasRole('Administrator')): ?>
        <a href="<?= SITE_URL ?>/admin/settings.php" class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>">Settings</a>
        <?php endif; ?>
        <hr style="border-color:#334155;margin:10px 20px;">
        <a href="<?= SITE_URL ?>/admin/logout.php">Logout</a>
    </nav>
</aside>
<div class="main">
    <div class="topbar">
        <div>
            <button class="toggle-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
            <h3><?= sanitizeInput($pageTitle ?? 'Dashboard') ?></h3>
        </div>
        <div class="user-info">
            <span>Welcome, <?= sanitizeInput($_SESSION['user_name']) ?> (<?= sanitizeInput($_SESSION['user_role']) ?>)</span>
            <a href="<?= SITE_URL ?>">View Site</a>
            <a href="<?= SITE_URL ?>/admin/logout.php">Logout</a>
        </div>
    </div>
    <div class="content">
        <?php displayMessage(); ?>
