<?php
$pageTitle = 'About Us';
$metaDesc = 'Learn about Almas Hospital - our history, mission, vision, and commitment to healthcare excellence.';
require_once 'includes/header.php';
$about = getPublishedContent('about');
$gallery = getPublishedContent('about_gallery');
$mission = getPublishedContent('about_mission');
$vision = getPublishedContent('about_vision');
?>
<!-- <section class="page-header">
    <div class="container">
        <h1>About <?= sanitizeInput($settings['Us'] ?? 'Us') ?></h1>
        <p>Our history, mission, and commitment to healthcare excellence</p>
    </div>
</section> -->
<section class="section">
    <div class="container">
        <div class="about-layout">
            <h6 class="about-label">Almas Hospital</h6>
            <h2 class="about-title">About Us</h2>

            <?php if ($about): ?>
            <div class="about-text-wrap">
                <?php if ($about['featured_image']): ?>
                <div class="about-featured-img">
                    <img src="<?= SITE_URL . '/' . sanitizeInput($about['featured_image']) ?>" alt="<?= sanitizeInput($about['title']) ?>" loading="lazy">
                </div>
                <?php endif; ?>
                <div class="content-area"><?= nl2p($about['content']) ?></div>
            </div>
            <?php else: ?>
            <div class="about-text-wrap">
                <div class="content-area">
                    <p>Almas Hospital has been a beacon of hope and healing, providing world-class healthcare services with a patient-centric approach. Our journey began with a vision to make quality healthcare accessible to everyone, and today we stand as a premier healthcare institution.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($gallery && trim($gallery['content'])): ?>
            <div class="about-section-block about-gallery-block">
                <div class="about-gallery-grid">
                    <?= $gallery['content'] ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mission && trim(strip_tags($mission['content']))): ?>
            <div class="about-section-block about-mission-block">
                <div class="about-section-inner">
                    <div class="about-section-text">
                        <h3 class="about-section-heading"><?= sanitizeInput($mission['title']) ?: 'Our Mission' ?></h3>
                        <div class="content-area"><?= nl2p($mission['content']) ?></div>
                    </div>
                    <?php if ($mission['featured_image']): ?>
                    <div class="about-section-img">
                        <img src="<?= SITE_URL . '/' . sanitizeInput($mission['featured_image']) ?>" alt="<?= sanitizeInput($mission['title']) ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($vision && trim(strip_tags($vision['content']))): ?>
            <div class="about-section-block about-vision-block">
                <div class="about-section-inner">
                    <?php if ($vision['featured_image']): ?>
                    <div class="about-section-img">
                        <img src="<?= SITE_URL . '/' . sanitizeInput($vision['featured_image']) ?>" alt="<?= sanitizeInput($vision['title']) ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="about-section-text">
                        <h3 class="about-section-heading"><?= sanitizeInput($vision['title']) ?: 'Our Vision' ?></h3>
                        <div class="content-area"><?= nl2p($vision['content']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
