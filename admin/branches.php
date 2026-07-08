<?php
$pageTitle = 'Manage Branches';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM branches WHERE id = $id");
    $_SESSION['success'] = 'Branch deleted.';
    redirect('branches.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $branchName = sanitize($_POST['branch_name']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $googleMap = sanitize($_POST['google_map']);
    $status = sanitize($_POST['status']);
    $branchId = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/gallery');
        if ($upload['success']) $image = $upload['path'];
    }

    if ($branchId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE branches SET branch_name=?, address=?, phone=?, email=?, google_map=?, image=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssssi', $branchName, $address, $phone, $email, $googleMap, $image, $status, $branchId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE branches SET branch_name=?, address=?, phone=?, email=?, google_map=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssssi', $branchName, $address, $phone, $email, $googleMap, $status, $branchId);
        }
    } else {
        if ($image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO branches (branch_name, address, phone, email, google_map, image, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssssi', $branchName, $address, $phone, $email, $googleMap, $image, $status, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO branches (branch_name, address, phone, email, google_map, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssssi', $branchName, $address, $phone, $email, $googleMap, $status, $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    $newId = $branchId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('branch', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Branch saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Branch saved successfully.';
    }
    redirect('branches.php');
}

$editBranch = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM branches WHERE id = $id");
    $editBranch = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT b.*, u.name as creator FROM branches b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.branch_name");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editBranch ? 'Edit Branch' : 'Branches' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editBranch ? '← Back' : 'Add Branch' ?></a>
    </div>
    <?php if ($editBranch || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="branch_id" value="<?= $editBranch['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Branch Name *</label>
            <input type="text" name="branch_name" class="form-control" value="<?= sanitizeInput($editBranch['branch_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Address *</label>
            <textarea name="address" class="form-control" required><?= sanitizeInput($editBranch['address'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= sanitizeInput($editBranch['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= sanitizeInput($editBranch['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Google Maps Link</label>
            <input type="text" name="google_map" class="form-control" value="<?= sanitizeInput($editBranch['google_map'] ?? '') ?>" placeholder="https://maps.google.com/...">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($editBranch['image'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editBranch['image']) ?>" style="max-height:80px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?= ($editBranch['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($editBranch['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Branch</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Branch Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= sanitizeInput($row['branch_name']) ?></strong></td>
                <td><?= sanitizeInput($row['phone'] ?? '-') ?></td>
                <td><?= sanitizeInput($row['email'] ?? '-') ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if (hasRole('Administrator')): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this branch?">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
