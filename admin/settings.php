<?php
$pageTitle = 'Website Settings';
require_once 'header.php';
requireRole('Administrator');

$settings = getSetting();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $websiteName = sanitize($_POST['website_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $facebook = sanitize($_POST['facebook']);
    $instagram = sanitize($_POST['instagram']);
    $youtube = sanitize($_POST['youtube']);
    $whatsapp = sanitize($_POST['whatsapp']);
    $footerText = $_POST['footer_text'];
    $logo = '';

    if (!empty($_FILES['logo']['name'])) {
        $upload = uploadFile($_FILES['logo'], UPLOAD_PATH . '/settings');
        if ($upload['success']) $logo = $upload['path'];
    }

    if ($settings) {
        if ($logo) {
            $stmt = mysqli_prepare($conn, "UPDATE website_settings SET website_name=?, email=?, phone=?, address=?, facebook=?, instagram=?, youtube=?, whatsapp=?, footer_text=?, logo=?, updated_by=? WHERE id=1");
            mysqli_stmt_bind_param($stmt, 'ssssssssssi', $websiteName, $email, $phone, $address, $facebook, $instagram, $youtube, $whatsapp, $footerText, $logo, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE website_settings SET website_name=?, email=?, phone=?, address=?, facebook=?, instagram=?, youtube=?, whatsapp=?, footer_text=?, updated_by=? WHERE id=1");
            mysqli_stmt_bind_param($stmt, 'sssssssssi', $websiteName, $email, $phone, $address, $facebook, $instagram, $youtube, $whatsapp, $footerText, $_SESSION['user_id']);
        }
    } else {
        if ($logo) {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_settings (website_name, email, phone, address, facebook, instagram, youtube, whatsapp, footer_text, logo, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssssssssi', $websiteName, $email, $phone, $address, $facebook, $instagram, $youtube, $whatsapp, $footerText, $logo, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_settings (website_name, email, phone, address, facebook, instagram, youtube, whatsapp, footer_text, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssssssi', $websiteName, $email, $phone, $address, $facebook, $instagram, $youtube, $whatsapp, $footerText, $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['success'] = 'Settings updated successfully.';
    redirect('settings.php');
}
?>
<div class="form-container">
    <h5>Website Settings</h5>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label>Website Name *</label>
            <input type="text" name="website_name" class="form-control" value="<?= sanitizeInput($settings['website_name'] ?? '') ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= sanitizeInput($settings['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= sanitizeInput($settings['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control"><?= sanitizeInput($settings['address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Logo</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <?php if (!empty($settings['logo'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($settings['logo']) ?>" style="max-height:60px;margin-top:5px;">
            <?php endif; ?>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Facebook URL</label>
                <input type="text" name="facebook" class="form-control" value="<?= sanitizeInput($settings['facebook'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Instagram URL</label>
                <input type="text" name="instagram" class="form-control" value="<?= sanitizeInput($settings['instagram'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>YouTube URL</label>
                <input type="text" name="youtube" class="form-control" value="<?= sanitizeInput($settings['youtube'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>WhatsApp Number</label>
                <input type="text" name="whatsapp" class="form-control" value="<?= sanitizeInput($settings['whatsapp'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Footer Text</label>
            <textarea name="footer_text" class="form-control" style="min-height:80px;"><?= sanitizeInput($settings['footer_text'] ?? '') ?></textarea>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Settings</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>
