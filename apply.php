<?php
$pageTitle = 'Apply for Job';
$metaDesc = 'Submit your job application at Almas Hospital.';
require_once 'includes/header.php';
$jobId = isset($_GET['job']) ? (int)$_GET['job'] : 0;
$stmt = mysqli_prepare($conn, "SELECT * FROM careers WHERE id = ? AND status = 'Open'");
mysqli_stmt_bind_param($stmt, 'i', $jobId);
mysqli_stmt_execute($stmt);
$job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$job) { header('Location: careers.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $coverLetter = sanitize($_POST['cover_letter']);

    if (empty($name) || empty($email) || empty($phone) || empty($_FILES['resume']['name'])) {
        $error = 'Please fill in all required fields and upload your resume.';
    } else {
        $upload = uploadFile($_FILES['resume'], UPLOAD_PATH . '/resumes', ['pdf','doc','docx']);
        if ($upload['success']) {
            $stmt = mysqli_prepare($conn, "INSERT INTO job_applications (career_id, applicant_name, email, phone, resume, cover_letter) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isssss', $jobId, $name, $email, $phone, $upload['path'], $coverLetter);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Your application has been submitted successfully. We will contact you if your profile matches our requirements.';
                redirect(SITE_URL . '/careers.php');
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = $upload['error'];
        }
    }
}
?>
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-paper-plane"></i> Apply for <?= sanitizeInput($job['job_title']) ?></h1>
        <p>Submit your application for <?= sanitizeInput($job['job_title']) ?></p>
    </div>
</section>
<section class="section bg-light-section">
    <div class="container">
        <?php displayMessage(); ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= sanitizeInput($error) ?></div><?php endif; ?>
        <div class="form-wrapper">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone *</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-file-upload"></i> Resume (PDF/DOC) *</label>
                    <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Cover Letter (Optional)</label>
                    <textarea name="cover_letter" class="form-control" placeholder="Tell us why you're a good fit..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Submit Application</button>
            </form>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
