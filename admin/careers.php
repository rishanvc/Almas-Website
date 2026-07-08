<?php
$pageTitle = 'Manage Careers';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM careers WHERE id = $id");
    $_SESSION['success'] = 'Career deleted.';
    redirect('careers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $title = sanitize($_POST['title']);
    $department = sanitize($_POST['department']);
    $description = $_POST['description'];
    $qualification = $_POST['qualification'];
    $deadline = !empty($_POST['deadline']) ? sanitize($_POST['deadline']) : null;
    $status = sanitize($_POST['status']);
    $careerId = isset($_POST['career_id']) ? (int)$_POST['career_id'] : 0;

    if ($careerId) {
        $stmt = mysqli_prepare($conn, "UPDATE careers SET job_title=?, department=?, description=?, qualification=?, deadline=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $title, $department, $description, $qualification, $deadline, $status, $careerId);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO careers (job_title, department, description, qualification, deadline, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $title, $department, $description, $qualification, $deadline, $status, $_SESSION['user_id']);
    }
    mysqli_stmt_execute($stmt);
    $newId = $careerId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('career', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Career saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Career saved successfully.';
    }
    redirect('careers.php');
}

$editCareer = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM careers WHERE id = $id");
    $editCareer = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT c.*, u.name as creator FROM careers c LEFT JOIN users u ON c.created_by = u.id ORDER BY c.created_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editCareer ? 'Edit Career' : 'Careers & Job Vacancies' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editCareer ? '← Back' : 'Add New Job' ?></a>
    </div>
    <?php if ($editCareer || isset($_GET['add'])): ?>
    <form method="POST" action="" style="padding:20px;">
        <input type="hidden" name="career_id" value="<?= $editCareer['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Job Title *</label>
                <input type="text" name="title" class="form-control" value="<?= sanitizeInput($editCareer['job_title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" class="form-control" value="<?= sanitizeInput($editCareer['department'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" class="form-control" style="min-height:200px;" required><?= sanitizeInput($editCareer['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Requirements/Qualifications</label>
            <textarea name="qualification" class="form-control" style="min-height:100px;"><?= sanitizeInput($editCareer['qualification'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Application Deadline</label>
                <input type="date" name="deadline" class="form-control" value="<?= $editCareer['deadline'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Open" <?= ($editCareer['status'] ?? 'Open') == 'Open' ? 'selected' : '' ?>>Open</option>
                    <option value="Closed" <?= ($editCareer['status'] ?? '') == 'Closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Job</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Job Title</th>
                <th>Department</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Applications</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)):
            $appCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM job_applications WHERE career_id = {$row['id']}"))['c'];
            ?>
            <tr>
                <td><strong><?= sanitizeInput($row['job_title']) ?></strong></td>
                <td><?= sanitizeInput($row['department'] ?? '-') ?></td>
                <td><?= $row['deadline'] ? date('d M Y', strtotime($row['deadline'])) : '-' ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Open' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                <td><?= $appCount ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?applications=<?= $row['id'] ?>" class="btn btn-sm btn-info">Applications</a>
                    <?php if (hasRole('Administrator')): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete?">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php
if (isset($_GET['applications'])):
$careerId = (int)$_GET['applications'];
$job = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM careers WHERE id = $careerId"));
if ($job):
$apps = mysqli_query($conn, "SELECT * FROM job_applications WHERE career_id = $careerId ORDER BY applied_at DESC");
?>
<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Applications for: <?= sanitizeInput($job['job_title']) ?></h5>
        <a href="careers.php" class="btn btn-sm btn-primary">← Back</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Applicant</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Resume</th>
                <th>Applied On</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($app = mysqli_fetch_assoc($apps)): ?>
            <tr>
                <td><strong><?= sanitizeInput($app['applicant_name']) ?></strong></td>
                <td><?= sanitizeInput($app['email']) ?></td>
                <td><?= sanitizeInput($app['phone']) ?></td>
                <td><a href="<?= SITE_URL . '/' . sanitizeInput($app['resume']) ?>" target="_blank" class="btn btn-sm btn-primary">View Resume</a></td>
                <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
            </tr>
            <?php if ($app['cover_letter']): ?>
            <tr><td colspan="5"><strong>Cover Letter:</strong> <?= sanitizeInput($app['cover_letter']) ?></td></tr>
            <?php endif; ?>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; endif; ?>
<?php require_once 'footer.php'; ?>
