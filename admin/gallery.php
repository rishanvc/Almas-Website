<?php
$pageTitle = 'Manage Gallery';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM gallery WHERE id = $id");
    $_SESSION['success'] = 'Image deleted.';
    redirect('gallery.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $galId = isset($_POST['gal_id']) ? (int)$_POST['gal_id'] : 0;
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/gallery');
        if ($upload['success']) {
            $image = $upload['path'];
        } else {
            $_SESSION['error'] = $upload['error'];
            redirect('gallery.php');
        }
    }

    if ($galId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE gallery SET title=?, description=?, image=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssi', $title, $description, $image, $galId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE gallery SET title=?, description=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssi', $title, $description, $galId);
        }
    } else {
        if (empty($image)) {
            $_SESSION['error'] = 'Please select an image.';
            redirect('gallery.php');
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO gallery (title, description, image, created_by) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssi', $title, $description, $image, $_SESSION['user_id']);
    }
    mysqli_stmt_execute($stmt);
    $newId = $galId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('gallery', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Image saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Image saved successfully.';
    }
    redirect('gallery.php');
}

$editGal = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM gallery WHERE id = $id");
    $editGal = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT g.*, u.name as creator FROM gallery g LEFT JOIN users u ON g.created_by = u.id ORDER BY g.created_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editGal ? 'Edit Image' : 'Gallery' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editGal ? '← Back' : 'Add Image' ?></a>
    </div>
    <?php if ($editGal || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="gal_id" value="<?= $editGal['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" value="<?= sanitizeInput($editGal['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"><?= sanitizeInput($editGal['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Image <?= $editGal ? '(Leave empty to keep current)' : '*' ?></label>
            <input type="file" name="image" class="form-control" accept="image/*" <?= $editGal ? '' : 'required' ?>>
            <?php if (!empty($editGal['image'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($editGal['image']) ?>" style="max-height:100px;margin-top:5px;">
            <?php endif; ?>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Uploaded By</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><img src="<?= SITE_URL . '/' . sanitizeInput($row['image']) ?>" style="height:50px;width:80px;object-fit:cover;border-radius:4px;"></td>
                <td><?= sanitizeInput($row['title']) ?></td>
                <td><?= sanitizeInput($row['creator'] ?? 'N/A') ?></td>
                <td><?= timeAgo($row['created_at']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this image?">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
