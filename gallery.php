<?php
$pageTitle = 'Gallery';
$metaDesc = 'Browse through our gallery showcasing Almas Hospital facilities, events, and team.';
require_once 'includes/header.php';
$gallery = getActiveGallery();
?>
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-images"></i> Gallery</h1>
        <p>A glimpse into our hospital and facilities</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($gallery) > 0): ?>
        <div class="gallery-grid">
            <?php foreach ($gallery as $item): ?>
            <div class="gallery-item">
                <img src="<?= SITE_URL . '/' . sanitizeInput($item['image']) ?>" alt="<?= sanitizeInput($item['title']) ?>">
                <div class="overlay">
                    <strong><?= sanitizeInput($item['title']) ?></strong>
                    <?php if ($item['description']): ?><br><small><?= sanitizeInput(substr($item['description'], 0, 80)) ?></small><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center">
            <p><i class="fas fa-info-circle"></i> Gallery images coming soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
