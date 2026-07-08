<?php
$pageTitle = 'Our Doctors';
$metaDesc = 'Meet our team of experienced and qualified doctors at Almas Hospital.';
require_once 'includes/header.php';
$departmentId = isset($_GET['department']) ? (int)$_GET['department'] : null;
$doctors = getActiveDoctors($departmentId);
$departments = getActiveDepartments();
?>
<section class="page-header">
    <div class="container">
        <h1>Our <strong>Doctors</strong></h1>
        <p>Meet our team of experienced medical professionals</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <form method="GET" class="text-center mb-30">
            <i class="fas fa-filter"></i>
            <select name="department" class="form-control d-inline-block w-auto" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $departmentId == $dept['id'] ? 'selected' : '' ?>><?= sanitizeInput($dept['department_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="row">
            <?php if (count($doctors) > 0): ?>
                <?php foreach ($doctors as $doc): ?>
                <div class="col-4">
                    <div class="card doctor-card">
                        <?php if ($doc['photo']): ?>
                        <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>" class="card-img">
                        <?php else: ?>
                        <div class="card-img" style="background:var(--light-bg);display:flex;align-items:center;justify-content:center;font-size:40px;color:var(--primary);"><i class="fas fa-user-md"></i></div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?= sanitizeInput($doc['name']) ?></h3>
                            <p class="designation"><?= sanitizeInput($doc['designation'] ?? '') ?></p>
                            <p class="card-text"><?= sanitizeInput($doc['qualification']) ?></p>
                            <p class="card-text"><strong><?= sanitizeInput($doc['specialization']) ?></strong></p>
                            <?php if ($doc['experience']): ?><p class="card-text"><i class="fas fa-briefcase"></i> Experience: <?= sanitizeInput($doc['experience']) ?></p><?php endif; ?>
                            <a href="<?= SITE_URL ?>/doctor.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-md"></i> View Profile</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p><i class="fas fa-info-circle"></i> No doctors found for this department.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
