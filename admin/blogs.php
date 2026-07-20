<?php
$pageTitle = 'Manage Blogs';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM blogs WHERE id = $id");
    $_SESSION['success'] = 'Blog deleted.';
    redirect('blogs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $content = $_POST['content'];
    $postedDate = !empty($_POST['posted_date']) ? sanitize($_POST['posted_date']) : null;
    $blogId = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/gallery');
        if ($upload['success']) {
            $image = $upload['path'];
        } else {
            $_SESSION['error'] = $upload['error'];
            redirect('blogs.php');
        }
    }

    if ($blogId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE blogs SET title=?, description=?, content=?, posted_date=?, image=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssi', $title, $description, $content, $postedDate, $image, $blogId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE blogs SET title=?, description=?, content=?, posted_date=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $title, $description, $content, $postedDate, $blogId);
        }
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO blogs (title, description, content, posted_date, image, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssssi', $title, $description, $content, $postedDate, $image, $_SESSION['user_id']);
    }
    mysqli_stmt_execute($stmt);
    $newId = $blogId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('blog', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Blog saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Blog saved successfully.';
    }
    redirect('blogs.php');
}

$editBlog = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM blogs WHERE id = $id");
    $editBlog = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT b.*, u.name as creator FROM blogs b LEFT JOIN users u ON b.created_by = u.id ORDER BY COALESCE(b.posted_date, b.created_at) DESC");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editBlog ? 'Edit Blog' : 'Blogs' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editBlog ? '← Back' : 'Add Blog' ?></a>
    </div>
    <?php if ($editBlog || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="blog_id" value="<?= $editBlog['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" value="<?= sanitizeInput($editBlog['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Short Description</label>
            <textarea name="description" class="form-control" style="min-height:80px;"><?= sanitizeInput($editBlog['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Content</label>
            <textarea name="content" id="blog-content" class="form-control" style="min-height:200px;"><?= sanitizeInput($editBlog['content'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Image <?= $editBlog ? '(Leave empty to keep current)' : '' ?></label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($editBlog['image'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editBlog['image']) ?>" style="max-height:100px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Posted Date</label>
                <input type="date" name="posted_date" class="form-control" value="<?= sanitizeInput($editBlog['posted_date'] ?? date('Y-m-d')) ?>">
            </div>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Blog</button>
    </form>
    <script>document.addEventListener('DOMContentLoaded', function() { var el = document.getElementById('blog-content'); if (el) makeEditor('blog-content'); });</script>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Posted Date</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php if ($row['image']): ?><img src="<?= SITE_URL . '/' . sanitizeInput($row['image']) ?>" style="height:40px;width:60px;object-fit:cover;border-radius:4px;"><?php else: ?>-<?php endif; ?></td>
                <td><strong><?= sanitizeInput($row['title']) ?></strong></td>
                <td><?= $row['posted_date'] ? date('d M Y', strtotime($row['posted_date'])) : date('d M Y', strtotime($row['created_at'])) ?></td>
                <td><?= sanitizeInput($row['creator'] ?? 'N/A') ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this blog?">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
