<?php
$pageTitle = 'Contact Us';
$metaDesc = 'Get in touch with Almas Hospital. Send us your enquiries and feedback.';
require_once 'includes/header.php';
$branches = getActiveBranches();
$settings = getSetting();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize($_POST['type']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $message_text = sanitize($_POST['message']);

    if (empty($name) || empty($subject) || empty($message_text)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO contact_enquiries (enquiry_type, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssss', $type, $name, $email, $phone, $subject, $message_text);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Your enquiry has been submitted successfully. We will get back to you soon.';
            redirect(SITE_URL . '/contact.php');
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<section class="page-header">
    <div class="container">
        <h1>Contact <strong>Us</strong></h1>
        <p>We're here to help you</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php displayMessage(); ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= sanitizeInput($error) ?></div><?php endif; ?>
        <div class="row">
            <div class="col-6">
                <h3><i class="fas fa-envelope"></i> Send Us a Message</h3>
                <div class="form-wrapper">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Enquiry Type *</label>
                            <select name="type" class="form-control" required>
                                <option value="General">General Enquiry</option>
                                <option value="International Patient">International Patient</option>
                                <option value="Home Care">Home Care</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Subject *</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Message *</label>
                            <textarea name="message" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Send Enquiry</button>
                    </form>
                </div>
            </div>
            <div class="col-6">
                <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
                <div class="card contact-info-card">
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong><br><?= nl2br(sanitizeInput($settings['address'] ?? '')) ?></p>
                    <p><i class="fas fa-phone"></i> <strong>Phone:</strong><br><?= sanitizeInput($settings['phone'] ?? '') ?></p>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong><br><?= sanitizeInput($settings['email'] ?? '') ?></p>
                    <?php if ($settings['whatsapp']): ?>
                    <p><i class="fab fa-whatsapp"></i> <strong>WhatsApp:</strong><br><a href="https://wa.me/<?= sanitizeInput($settings['whatsapp']) ?>" target="_blank"><?= sanitizeInput($settings['whatsapp']) ?></a></p>
                    <?php endif; ?>
                    <hr>
                    <h4><i class="fas fa-code-branch"></i> Our Branches</h4>
                    <?php foreach ($branches as $branch): ?>
                    <div class="branch-item">
                        <strong><i class="fas fa-map-marker-alt"></i> <?= sanitizeInput($branch['branch_name']) ?></strong><br>
                        <?= nl2br(sanitizeInput($branch['address'])) ?><br>
                        <?php if ($branch['phone']): ?><i class="fas fa-phone"></i> <?= sanitizeInput($branch['phone']) ?><br><?php endif; ?>
                        <?php if ($branch['google_map']): ?><a href="<?= sanitizeInput($branch['google_map']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-map"></i> View on Map</a><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
