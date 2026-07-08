<?php
$pageTitle = 'Departments';
$metaDesc = 'Explore all departments at Almas Hospital with detailed information about facilities and services.';
require_once 'includes/header.php';
$departments = getActiveDepartments();
?>
<section class="page-header">
    <div class="container">
        <h1>Our Departments</h1>
        <p>Comprehensive medical care across all specialties</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($departments) > 0): ?>
        <div class="row">
            <?php foreach ($departments as $dept): ?>
            <div class="col-4">
                <div class="card">
                    <?php if ($dept['image']): ?>
                    <img src="<?= SITE_URL . '/' . sanitizeInput($dept['image']) ?>" alt="<?= sanitizeInput($dept['department_name']) ?>" class="card-img">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title"><?= sanitizeInput($dept['department_name']) ?></h3>
                        <p class="card-text"><?= substr(strip_tags($dept['description']), 0, 150) ?>...</p>
                        <a href="<?= SITE_URL ?>/department.php?id=<?= $dept['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center">
            <p>Department information coming soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
