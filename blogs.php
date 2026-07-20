<?php
$pageTitle = 'Blogs';
$metaDesc = 'Read the latest blogs and updates from Almas Hospital.';
require_once 'includes/header.php';
$blogs = getPublishedBlogs();
?>
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-blog"></i> Blogs</h1>
        <p>Latest updates and insights from Almas Hospital</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (count($blogs) > 0): ?>
        <div class="blogs-grid">
            <?php foreach ($blogs as $item): ?>
            <article class="blog-card">
                <?php if ($item['image']): ?>
                <div class="blog-card-img">
                    <img src="<?= SITE_URL . '/' . sanitizeInput($item['image']) ?>" alt="<?= sanitizeInput($item['title']) ?>">
                </div>
                <?php endif; ?>
                <div class="blog-card-body">
                    <div class="blog-meta">
                        <i class="far fa-calendar-alt"></i>
                        <span><?= date('F d, Y', strtotime($item['posted_date'] ?? $item['created_at'])) ?></span>
                    </div>
                    <h3 class="blog-title"><?= sanitizeInput($item['title']) ?></h3>
                    <?php if ($item['description']): ?>
                    <p class="blog-desc"><?= sanitizeInput($item['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($item['content']): ?>
                    <div class="blog-content content-area">
                        <?= nl2p($item['content']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center">
            <p><i class="fas fa-info-circle"></i> Blog posts coming soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
