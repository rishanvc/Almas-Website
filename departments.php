<?php
$pageTitle = 'Departments';
$metaDesc = 'Explore all departments at Almas Hospital with detailed information about facilities and services.';
require_once 'includes/header.php';

$perPage = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalDepts = countActiveDepartments();
$totalPages = ceil($totalDepts / $perPage);
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
$departments = getActiveDepartmentsPaginated($page, $perPage);
?>
<section class="page-header">
    <div class="container">
        <h1>Our Specialities</h1>
        <p>Comprehensive medical care across all specialties</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($departments) > 0): ?>
        <div class="row">
            <?php foreach ($departments as $dept): ?>
            <div class="col-4 animate-in">
                <div class="card">
                    <?php if ($dept['image']): ?>
                    <div class="card-img-wrapper">
                        <img src="<?= SITE_URL . '/' . sanitizeInput($dept['image']) ?>" alt="<?= sanitizeInput($dept['department_name']) ?>" class="card-img">
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title"><?= sanitizeInput($dept['department_name']) ?></h3>
                        <p class="card-text"><?= substr(strip_tags($dept['description']), 0, 150) ?>...</p>
                        <a href="<?= SITE_URL ?>/department.php?id=<?= $dept['id'] ?>" class="btn btn-sm btn-primary">Explore</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center">
            <p>Department information coming soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
