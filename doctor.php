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
<section class="page-header">
    <div class="container">
        <h1>Doctor <strong>Profile</strong></h1>
        <p>Detailed profile of <?= sanitizeInput($doc['name']) ?></p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="row">
            <div class="col-4">
                <?php if ($doc['photo']): ?>
                <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>" class="detail-img">
                <?php endif; ?>
            </div>
            <div class="col-8">
                <h2><?= sanitizeInput($doc['name']) ?></h2>
                <p class="designation"><?= sanitizeInput($doc['designation'] ?? '') ?></p>
                <hr>
                <table class="table">
                    <tr>
                        <th><i class="fas fa-building"></i> Department</th>
                        <td><?= sanitizeInput($doc['department_name'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-stethoscope"></i> Specialization</th>
                        <td><?= sanitizeInput($doc['specialization']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-graduation-cap"></i> Qualification</th>
                        <td><?= sanitizeInput($doc['qualification']) ?></td>
                    </tr>
                    <?php if ($doc['experience']): ?>
                    <tr>
                        <th><i class="fas fa-briefcase"></i> Experience</th>
                        <td><?= sanitizeInput($doc['experience']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php if ($doc['profile']): ?>
                <hr>
                <div class="content-area"><?= $doc['profile'] ?></div>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/appointment.php?doctor=<?= $doc['id'] ?>" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Book Appointment</a>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
