<?php
$pageTitle = 'Doctor Profile';
$metaDesc = 'View doctor profile and details.';
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = mysqli_prepare($conn, "SELECT d.*, dep.department_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.id = ? AND d.status = 'Active'");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$doc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$doc) { 
    header('Location: doctors.php'); 
    exit; 
}

$pageTitle = sanitizeInput($doc['name']);
?>

<div class="doctor-profile-wrapper">
    <!-- Breadcrumb & Top Bar -->
    <div class="doctor-profile-topbar">
        <div class="container">
            <div class="doctor-topbar-inner">
                <a href="<?= SITE_URL ?>/doctors.php" class="doctor-back-link">
                    <i class="fas fa-arrow-left"></i> Back to Doctors
                </a>
                <nav class="doctor-breadcrumb">
                    <a href="<?= SITE_URL ?>/">Home</a>
                    <span class="sep">/</span>
                    <a href="<?= SITE_URL ?>/doctors.php">Doctors</a>
                    <span class="sep">/</span>
                    <span class="current"><?= sanitizeInput($doc['name']) ?></span>
                </nav>
            </div>
        </div>
    </div>

    <!-- Main Hero Card Section -->
    <section class="section doctor-profile-section">
        <div class="container">
            <div class="doctor-profile-hero">
                <!-- Left: Photo Container with Badges -->
                <div class="doctor-photo-column">
                    <div class="doctor-photo-card">
                        <?php if (!empty($doc['photo'])): ?>
                            <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>" class="doctor-portrait">
                        <?php else: ?>
                            <div class="doctor-photo-placeholder">
                                <i class="fas fa-user-md"></i>
                            </div>
                        <?php endif; ?>

                        <div class="doctor-availability-badge">
                            <span class="pulse-dot"></span> Available for Consultation
                        </div>

                        <?php if (!empty($doc['experience'])): ?>
                            <div class="doctor-experience-glass-badge">
                                <i class="fas fa-award"></i>
                                <div>
                                    <span class="exp-val"><?= sanitizeInput($doc['experience']) ?></span>
                                    <span class="exp-lbl">Experience</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="doctor-quick-actions">
                        <a href="<?= SITE_URL ?>/appointment.php?doctor=<?= $doc['id'] ?>" class="btn btn-primary btn-block doctor-hero-btn">
                            <i class="fas fa-calendar-check"></i> Book Appointment
                        </a>
                        <a href="<?= SITE_URL ?>/contact.php" class="btn btn-outline btn-block doctor-hero-btn-alt">
                            <i class="fas fa-phone-alt"></i> Contact Hospital
                        </a>
                    </div>
                </div>

                <!-- Right: Doctor Details & Stats -->
                <div class="doctor-info-column">
                    <div class="doctor-header-info">
                        <div class="doctor-title-row">
                            <h1 class="doctor-main-name"><?= sanitizeInput($doc['name']) ?></h1>
                            <span class="doctor-verified-badge" title="Verified Specialist">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </div>

                        <?php if (!empty($doc['designation'])): ?>
                            <p class="doctor-main-designation"><?= sanitizeInput($doc['designation']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($doc['department_name'])): ?>
                            <div class="doctor-dept-pill-wrap">
                                <a href="<?= SITE_URL ?>/department.php?id=<?= $doc['department_id'] ?>" class="doctor-dept-pill">
                                    <i class="fas fa-hospital-user"></i> Department of <?= sanitizeInput($doc['department_name']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Highlight Stats Grid -->
                    <div class="doctor-stats-grid">
                        <div class="doctor-stat-box">
                            <div class="stat-icon-wrap icon-primary">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <div class="stat-text-wrap">
                                <span class="stat-label">Specialization</span>
                                <span class="stat-value"><?= sanitizeInput($doc['specialization']) ?></span>
                            </div>
                        </div>

                        <div class="doctor-stat-box">
                            <div class="stat-icon-wrap icon-accent">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-text-wrap">
                                <span class="stat-label">Qualification</span>
                                <span class="stat-value"><?= sanitizeInput($doc['qualification']) ?></span>
                            </div>
                        </div>

                        <div class="doctor-stat-box">
                            <div class="stat-icon-wrap icon-blue">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-text-wrap">
                                <span class="stat-label">Department</span>
                                <span class="stat-value"><?= sanitizeInput($doc['department_name'] ?? 'General Medicine') ?></span>
                            </div>
                        </div>

                        <?php if (!empty($doc['experience'])): ?>
                        <div class="doctor-stat-box">
                            <div class="stat-icon-wrap icon-gold">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-text-wrap">
                                <span class="stat-label">Experience</span>
                                <span class="stat-value"><?= sanitizeInput($doc['experience']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Doctor Biography / About Section -->
                    <?php if (!empty($doc['profile'])): ?>
                    <div class="doctor-bio-card">
                        <h3 class="doctor-bio-heading">
                            <i class="fas fa-user-md"></i> About <?= sanitizeInput($doc['name']) ?>
                        </h3>
                        <div class="content-area doctor-bio-content">
                            <?= $doc['profile'] ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Bottom Callout Card -->
                    <div class="doctor-booking-banner">
                        <div class="banner-content">
                            <h4><i class="fas fa-user-clock"></i> Schedule a Consultation</h4>
                            <p>Get expert advice and compassionate medical care from <?= sanitizeInput($doc['name']) ?>.</p>
                        </div>
                        <a href="<?= SITE_URL ?>/appointment.php?doctor=<?= $doc['id'] ?>" class="btn btn-primary banner-btn">
                            Book Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
