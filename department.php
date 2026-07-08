<?php
$pageTitle = 'Department Details';
$metaDesc = 'View department details and facilities.';
require_once 'includes/header.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = mysqli_prepare($conn, "SELECT * FROM departments WHERE id = ? AND status = 'Active'");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$dept = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$dept) { header('Location: departments.php'); exit; }
$facilities = getDepartmentFacilities($id);
$doctors = getActiveDoctors($id);
$sections = getDepartmentSections($id);
$settings = getSetting();
$pageTitle = sanitizeInput($dept['department_name']);
?>

<?php
// --- Common Section: Banner ---
$bannerImage = $dept['image'] ? SITE_URL . '/' . sanitizeInput($dept['image']) : '';
?>
<section class="dept-banner"<?= $bannerImage ? ' style="background-image:url(' . $bannerImage . ');"' : '' ?>>
</section>

<section class="dept-title-section">
    <div class="container">
        <span class="dept-title-label"><?= sanitizeInput($settings['website_name'] ?? 'ALMAS HOSPITAL') ?></span>
        <h1 class="dept-title-name"><?= sanitizeInput($dept['department_name']) ?></h1>
    </div>
</section>


<?php
// --- Common Section: About the Department ---
?>
<section class="section dept-about">
    <div class="container">
        <h2 class="section-title">About the Department</h2>
        <div class="content-area"><?= $dept['description'] ?></div>
        <div class="dept-cta" style="margin-top:30px;text-align:center;">
            <a href="<?= SITE_URL ?>/appointment.php?department=<?= $id ?>" class="btn btn-primary btn-lg"><i class="fas fa-calendar-check"></i> Book Appointment</a>
            <a href="<?= SITE_URL ?>/contact.php" class="btn btn-outline-primary btn-lg"><i class="fas fa-phone-alt"></i> Contact Us</a>
        </div>
    </div>
</section>

<?php
// --- Configurable Sections ---
foreach ($sections as $section):
    $secTitle = sanitizeInput($section['title']);
    $secKey = sanitizeInput($section['section_key']);
?>
<section class="section dept-section dept-section-<?= sanitizeInput($section['section_type']) ?>" id="section-<?= $secKey ?>">
    <div class="container">
        <h2 class="section-title"><?= $secTitle ?></h2>

        <?php if ($section['section_type'] === 'content'): ?>
            <div class="content-area"><?= $section['content'] ?></div>

        <?php elseif ($section['section_type'] === 'list'): ?>
            <?php
            $items = json_decode($section['content'], true) ?: [];
            ?>
            <div class="row">
                <?php foreach ($items as $item): ?>
                <div class="col-4">
                    <div class="card service-card">
                        <div class="card-body">
                            <h3 class="card-title"><?= sanitizeInput($item['title'] ?? '') ?></h3>
                            <p class="card-text"><?= sanitizeInput($item['description'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($section['section_type'] === 'doctors'): ?>
            <?php if (count($doctors) > 0): ?>
            <div class="row">
                <?php foreach ($doctors as $doc): ?>
                <div class="col-4">
                    <div class="card doctor-card">
                        <?php if ($doc['photo']): ?>
                        <img src="<?= SITE_URL . '/' . sanitizeInput($doc['photo']) ?>" alt="<?= sanitizeInput($doc['name']) ?>" class="card-img">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?= sanitizeInput($doc['name']) ?></h3>
                            <p class="designation"><?= sanitizeInput($doc['designation'] ?? '') ?></p>
                            <p class="card-text"><?= sanitizeInput($doc['specialization']) ?></p>
                            <a href="<?= SITE_URL ?>/doctor.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-md"></i> View Profile</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="text-align:center;color:#94a3b8;">No doctors assigned to this department yet.</p>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>
