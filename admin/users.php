<?php
$pageTitle = 'Manage Users';
require_once 'header.php';
requireRole('Administrator');

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id");
        $_SESSION['success'] = 'User deleted successfully.';
    }
    redirect('users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $phone = sanitize($_POST['phone']);
    $status = sanitize($_POST['status']);
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($role)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
    } else {
        if ($userId) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=?, phone=?, status=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $email, $role, $phone, $status, $hashed, $userId);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=?, phone=?, status=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $role, $phone, $status, $userId);
            }
        } else {
            if (empty($password)) {
                $_SESSION['error'] = 'Password is required for new users.';
                redirect('users.php');
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $hashed, $role, $phone, $status);
        }
        mysqli_stmt_execute($stmt);
        $savedId = $userId ?: mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Save department assignments for Content Creator
        if ($role === 'Content Creator') {
            $assignAll = isset($_POST['assign_all']) ? 1 : 0;
            mysqli_query($conn, "UPDATE users SET assign_all_departments = $assignAll WHERE id = $savedId");
            // Clear existing assignments
            mysqli_query($conn, "DELETE FROM user_departments WHERE user_id = $savedId");
            if (!$assignAll && !empty($_POST['departments'])) {
                $deptIds = array_map('intval', $_POST['departments']);
                foreach ($deptIds as $deptId) {
                    mysqli_query($conn, "INSERT INTO user_departments (user_id, department_id) VALUES ($savedId, $deptId)");
                }
            }
        } else {
            // Clear department assignments for non-Content Creator roles
            mysqli_query($conn, "UPDATE users SET assign_all_departments = 0 WHERE id = $savedId");
            mysqli_query($conn, "DELETE FROM user_departments WHERE user_id = $savedId");
        }

        $_SESSION['success'] = 'User saved successfully.';
        redirect('users.php');
    }
}

$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    $editUser = mysqli_fetch_assoc($result);
}
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editUser ? 'Edit User' : 'All Users' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editUser ? '← Back' : 'Add New User' ?></a>
    </div>
    <?php if ($editUser || isset($_GET['add'])): ?>
    <form method="POST" action="" style="padding:20px;">
        <input type="hidden" name="user_id" value="<?= $editUser['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" class="form-control" value="<?= sanitizeInput($editUser['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="<?= sanitizeInput($editUser['email'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password <?= $editUser ? '(Leave blank to keep current)' : '*' ?></label>
                <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" class="form-control" required>
                    <option value="Administrator" <?= ($editUser['role'] ?? '') == 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                    <option value="Content Creator" <?= ($editUser['role'] ?? '') == 'Content Creator' ? 'selected' : '' ?>>Content Creator</option>
                    <option value="Content Approver" <?= ($editUser['role'] ?? '') == 'Content Approver' ? 'selected' : '' ?>>Content Approver</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= sanitizeInput($editUser['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?= ($editUser['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($editUser['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <!-- Department Assignment (only for Content Creator) -->
        <div id="department-assignment" style="border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:18px;<?= ($editUser['role'] ?? '') === 'Content Creator' ? '' : 'display:none;' ?>">
            <h6 style="margin-bottom:12px;color:#333;">Department Access</h6>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="assign_all" value="1" id="assign-all" <?= ($editUser['assign_all_departments'] ?? 0) ? 'checked' : '' ?>>
                    Assign to All Departments
                </label>
            </div>
            <div id="department-list" style="<?= ($editUser['assign_all_departments'] ?? 0) ? 'display:none;' : '' ?>">
                <label style="display:block;margin-bottom:8px;font-weight:500;color:#475569;font-size:14px;">Select Departments</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
                    <?php
                    $allDepts = getActiveDepartments();
                    $assignedDepts = [];
                    if ($editUser) {
                        $assignedDepts = getUserAssignedDepartments($editUser['id']);
                    }
                    foreach ($allDepts as $dept):
                    ?>
                    <label style="font-weight:400;font-size:13px;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>" <?= in_array($dept['id'], $assignedDepts) ? 'checked' : '' ?>>
                        <?= sanitizeInput($dept['department_name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        document.querySelector('select[name="role"]').addEventListener('change', function() {
            var div = document.getElementById('department-assignment');
            div.style.display = this.value === 'Content Creator' ? 'block' : 'none';
        });
        document.getElementById('assign-all').addEventListener('change', function() {
            document.getElementById('department-list').style.display = this.checked ? 'none' : 'block';
        });
        </script>

        <button type="submit" name="save" class="btn btn-success">Save User</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= sanitizeInput($row['name']) ?></td>
                <td><?= sanitizeInput($row['email']) ?></td>
                <td><span class="badge badge-primary"><?= sanitizeInput($row['role']) ?></span></td>
                <td><?= sanitizeInput($row['phone'] ?? '-') ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this user?">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>
