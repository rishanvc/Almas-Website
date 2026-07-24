<?php
$pageTitle = 'Home Care Services';
$metaDesc = 'Explore our professional home care services for your loved ones.';
require_once 'includes/header.php';
$homeCareItems = getActiveHomeCare();
$settings = getSetting();
?>

<section class="dept-main homecare-main">
    <div class="container">
        <?php if (count($homeCareItems) > 0): ?>
            <?php foreach ($homeCareItems as $index => $item): ?>
            <div class="homecare-item-block<?= $index > 0 ? ' with-divider' : '' ?>">
                <div class="homecare-flow-wrap">
                    <?php if (!empty($item['image'])): ?>
                    <div class="homecare-img-float">
                        <img src="<?= SITE_URL . '/' . sanitizeInput($item['image']) ?>" alt="<?= sanitizeInput($item['heading'] ?? 'Home Care Service') ?>" loading="lazy">
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($item['heading'])): ?>
                    <h2 class="homecare-item-title"><?= sanitizeInput($item['heading']) ?></h2>
                    <?php endif; ?>

                    <?php if (!empty($item['description'])): ?>
                    <div class="content-area homecare-item-desc">
                        <?= nl2p($item['description']) ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    $listItems = json_decode($item['list_items'] ?? '', true);
                    if (!empty($listItems) && is_array($listItems)):
                    ?>
                    <div class="homecare-item-list">
                        <?php foreach ($listItems as $li): ?>
                        <div class="homecare-list-item">
                            <i class="fas fa-check-circle"></i>
                            <span><?= sanitizeInput($li) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($item['additional_text'])): ?>
                    <div class="content-area homecare-item-additional">
                        <?= nl2p($item['additional_text']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="homecare-cta-wrap">
                        <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary" style="border-radius: 50px; padding: 12px 32px; font-size: 0.95rem;">
                            <i class="fas fa-phone-alt" style="margin-right: 6px;"></i> Inquire / Book Service
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding: 80px 20px; background: #fff; border-radius: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.04); border: 1px solid rgba(226, 232, 240, 0.8);">
                <i class="fas fa-house-medical" style="font-size: 54px; color: var(--primary, #981c4e); margin-bottom: 20px; opacity: 0.8;"></i>
                <h3 style="color: #0f172a; font-weight: 700; margin-bottom: 10px;">Home Care Services Coming Soon</h3>
                <p style="color: #64748b; max-width: 500px; margin: 0 auto 24px;">We are currently preparing our home care services. Please check back soon or contact us directly.</p>
                <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary" style="border-radius: 50px; padding: 12px 32px;">Contact Us</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>