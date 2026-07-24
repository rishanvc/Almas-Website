<?php
$pageTitle = 'Our Doctors';
$metaDesc = 'Meet our team of experienced and qualified doctors at Almas Hospital.';
require_once 'includes/header.php';
$departmentId = isset($_GET['department']) ? (int)$_GET['department'] : null;
$perPage = 9;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalDoctors = countActiveDoctors($departmentId);
$totalPages = max(1, ceil($totalDoctors / $perPage));
$page = min($page, $totalPages);
$doctors = getActiveDoctorsPaginated($departmentId, $page, $perPage);
$departments = getActiveDepartments();

function buildQueryString($overrides) {
    $params = array_filter(array_merge($_GET, $overrides));
    return http_build_query($params);
}
?>
<section class="page-header">
    <div class="container">
        <h1>Our <strong>Doctors</strong></h1>
        <p>Meet our team of experienced medical professionals</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <form method="GET" class="doctor-filter-bar">
            <i class="fas fa-sliders-h doctor-filter-icon"></i>
            <select name="department" class="doctor-filter-select" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $departmentId == $dept['id'] ? 'selected' : '' ?>><?= sanitizeInput($dept['department_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down doctor-filter-chevron"></i>
        </form>
        <div class="row">
            <?php if (count($doctors) > 0): ?>
                <?php foreach ($doctors as $doc): ?>
                <div class="col-4">
                    <div class="card doctor-card">
                        <div class="doctor-card-media">
                            <?php if ($doc['photo']): ?>
                            <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>" class="card-img">
                            <?php else: ?>
                            <div class="card-img doctor-card-placeholder"><i class="fas fa-user-md"></i></div>
                            <?php endif; ?>
                            <div class="doctor-card-overlay">
                                <h3 class="card-title"><?= sanitizeInput($doc['name']) ?></h3>
                                <?php if ($doc['designation']): ?>
                                <p class="designation"><?= sanitizeInput($doc['designation']) ?></p>
                                <?php endif; ?>
                                <a href="<?= SITE_URL ?>/doctor.php?id=<?= $doc['id'] ?>" class="btn-sm"><span>View Profile</span><i class="fas fa-arrow-right"></i></a>
                            </div>
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
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?<?= buildQueryString(['page' => $page - 1]) ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            if ($start > 1): ?>
            <a href="?<?= buildQueryString(['page' => 1]) ?>">1</a>
            <?php if ($start > 2): ?>
            <span>...</span>
            <?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?<?= buildQueryString(['page' => $i]) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
            <span>...</span>
            <?php endif; ?>
            <a href="?<?= buildQueryString(['page' => $totalPages]) ?>"><?= $totalPages ?></a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= buildQueryString(['page' => $page + 1]) ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
