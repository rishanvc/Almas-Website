<?php
$pageTitle = 'Careers';
$metaDesc = 'Explore career opportunities at Almas Hospital. Join our team of healthcare professionals.';
require_once 'includes/header.php';
$careers = getActiveCareers();
?>
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-briefcase"></i> Careers</h1>
        <p>Join our team of healthcare professionals</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($careers) > 0): ?>
            <div class="row">
                <?php foreach ($careers as $job): ?>
                <div class="col-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-briefcase"></i> <?= sanitizeInput($job['job_title']) ?></h3>
                            <?php if ($job['department']): ?><p><i class="fas fa-building"></i> <strong>Department:</strong> <?= sanitizeInput($job['department']) ?></p><?php endif; ?>
                            <p class="card-text"><?= substr(strip_tags($job['description']), 0, 200) ?>...</p>
                            <?php if ($job['qualification']): ?><p><i class="fas fa-graduation-cap"></i> <strong>Qualifications:</strong> <?= sanitizeInput(substr($job['qualification'], 0, 150)) ?>...</p><?php endif; ?>
                            <?php if ($job['deadline']): ?><p><i class="fas fa-clock"></i> <strong>Deadline:</strong> <?= date('d M Y', strtotime($job['deadline'])) ?></p><?php endif; ?>
                            <a href="<?= SITE_URL ?>/apply.php?job=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-paper-plane"></i> Apply Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center">
                <h3><i class="fas fa-info-circle"></i> No Vacancies Currently</h3>
                <p>There are no open positions at the moment. Please check back later or send your resume to <?= sanitizeInput($settings['email'] ?? '') ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
