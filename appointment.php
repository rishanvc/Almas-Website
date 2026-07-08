<?php
$pageTitle = 'Book Appointment';
$metaDesc = 'Book an appointment online at Almas Hospital.';
require_once 'includes/header.php';
$departments = getActiveDepartments();
$selectedDoctor = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $deptId = !empty($_POST['department']) ? (int)$_POST['department'] : null;
    $docId = !empty($_POST['doctor']) ? (int)$_POST['doctor'] : null;
    $date = sanitize($_POST['appointment_date']);
    $message = sanitize($_POST['message']);

    if (empty($name) || empty($phone) || empty($date)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO appointments (patient_name, email, phone, department_id, doctor_id, appointment_date, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmt, 'sssiiss', $name, $email, $phone, $deptId, $docId, $date, $message);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Your appointment request has been submitted successfully. We will contact you shortly to confirm.';
            redirect(SITE_URL . '/appointment.php');
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<section class="page-header">
    <div class="container">
        <h1>Book an <strong>Appointment</strong></h1>
        <p>Schedule your visit with our medical experts</p>
    </div>
</section>
<section class="section bg-light-section">
    <div class="container">
        <?php displayMessage(); ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= sanitizeInput($error) ?></div><?php endif; ?>
        <div class="form-wrapper">
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Department</label>
                    <select name="department" class="form-control" id="deptSelect">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= sanitizeInput($dept['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user-md"></i> Doctor</label>
                    <select name="doctor" class="form-control">
                        <option value="">Select Doctor (Optional)</option>
                        <?php
                        $doctors = getActiveDoctors();
                        foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= $selectedDoctor == $doc['id'] ? 'selected' : '' ?>><?= sanitizeInput($doc['name']) ?> - <?= sanitizeInput($doc['specialization']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Preferred Date *</label>
                    <input type="date" name="appointment_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Message (Optional)</label>
                    <textarea name="message" class="form-control" placeholder="Any specific requirements..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Submit Appointment Request</button>
            </form>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
