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
if (!$doc) { header('Location: doctors.php'); exit; }
$pageTitle = sanitizeInput($doc['name']);
?>
<section class="section">
    <div class="container">
        <div class="doctor-profile-card">
            <div class="doctor-profile-photo">
                <?php if ($doc['photo']): ?>
                <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>">
                <?php else: ?>
                <div class="doctor-profile-photo-placeholder"><i class="fas fa-user-md"></i></div>
                <?php endif; ?>
            </div>
            <div class="doctor-profile-info">
                <div class="doctor-profile-header">
                    <h1 class="doctor-profile-name"><?= sanitizeInput($doc['name']) ?></h1>
                    <?php if ($doc['designation']): ?>
                    <p class="doctor-profile-designation"><?= sanitizeInput($doc['designation']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="doctor-profile-details">
                    <div class="doctor-detail-item">
                        <div class="doctor-detail-icon"><i class="fas fa-building"></i></div>
                        <div>
                            <span class="doctor-detail-label">Department</span>
                            <span class="doctor-detail-value"><?= sanitizeInput($doc['department_name'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="doctor-detail-item">
                        <div class="doctor-detail-icon"><i class="fas fa-stethoscope"></i></div>
                        <div>
                            <span class="doctor-detail-label">Specialization</span>
                            <span class="doctor-detail-value"><?= sanitizeInput($doc['specialization']) ?></span>
                        </div>
                    </div>
                    <div class="doctor-detail-item">
                        <div class="doctor-detail-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <span class="doctor-detail-label">Qualification</span>
                            <span class="doctor-detail-value"><?= sanitizeInput($doc['qualification']) ?></span>
                        </div>
                    </div>
                    <?php if ($doc['experience']): ?>
                    <div class="doctor-detail-item">
                        <div class="doctor-detail-icon"><i class="fas fa-briefcase"></i></div>
                        <div>
                            <span class="doctor-detail-label">Experience</span>
                            <span class="doctor-detail-value"><?= sanitizeInput($doc['experience']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($doc['profile']): ?>
                <div class="doctor-profile-bio">
                    <h3>About</h3>
                    <div class="content-area"><?= $doc['profile'] ?></div>
                </div>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/appointment.php?doctor=<?= $doc['id'] ?>" class="btn btn-primary btn-lg doctor-profile-cta">
                    <i class="fas fa-calendar-check"></i> Book Appointment
                </a>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
