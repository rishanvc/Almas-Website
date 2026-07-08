<?php
$pageTitle = 'About Us';
$metaDesc = 'Learn about Almas Hospital - our history, mission, vision, and commitment to healthcare excellence.';
require_once 'includes/header.php';
$content = getPublishedContent('about');
?>
<section class="page-header">
    <div class="container">
        <h1>About <?= sanitizeInput($settings['Us'] ?? 'Us') ?></h1>
        <p>Our history, mission, and commitment to healthcare excellence</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if ($content): ?>
            <?php if ($content['featured_image']): ?>
            <img src="<?= SITE_URL . '/' . sanitizeInput($content['featured_image']) ?>" alt="<?= sanitizeInput($content['title']) ?>" class="detail-img">
            <?php endif; ?>
            <div class="content-area"><?= $content['content'] ?></div>
        <?php else: ?>
            <div class="content-area">
                <h2>Welcome to Almas Hospital</h2>
                <p>Almas Hospital has been a beacon of hope and healing, providing world-class healthcare services with a patient-centric approach. Our journey began with a vision to make quality healthcare accessible to everyone, and today we stand as a premier healthcare institution.</p>
                <h2>Our Mission</h2>
                <p>To provide compassionate, accessible, and high-quality healthcare services to all patients, delivered by skilled professionals using advanced medical technology.</p>
                <h2>Our Vision</h2>
                <p>To be a trusted leader in healthcare, known for clinical excellence, innovative treatments, and exceptional patient experience.</p>
                <h2>Our Values</h2>
                <p><strong>Compassion:</strong> We treat every patient with dignity, respect, and empathy.</p>
                <p><strong>Excellence:</strong> We strive for the highest standards in medical care and service.</p>
                <p><strong>Innovation:</strong> We embrace advanced technology and modern treatment methods.</p>
                <p><strong>Integrity:</strong> We uphold ethical practices and transparency in all we do.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
