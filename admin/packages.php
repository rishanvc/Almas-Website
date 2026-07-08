<?php
$pageTitle = 'Manage Health Packages';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM health_packages WHERE id = $id");
    $_SESSION['success'] = 'Package deleted.';
    redirect('packages.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = sanitize($_POST['package_name']);
    $description = $_POST['description'];
    $benefits = $_POST['benefits'];
    $status = sanitize($_POST['status']);
    $pkgId = isset($_POST['pkg_id']) ? (int)$_POST['pkg_id'] : 0;
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/gallery');
        if ($upload['success']) $image = $upload['path'];
    }

    if ($pkgId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE health_packages SET package_name=?, description=?, benefits=?, image=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssi', $name, $description, $benefits, $image, $status, $pkgId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE health_packages SET package_name=?, description=?, benefits=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $name, $description, $benefits, $status, $pkgId);
        }
    } else {
        if ($image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO health_packages (package_name, description, benefits, image, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssi', $name, $description, $benefits, $image, $status, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO health_packages (package_name, description, benefits, status, created_by) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssi', $name, $description, $benefits, $status, $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['success'] = 'Package saved successfully.';
    redirect('packages.php');
}

$editPkg = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM health_packages WHERE id = $id");
    $editPkg = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT * FROM health_packages ORDER BY package_name");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editPkg ? 'Edit Package' : 'Health Packages' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editPkg ? '← Back' : 'Add Package' ?></a>
    </div>
    <?php if ($editPkg || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="pkg_id" value="<?= $editPkg['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Package Name *</label>
            <input type="text" name="package_name" class="form-control" value="<?= sanitizeInput($editPkg['package_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" class="form-control" style="min-height:200px;" required><?= sanitizeInput($editPkg['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Benefits</label>
            <textarea name="benefits" class="form-control" style="min-height:100px;"><?= sanitizeInput($editPkg['benefits'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($editPkg['image'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editPkg['image']) ?>" style="max-height:80px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?= ($editPkg['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($editPkg['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Package</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Package Name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= sanitizeInput($row['package_name']) ?></strong></td>
                <td><span class="badge badge-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this package?">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
