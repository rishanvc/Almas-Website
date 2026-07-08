<?php
$pageTitle = "Chairman's Message";
$metaDesc = "Message from the Chairman of Almas Hospital.";
require_once 'includes/header.php';
$content = getPublishedContent('chairman');
?>
<section class="page-header">
    <div class="container">
        <h1>Chairman's Message</h1>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if ($content): ?>
            <div class="row">
                <?php if ($content['featured_image']): ?>
                <div class="col-4">
                    <img src="<?= SITE_URL . '/' . sanitizeInput($content['featured_image']) ?>" alt="Chairman" style="width:100%;border-radius:var(--br);">
                </div>
                <div class="col-8">
                <?php else: ?>
                <div class="col-12">
                <?php endif; ?>
                    <div class="content-area"><?= $content['content'] ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="content-area">
                <h2>Welcome Message from Our Chairman</h2>
                <p>Dear Patients, Partners, and Well-wishers,</p>
                <p>It gives me immense pleasure to welcome you to Almas Hospital. Our commitment to providing exceptional healthcare services has been the driving force behind our journey.</p>
                <p>At Almas Hospital, we believe that quality healthcare is a fundamental right. Our team of dedicated medical professionals works tirelessly to ensure that every patient receives the best possible care in a compassionate and supportive environment.</p>
                <p>We have invested in state-of-the-art medical technology and infrastructure to provide accurate diagnoses and effective treatments. Our patient-centric approach ensures that your comfort and well-being remain our top priority.</p>
                <p>Thank you for choosing Almas Hospital for your healthcare needs. We look forward to serving you with excellence and compassion.</p>
                <p><strong>Chairman</strong><br>Almas Hospital</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
