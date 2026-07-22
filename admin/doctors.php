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
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;" id="doctor-form">
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
            <textarea name="profile" id="doctor-profile" class="form-control" style="min-height:150px;"><?= sanitizeInput($editDoc['profile'] ?? '') ?></textarea>
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

<style>
.editor-toolbar { display:flex; gap:4px; flex-wrap:wrap; padding:8px; background:#f8fafc; border:1px solid #cbd5e1; border-bottom:0; border-radius:6px 6px 0 0; }
.editor-toolbar button { padding:5px 10px; background:#fff; border:1px solid #e2e8f0; border-radius:4px; cursor:pointer; font-size:13px; color:#475569; transition:all 0.2s; }
.editor-toolbar button:hover { background:#f1f5f9; border-color:#94a3b8; }
.editor-toolbar .sep { width:1px; background:#e2e8f0; margin:2px 4px; }
.editor-content { min-height:200px; padding:12px; border:1px solid #cbd5e1; border-radius:0 0 6px 6px; font-size:14px; line-height:1.7; background:#fff; outline:none; }
.editor-content:focus { border-color:#981c4e; box-shadow:0 0 0 3px rgba(152,28,78,0.12); }
.editor-content ul, .editor-content ol { padding-left:24px; margin:8px 0; }
.editor-content li { margin-bottom:4px; }
.editor-content li > ul, .editor-content li > ol { margin:4px 0; }
.editor-content p { margin-bottom:8px; }
</style>

<script>
function makeEditor(textareaId) {
    var ta = document.getElementById(textareaId);
    if (!ta) return;
    var wrapper = document.createElement('div');
    wrapper.className = 'editor-wrapper';
    ta.parentNode.insertBefore(wrapper, ta);
    wrapper.appendChild(ta);
    var toolbar = document.createElement('div');
    toolbar.className = 'editor-toolbar';
    toolbar.innerHTML =
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'bold\')" title="Bold"><b>B</b></button>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'italic\')" title="Italic"><i>I</i></button>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'underline\')" title="Underline"><u>U</u></button>' +
        '<span class="sep"></span>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'insertUnorderedList\')" title="Bullet List"><i class="fas fa-list-ul"></i></button>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'insertOrderedList\')" title="Numbered List"><i class="fas fa-list-ol"></i></button>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'indent\')" title="Indent"><i class="fas fa-indent"></i></button>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="execCmd(\'outdent\')" title="Outdent"><i class="fas fa-outdent"></i></button>' +
        '<span class="sep"></span>' +
        '<button type="button" onmousedown="event.preventDefault()" onclick="insertLinkCmd()" title="Insert Link"><i class="fas fa-link"></i></button>';
    wrapper.insertBefore(toolbar, ta);
    var editor = document.createElement('div');
    editor.className = 'editor-content';
    editor.contentEditable = true;
    editor.innerHTML = ta.value;
    editor.dataset.target = textareaId;
    editor.oninput = function() { document.getElementById(this.dataset.target).value = this.innerHTML; };
    wrapper.insertBefore(editor, ta);
    ta.style.display = 'none';
}
function syncAllEditors() {
    document.querySelectorAll('.editor-content').forEach(function(el) {
        var ta = document.getElementById(el.dataset.target);
        if (ta) ta.value = el.innerHTML;
    });
}
function execCmd(cmd) {
    document.execCommand(cmd, false, null);
    syncAllEditors();
}
function insertLinkCmd() {
    var url = prompt('Enter URL:');
    if (url) { document.execCommand('createLink', false, url); syncAllEditors(); }
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('doctor-profile')) makeEditor('doctor-profile');
});
document.getElementById('doctor-form').addEventListener('submit', function() {
    syncAllEditors();
});
</script>

<?php require_once 'footer.php'; ?>
