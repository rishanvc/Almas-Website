<?php
$pageTitle = 'Our Branches';
$metaDesc = 'Find Almas Hospital branches near you.';
require_once 'includes/header.php';
$branches = getActiveBranches();
?>
<section class="page-header">
    <div class="container">
        <h1>Our <strong>Branches</strong></h1>
        <p>Find a branch near you</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($branches) > 0): ?>
            <div class="row">
                <?php foreach ($branches as $branch): ?>
                <div class="col-6">
                    <div class="card">
                        <?php if ($branch['image']): ?>
                        <img src="<?= SITE_URL . '/' . sanitizeInput($branch['image']) ?>" alt="<?= sanitizeInput($branch['branch_name']) ?>" class="card-img">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> <?= sanitizeInput($branch['branch_name']) ?></h3>
                            <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong><br><?= nl2br(sanitizeInput($branch['address'])) ?></p>
                            <?php if ($branch['phone']): ?><p><i class="fas fa-phone"></i> <strong>Phone:</strong> <?= sanitizeInput($branch['phone']) ?></p><?php endif; ?>
                            <?php if ($branch['email']): ?><p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?= sanitizeInput($branch['email']) ?></p><?php endif; ?>
                            <?php if ($branch['google_map']): ?>
                            <a href="<?= sanitizeInput($branch['google_map']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-map"></i> View on Google Maps</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center">
                <p><i class="fas fa-info-circle"></i> Branch information coming soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
