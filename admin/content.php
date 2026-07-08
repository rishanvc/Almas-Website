<?php
$pageTitle = 'Website Content';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $pageName = sanitize($_POST['page_name']);
    $title = sanitize($_POST['title']);
    $content = $_POST['content'];
    $contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
    $image = '';

    if (!empty($_FILES['featured_image']['name'])) {
        $upload = uploadFile($_FILES['featured_image'], UPLOAD_PATH . '/gallery');
        if ($upload['success']) $image = $upload['path'];
    }

    $status = 'Pending';
    if (hasRole('Administrator')) $status = 'Published';

    if ($contentId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE website_contents SET page_name=?, title=?, content=?, featured_image=?, status=?, updated_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssii', $pageName, $title, $content, $image, $status, $_SESSION['user_id'], $contentId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE website_contents SET page_name=?, title=?, content=?, status=?, updated_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssii', $pageName, $title, $content, $status, $_SESSION['user_id'], $contentId);
        }
    } else {
        if ($image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_contents (page_name, title, content, featured_image, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssii', $pageName, $title, $content, $image, $status, $_SESSION['user_id'], $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_contents (page_name, title, content, status, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssii', $pageName, $title, $content, $status, $_SESSION['user_id'], $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    $newId = $contentId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if ($status === 'Pending' && !hasRole('Administrator')) {
        createApprovalRequest('website_content', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Content saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Content saved successfully.';
    }
    redirect('content.php');
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (hasRole('Administrator')) {
        mysqli_query($conn, "DELETE FROM website_contents WHERE id = $id");
        $_SESSION['success'] = 'Content deleted successfully.';
    } elseif (hasRole('Content Creator')) {
        $res = mysqli_query($conn, "SELECT status FROM website_contents WHERE id = $id");
        $row = mysqli_fetch_assoc($res);
        $prevStatus = $row ? $row['status'] : 'Draft';
        mysqli_query($conn, "UPDATE website_contents SET status='Draft' WHERE id = $id");
        $stmt = mysqli_prepare($conn, "INSERT INTO approval_requests (entity_type, entity_id, requested_by, comments, status) VALUES ('website_content', ?, ?, ?, 'Pending')");
        $delComments = '__DELETE__|' . $prevStatus;
        mysqli_stmt_bind_param($stmt, 'iis', $id, $_SESSION['user_id'], $delComments);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['success'] = 'Deletion request submitted for approval.';
    }
    redirect('content.php');
}

$editContent = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM website_contents WHERE id = $id");
    $editContent = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT wc.*, u.name as creator FROM website_contents wc LEFT JOIN users u ON wc.created_by = u.id ORDER BY wc.updated_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editContent ? 'Edit Content' : 'Website Content' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editContent ? '← Back' : 'Add New Content' ?></a>
    </div>
    <?php if ($editContent || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="content_id" value="<?= $editContent['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Page Name *</label>
                <select name="page_name" class="form-control" required>
                    <option value="">Select Page</option>
                    <?php
                    $pages = ['home'=>'Home','about'=>'About Hospital','chairman'=>'Chairman Message','mission'=>'Mission & Vision','announcements'=>'Announcements'];
                    foreach ($pages as $key => $val):
                    ?>
                    <option value="<?= $key ?>" <?= ($editContent['page_name'] ?? '') == $key ? 'selected' : '' ?>><?= $val ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" value="<?= sanitizeInput($editContent['title'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Content *</label>
            <textarea name="content" class="form-control" style="min-height:300px;"><?= sanitizeInput($editContent['content'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Featured Image</label>
            <input type="file" name="featured_image" class="form-control" accept="image/*">
            <?php if (!empty($editContent['featured_image'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($editContent['featured_image']) ?>" style="max-height:100px;margin-top:5px;">
            <?php endif; ?>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Content</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Page</th>
                <th>Title</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Updated</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><span class="badge badge-info"><?= sanitizeInput($row['page_name']) ?></span></td>
                <td><?= sanitizeInput($row['title']) ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Published' ? 'success' : ($row['status'] == 'Pending' ? 'warning' : 'secondary') ?>"><?= $row['status'] ?></span></td>
                <td><?= sanitizeInput($row['creator'] ?? 'N/A') ?></td>
                <td><?= timeAgo($row['updated_at']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if (hasAnyRole(['Administrator', 'Content Creator'])): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this content?">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
