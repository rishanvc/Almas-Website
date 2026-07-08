<?php
$pageTitle = 'Manage Doctors';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM doctors WHERE id = $id");
    $_SESSION['success'] = 'Doctor deleted.';
    redirect('doctors.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = sanitize($_POST['name']);
    $designation = sanitize($_POST['designation']);
    $qualification = sanitize($_POST['qualification']);
    $specialization = sanitize($_POST['specialization']);
    $experience = sanitize($_POST['experience']);
    $profile = $_POST['profile'];
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $status = sanitize($_POST['status']);
    $docId = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;
    $photo = '';

    if (!empty($_FILES['photo']['name'])) {
        $upload = uploadFile($_FILES['photo'], UPLOAD_PATH . '/doctors');
        if ($upload['success']) $photo = $upload['path'];
    }

    if ($docId) {
        if ($photo) {
            $stmt = mysqli_prepare($conn, "UPDATE doctors SET department_id=?, name=?, designation=?, qualification=?, specialization=?, experience=?, profile=?, photo=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'issssssssi', $departmentId, $name, $designation, $qualification, $specialization, $experience, $profile, $photo, $status, $docId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE doctors SET department_id=?, name=?, designation=?, qualification=?, specialization=?, experience=?, profile=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'isssssssi', $departmentId, $name, $designation, $qualification, $specialization, $experience, $profile, $status, $docId);
        }
    } else {
        $photoField = $photo ?: '';
        $stmt = mysqli_prepare($conn, "INSERT INTO doctors (department_id, name, designation, qualification, specialization, experience, profile, photo, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issssssssi', $departmentId, $name, $designation, $qualification, $specialization, $experience, $profile, $photoField, $status, $_SESSION['user_id']);
    }
    mysqli_stmt_execute($stmt);
    $newId = $docId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('doctor', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Doctor saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Doctor saved successfully.';
    }
    redirect('doctors.php');
}

$editDoc = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM doctors WHERE id = $id");
    $editDoc = mysqli_fetch_assoc($result);
}
$userId = $_SESSION['user_id'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !getUserAssignAllStatus($userId)) {
    $departments = getAccessibleDepartments($userId);
    $assignedIds = getUserAssignedDepartments($userId);
    if (empty($assignedIds)) {
        $result = mysqli_query($conn, "SELECT d.*, dep.department_name, u.name as creator FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id LEFT JOIN users u ON d.created_by = u.id WHERE 1=0 ORDER BY d.name");
    } else {
        $ids = implode(',', array_map('intval', $assignedIds));
        $result = mysqli_query($conn, "SELECT d.*, dep.department_name, u.name as creator FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id LEFT JOIN users u ON d.created_by = u.id WHERE d.department_id IN ($ids) ORDER BY d.name");
    }
} else {
    $departments = getActiveDepartments();
    $result = mysqli_query($conn, "SELECT d.*, dep.department_name, u.name as creator FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id LEFT JOIN users u ON d.created_by = u.id ORDER BY d.name");
}
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editDoc ? 'Edit Doctor' : 'Doctors' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editDoc ? '← Back' : 'Add Doctor' ?></a>
    </div>
    <?php if ($editDoc || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="doc_id" value="<?= $editDoc['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="<?= sanitizeInput($editDoc['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="designation" class="form-control" value="<?= sanitizeInput($editDoc['designation'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= ($editDoc['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>><?= sanitizeInput($dept['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Specialization *</label>
                <input type="text" name="specialization" class="form-control" value="<?= sanitizeInput($editDoc['specialization'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Qualification *</label>
                <input type="text" name="qualification" class="form-control" value="<?= sanitizeInput($editDoc['qualification'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Experience</label>
                <input type="text" name="experience" class="form-control" value="<?= sanitizeInput($editDoc['experience'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Profile/Biography</label>
            <textarea name="profile" class="form-control" style="min-height:150px;"><?= sanitizeInput($editDoc['profile'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Photo</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
                <?php if (!empty($editDoc['photo'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editDoc['photo']) ?>" style="max-height:80px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?= ($editDoc['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($editDoc['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Doctor</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Department</th>
                <th>Specialization</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= sanitizeInput($row['name']) ?></strong></td>
                <td><?= sanitizeInput($row['department_name'] ?? '-') ?></td>
                <td><?= sanitizeInput($row['specialization']) ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if (hasRole('Administrator')): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this doctor?">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
