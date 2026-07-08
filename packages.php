<?php
$pageTitle = 'Health Packages';
$metaDesc = 'Explore our health check-up packages and preventive healthcare services.';
require_once 'includes/header.php';
$packages = getActivePackages();
?>
<section class="page-header">
    <div class="container">
        <h1>Health <strong>Packages</strong></h1>
        <p>Comprehensive health check-up packages for your well-being</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($packages) > 0): ?>
        <div class="row">
            <?php foreach ($packages as $pkg): ?>
            <div class="col-4">
                <div class="card">
                    <?php if ($pkg['image']): ?>
                    <img src="<?= SITE_URL . '/' . sanitizeInput($pkg['image']) ?>" alt="<?= sanitizeInput($pkg['package_name']) ?>" class="card-img">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title"><i class="fas fa-heartbeat"></i> <?= sanitizeInput($pkg['package_name']) ?></h3>
                        <p class="card-text"><?= substr(strip_tags($pkg['description']), 0, 150) ?>...</p>
                        <?php if ($pkg['benefits']): ?>
                        <p class="card-text"><strong><i class="fas fa-check-circle"></i> Benefits:</strong> <?= substr(strip_tags($pkg['benefits']), 0, 120) ?>...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center">
            <p><i class="fas fa-info-circle"></i> Health package information coming soon. Please contact us for more details.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
