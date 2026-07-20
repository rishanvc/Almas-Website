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
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <a href="<?= SITE_URL ?>/admin/index.php">
            <?php if (!empty($settings['logo'])): ?>
            <img src="<?= SITE_URL . '/' . sanitizeInput($settings['logo']) ?>" alt="<?= sanitizeInput($settings['website_name'] ?? 'Admin') ?>" class="sidebar-logo-img">
            <?php endif; ?>
            <span class="logo-text"><?= sanitizeInput($settings['website_name'] ?? 'Admin') ?></span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= SITE_URL ?>/admin/index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <?php if (hasRole('Administrator')): ?>
        <a href="<?= SITE_URL ?>/admin/users.php" class="<?= $currentPage == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users-gear"></i> Manage Users</a>
        <?php endif; ?>
        <?php if (hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])): ?>
        <a href="<?= SITE_URL ?>/admin/content.php" class="<?= $currentPage == 'content.php' ? 'active' : '' ?>"><i class="fas fa-file-lines"></i> Website Content</a>
        <a href="<?= SITE_URL ?>/admin/departments.php" class="<?= $currentPage == 'departments.php' ? 'active' : '' ?>"><i class="fas fa-hospital"></i> Departments</a>
        <a href="<?= SITE_URL ?>/admin/doctors.php" class="<?= $currentPage == 'doctors.php' ? 'active' : '' ?>"><i class="fas fa-user-doctor"></i> Doctors</a>
        <a href="<?= SITE_URL ?>/admin/appointments.php" class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="<?= SITE_URL ?>/admin/packages.php" class="<?= $currentPage == 'packages.php' ? 'active' : '' ?>"><i class="fas fa-box-open"></i> Packages</a>
        <a href="<?= SITE_URL ?>/admin/careers.php" class="<?= $currentPage == 'careers.php' ? 'active' : '' ?>"><i class="fas fa-briefcase"></i> Careers</a>
        <a href="<?= SITE_URL ?>/admin/gallery.php" class="<?= $currentPage == 'gallery.php' ? 'active' : '' ?>"><i class="fas fa-images"></i> Gallery</a>
        <a href="<?= SITE_URL ?>/admin/branches.php" class="<?= $currentPage == 'branches.php' ? 'active' : '' ?>"><i class="fas fa-building"></i> Branches</a>
        <a href="<?= SITE_URL ?>/admin/enquiries.php" class="<?= $currentPage == 'enquiries.php' ? 'active' : '' ?>"><i class="fas fa-envelope-open-text"></i> Enquiries</a>
        <?php endif; ?>
        <?php if (hasAnyRole(['Administrator', 'Content Approver'])): ?>
        <a href="<?= SITE_URL ?>/admin/approvals.php" class="<?= $currentPage == 'approvals.php' ? 'active' : '' ?>"><i class="fas fa-clipboard-check"></i> Approvals</a>
        <?php endif; ?>
        <?php if (hasRole('Administrator')): ?>
        <a href="<?= SITE_URL ?>/admin/settings.php" class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-gear"></i> Settings</a>
        <?php endif; ?>
        <hr style="border-color:#e2e8f0;margin:10px 20px;">
        <a href="<?= SITE_URL ?>/admin/logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>
<div class="main">
    <div class="topbar">
        <div>
            <button class="toggle-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
            <h3><?= sanitizeInput($pageTitle ?? 'Dashboard') ?></h3>
        </div>
        <div class="user-info">
            <div class="user-badge">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= sanitizeInput($_SESSION['user_name']) ?></span>
                    <span class="user-role"><?= sanitizeInput($_SESSION['user_role']) ?></span>
                </div>
            </div>
            <a href="<?= SITE_URL ?>"><i class="fas fa-external-link-alt"></i> View Site</a>
            <a href="<?= SITE_URL ?>/admin/logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
    <div class="content">
        <?php displayMessage(); ?>
