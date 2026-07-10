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
$doctors = getActiveDoctors($id);
$sections = getDepartmentSections($id);
$faqs = getDepartmentFAQs($id);
$settings = getSetting();
$pageTitle = sanitizeInput($dept['department_name']);
?>

<?php
// --- Banner (kept exactly as before) ---
$bannerImage = $dept['image'] ? SITE_URL . '/' . sanitizeInput($dept['image']) : '';
?>
<section class="dept-banner"<?= $bannerImage ? ' style="background-image:url(' . $bannerImage . ');"' : '' ?>>
</section>

<section class="dept-main">
    <div class="container">
        <h6 class="dept-title-label"><?= sanitizeInput($settings['website_name'] ?? 'ALMAS HOSPITAL') ?></h6>
        <h2 class="dept-title-name"><?= sanitizeInput($dept['department_name']) ?></h2>
        <?php if ($dept['description']): ?>
        <div class="dept-intro-content content-area">
            <?= nl2p($dept['description']) ?>
            <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary" style="border-radius:50px;padding:12px 32px;margin-top:20px;"><i class="fas fa-phone-alt"></i> Contact Now</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php
// --- Dynamic Units ---
foreach ($sections as $section):
    if ($section['section_type'] === 'doctors') continue;
    $secTitle = sanitizeInput($section['title']);
    $secKey = sanitizeInput($section['section_key']);
    $secImage = $section['image_path'] ? SITE_URL . '/' . sanitizeInput($section['image_path']) : '';
?>
<section class="section dept-section dept-section-<?= sanitizeInput($section['section_type']) ?>" id="section-<?= $secKey ?>">
    <div class="container">
        <?php if ($secTitle): ?>
        <h2 class="section-title"><?= $secTitle ?></h2>
        <?php endif; ?>

        <?php if ($section['section_type'] === 'content'): ?>
            <div class="dept-unit">
                <?php if ($secImage): ?>
                <div class="dept-unit-image">
                    <img src="<?= $secImage ?>" alt="<?= $secTitle ?>">
                </div>
                <?php endif; ?>
                <div class="dept-unit-content content-area"><?= nl2p($section['content']) ?></div>
            </div>

        <?php elseif ($section['section_type'] === 'list'): ?>
            <?php
            $items = json_decode($section['content'], true) ?: [];
            ?>
            <?php if (count($items) > 0): ?>
            <div class="row dept-list-grid">
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
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php endforeach; ?>

<?php
// --- FAQ Section ---
if (count($faqs) > 0):
?>
<section class="section dept-faq">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="faq-list">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="faq-item<?= $i === 0 ? ' faq-open' : '' ?>">
                <button class="faq-question" onclick="this.parentElement.classList.toggle('faq-open')">
                    <span><?= sanitizeInput($faq['question']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer content-area"><?= nl2p($faq['answer']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// --- Doctors Section ---
if (count($doctors) > 0):
    $docSectionTitle = 'Our Doctors';
    foreach ($sections as $section) {
        if ($section['section_type'] === 'doctors' && $section['title']) {
            $docSectionTitle = sanitizeInput($section['title']);
            break;
        }
    }
?>
<section class="section dept-section dept-section-doctors">
    <div class="container">
        <h2 class="section-title"><?= $docSectionTitle ?></h2>
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
                        <a href="<?= SITE_URL ?>/doctor.php?id=<?= $doc['id'] ?>" class="btn btn-sm"><i class="fas fa-user-md"></i> View Profile</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php elseif (count($sections) > 0): ?>
    <?php foreach ($sections as $section): ?>
        <?php if ($section['section_type'] === 'doctors'): ?>
        <section class="section dept-section dept-section-doctors">
            <div class="container">
                <h2 class="section-title"><?= sanitizeInput($section['title']) ?></h2>
                <p style="text-align:center;color:#94a3b8;">No doctors assigned to this department yet.</p>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
