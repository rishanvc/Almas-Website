<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
logoutUser();
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
