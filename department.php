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
$facilities = getDepartmentFacilities($id);
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
        <?php
        $introImgs = json_decode($dept['description_images'] ?? '', true);
        $introImg = (is_array($introImgs) && !empty($introImgs[0])) ? SITE_URL . '/' . sanitizeInput($introImgs[0]) : '';
        ?>
        <div class="dept-intro-wrap content-area">
            <?php if ($introImg): ?>
            <div class="dept-intro-img-float">
                <img src="<?= $introImg ?>" alt="<?= sanitizeInput($dept['department_name']) ?>">
            </div>
            <?php endif; ?>
            <h6 class="dept-title-label"><?= sanitizeInput($settings['website_name'] ?? 'ALMAS HOSPITAL') ?></h6>
            <h1 class="dept-title-name" style="margin:0 0 48px;"><?= sanitizeInput($dept['department_name']) ?></h1>
            <?php if ($dept['description']): ?>
                <?= nl2p($dept['description']) ?>
            <?php endif; ?>
        </div>
        <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary" style="border-radius:50px;padding:12px 32px;margin-top:20px;"><i class="fas fa-phone-alt"></i> Contact Now</a>
    </div>
</section>

<?php
// --- Facilities Section ---
if (count($facilities) > 0):
?>
<section class="section dept-section dept-facilities">
    <div class="container">
        <h2 class="section-title">Our Facilities</h2>
        <?php foreach ($facilities as $fac):
            $facImage = $fac['image'] ? SITE_URL . '/' . sanitizeInput($fac['image']) : '';
            $facContentData = json_decode($fac['content'] ?? '', true);
            $facParagraphs = [];
            $facListItems = [];
            if (is_array($facContentData)) {
                $facParagraphs = $facContentData['paragraphs'] ?? [];
                $facListItems  = $facContentData['items'] ?? [];
            } elseif ($fac['description'] && !$facContentData) {
                $facParagraphs = [['content' => $fac['description']]];
            }
        ?>
        <div class="dept-facility-card">
            <div class="dept-facility-inner">
                <?php if ($facImage): ?>
                <div class="dept-facility-image">
                    <img src="<?= $facImage ?>" alt="<?= sanitizeInput($fac['facility_name']) ?>">
                </div>
                <?php endif; ?>
                <div class="dept-facility-body">
                    <?php if (!empty($fac['facility_name'])): ?>
                    <h3 class="dept-facility-name"><?= sanitizeInput($fac['facility_name']) ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($facParagraphs)): ?>
                    <div class="dept-facility-text content-area">
                        <?php foreach ($facParagraphs as $para): ?>
                            <?= nl2p(sanitizeInput($para['content'] ?? '')) ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($facListItems)): ?>
                    <div class="dept-facility-list">
                        <?php foreach ($facListItems as $item): ?>
                        <div class="dept-facility-list-item">
                            <div class="dept-checklist-row">
                                <i class="fas fa-check-circle dept-check-icon"></i>
                                <div class="dept-checklist-text">
                                    <span class="dept-checklist-title"><?= sanitizeInput($item['title'] ?? '') ?></span>
                                    <?php if (!empty($item['description'])): ?>
                                    <span class="dept-checklist-desc"><?= sanitizeInput($item['description']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($item['children'])): ?>
                            <ul class="dept-checklist-sub">
                                <?php foreach ($item['children'] as $child): ?>
                                <li>
                                    <span class="dept-checklist-subtitle"><?= sanitizeInput($child['title'] ?? '') ?></span>
                                    <?php if (!empty($child['description'])): ?>
                                    <span class="dept-checklist-subdesc"><?= sanitizeInput($child['description']) ?></span>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
// --- Dynamic Units ---
foreach ($sections as $section):
    if ($section['section_type'] === 'doctors') continue;

    $secType = $section['section_type'];
    // Backward compatibility: old 'content' type is now 'text'
    if ($secType === 'content') $secType = 'text';

    $secTitle   = sanitizeInput($section['title']);
    $secSubtitle = sanitizeInput($section['subtitle'] ?? '');
    $secKey     = sanitizeInput($section['section_key']);
    $secImage   = $section['image_path'] ? SITE_URL . '/' . sanitizeInput($section['image_path']) : '';
    $btnText    = sanitizeInput($section['button_text'] ?? '');
    $btnUrl     = sanitizeInput($section['button_url'] ?? '');

    // Decode JSON content
    $contentData = json_decode($section['content'], true);
    $paragraphs  = [];
    $listItems   = [];
    $galleryImages = [];

    if (is_array($contentData)) {
        $paragraphs    = $contentData['paragraphs'] ?? [];
        $listItems     = $contentData['items'] ?? [];
        $galleryImages = $contentData['images'] ?? [];
    } elseif ($secType === 'text' && $section['content'] && !str_starts_with(trim($section['content']), '{')) {
        // Raw HTML/text backward compatibility: wrap as a single paragraph
        $paragraphs = [['content' => $section['content']]];
    }

    $hasContent = ($secType === 'list' && count($listItems) > 0)
               || ($secType === 'gallery' && count($galleryImages) > 0)
               || ($secType === 'doctors')
               || (in_array($secType, ['text','image_text','text_image','cta']) && count($paragraphs) > 0)
               || ($secType === 'cta' && ($btnText || count($paragraphs) > 0));
    if (!$hasContent) continue;

    // Build CSS classes
    $wrapClass = 'dept-unit';
    if ($secType === 'text_image') $wrapClass .= ' dept-unit-reverse';
?>
<section class="section dept-section dept-section-<?= $secType ?>" id="section-<?= $secKey ?>">
    <div class="container">
        <?php if ($secTitle): ?>
        <h2 class="section-title"><?= $secTitle ?></h2>
        <?php endif; ?>
        <?php if ($secSubtitle): ?>
        <p class="section-subtitle"><?= $secSubtitle ?></p>
        <?php endif; ?>

        <?php if (in_array($secType, ['text', 'image_text', 'text_image', 'cta'])): ?>
            <div class="<?= $wrapClass ?>">
                <?php if ($secImage && in_array($secType, ['image_text', 'text_image'])): ?>
                <div class="dept-unit-image">
                    <img src="<?= $secImage ?>" alt="<?= $secTitle ?>">
                </div>
                <?php endif; ?>
                <div class="dept-unit-content content-area">
                    <?php foreach ($paragraphs as $para): ?>
                        <?php if ($secType === 'text'): ?>
                            <?= nl2p($para['content'] ?? '') ?>
                        <?php else: ?>
                            <?= nl2p(sanitizeInput($para['content'] ?? '')) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($secType === 'cta' && $btnText): ?>
            <div class="dept-unit-cta">
                <a href="<?= $btnUrl ?: SITE_URL . '/contact.php' ?>" class="btn btn-primary btn-lg"><?= $btnText ?></a>
            </div>
            <?php endif; ?>

        <?php elseif ($secType === 'list'): ?>
            <?php
            $hasSubItems = false;
            foreach ($listItems as $item) {
                if (!empty($item['children']) && count($item['children']) > 0) {
                    $hasSubItems = true;
                    break;
                }
            }
            $numCounter = 0;
            ?>
            <div class="dept-checklist <?= $hasSubItems ? 'dept-checklist-numbered' : '' ?>">
                <?php foreach ($listItems as $item): ?>
                <div class="dept-checklist-item">
                    <div class="dept-checklist-row">
                        <?php if ($hasSubItems): ?>
                        <?php $numCounter++; ?>
                        <span class="dept-check-num"><?= $numCounter ?>.</span>
                        <?php else: ?>
                        <i class="fas fa-check-circle dept-check-icon"></i>
                        <?php endif; ?>
                        <div class="dept-checklist-text">
                            <span class="dept-checklist-title"><?= sanitizeInput($item['title'] ?? '') ?></span>
                            <?php if (!empty($item['description'])): ?>
                            <span class="dept-checklist-desc"><?= sanitizeInput($item['description']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($item['children'])): ?>
                    <ul class="dept-checklist-sub">
                        <?php foreach ($item['children'] as $child): ?>
                        <li>
                            <i class="fas fa-check-circle dept-sub-icon"></i>
                            <span class="dept-checklist-subtitle"><?= sanitizeInput($child['title'] ?? '') ?></span>
                            <?php if (!empty($child['description'])): ?>
                            <span class="dept-checklist-subdesc"><?= sanitizeInput($child['description']) ?></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($secType === 'gallery'): ?>
            <div class="dept-gallery-grid">
                <?php foreach ($galleryImages as $img): ?>
                <div class="dept-gallery-item">
                    <img src="<?= SITE_URL . '/' . sanitizeInput($img) ?>" alt="<?= $secTitle ?>">
                </div>
                <?php endforeach; ?>
            </div>
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
        <h2 class="section-title faq-section-title">Frequently Asked Questions (FAQs)</h2>
        <div class="faq-list">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="faq-item<?= $i === 0 ? ' faq-open' : '' ?>">
                <button class="faq-question" onclick="faqToggle(this)">
                    <span class="faq-question-text"><?= sanitizeInput($faq['question']) ?></span>
                    <span class="faq-icon"><span class="faq-icon-plus"></span><span class="faq-icon-minus"></span></span>
                </button>
                <div class="faq-answer">
                    <div class="faq-answer-inner content-area"><?= nl2p($faq['answer']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function faqToggle(btn) {
    var item = btn.closest('.faq-item');
    var wasOpen = item.classList.contains('faq-open');
    document.querySelectorAll('.faq-item.faq-open').forEach(function(el) { el.classList.remove('faq-open'); });
    if (!wasOpen) item.classList.add('faq-open');
}
</script>

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
        </div>
    </div>
</section>
<?php elseif (count($sections) > 0): ?>
    <?php foreach ($sections as $section): ?>
        <?php if ($section['section_type'] === 'doctors'): ?>
        <section class="section dept-section dept-section-doctors">
            <div class="container">
                <h2 class="section-title"><?= sanitizeInput($section['title']) ?></h2>
                <p style="color:#94a3b8;">No doctors assigned to this department yet.</p>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
