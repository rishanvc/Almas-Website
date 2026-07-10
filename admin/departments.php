<?php
$pageTitle = 'Manage Departments';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM departments WHERE id = $id");
    $_SESSION['success'] = 'Department deleted.';
    redirect('departments.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = sanitize($_POST['name']);
    $desc = $_POST['description'];
    $status = sanitize($_POST['status']);
    $deptId = isset($_POST['dept_id']) ? (int)$_POST['dept_id'] : 0;
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/departments');
        if ($upload['success']) $image = $upload['path'];
    }

    if (!hasRole('Administrator') && !hasRole('Content Approver')) $status = 'Active';

    if ($deptId) {
        if ($image) {
            $stmt = mysqli_prepare($conn, "UPDATE departments SET department_name=?, description=?, image=?, status=?, created_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssii', $name, $desc, $image, $status, $_SESSION['user_id'], $deptId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE departments SET department_name=?, description=?, status=?, created_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssii', $name, $desc, $status, $_SESSION['user_id'], $deptId);
        }
    } else {
        if ($image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO departments (department_name, description, image, created_by) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssi', $name, $desc, $image, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO departments (department_name, description, created_by) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssi', $name, $desc, $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    $newId = $deptId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('department', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Department saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Department saved successfully.';
    }
    redirect('departments.php');
}

$editDept = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM departments WHERE id = $id");
    $editDept = mysqli_fetch_assoc($result);
}
$userId = $_SESSION['user_id'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !getUserAssignAllStatus($userId)) {
    $assignedIds = getUserAssignedDepartments($userId);
    if (empty($assignedIds)) {
        $result = mysqli_query($conn, "SELECT d.*, u.name as creator FROM departments d LEFT JOIN users u ON d.created_by = u.id WHERE 1=0 ORDER BY d.department_name");
    } else {
        $ids = implode(',', array_map('intval', $assignedIds));
        $result = mysqli_query($conn, "SELECT d.*, u.name as creator FROM departments d LEFT JOIN users u ON d.created_by = u.id WHERE d.id IN ($ids) ORDER BY d.department_name");
    }
} else {
    $result = mysqli_query($conn, "SELECT d.*, u.name as creator FROM departments d LEFT JOIN users u ON d.created_by = u.id ORDER BY d.department_name");
}
?>
<div class="table-container">
    <div class="header">
        <h5><?= $editDept ? 'Edit Department' : 'Departments' ?></h5>
        <a href="?add=1" class="btn btn-sm btn-primary"><?= $editDept ? '← Back' : 'Add Department' ?></a>
    </div>
    <?php if ($editDept || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="dept_id" value="<?= $editDept['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Department Name *</label>
            <input type="text" name="name" class="form-control" value="<?= sanitizeInput($editDept['department_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" id="dept-description" class="form-control" style="min-height:200px;" required><?= sanitizeInput($editDept['description'] ?? '') ?></textarea>
            <small style="color:#94a3b8;">Use the toolbar to format text, add lists, and insert images.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($editDept['image'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editDept['image']) ?>" style="max-height:80px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?= ($editDept['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= ($editDept['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="submit" name="save" class="btn btn-success">Save Department</button>
    </form>
    <script>document.addEventListener('DOMContentLoaded', function() { var el = document.getElementById('dept-description'); if (el) makeEditor('dept-description'); });</script>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= sanitizeInput($row['department_name']) ?></strong></td>
                <td><span class="badge badge-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                <td><?= sanitizeInput($row['creator'] ?? 'N/A') ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if (hasRole('Administrator')): ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this department?">Delete</a>
                    <?php endif; ?>
                    <?php if (hasAnyRole(['Administrator', 'Content Creator'])): ?>
                    <a href="?facilities=<?= $row['id'] ?>" class="btn btn-sm btn-info">Facilities</a>
                    <a href="?sections=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Units</a>
                    <a href="?faq=<?= $row['id'] ?>" class="btn btn-sm btn-secondary">FAQ</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php
// Facilities management
if (isset($_GET['facilities'])):
$deptId = (int)$_GET['facilities'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !canUserAccessDepartment($_SESSION['user_id'], $deptId)) {
    $_SESSION['error'] = 'You do not have access to this department.';
    redirect('departments.php');
}
$dept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM departments WHERE id = $deptId"));
if (!$dept) { redirect('departments.php'); }

if (isset($_GET['delete_facility'])) {
    $fid = (int)$_GET['delete_facility'];
    mysqli_query($conn, "DELETE FROM department_facilities WHERE id = $fid");
    $_SESSION['success'] = 'Facility deleted.';
    redirect('departments.php?facilities=' . $deptId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_facility'])) {
    $fname = sanitize($_POST['facility_name']);
    $fdesc = sanitize($_POST['facility_desc']);
    $fid = isset($_POST['facility_id']) ? (int)$_POST['facility_id'] : 0;
    $fimage = '';

    if (!empty($_FILES['facility_image']['name'])) {
        $upload = uploadFile($_FILES['facility_image'], UPLOAD_PATH . '/departments');
        if ($upload['success']) $fimage = $upload['path'];
    }

    if ($fid) {
        if ($fimage) {
            $stmt = mysqli_prepare($conn, "UPDATE department_facilities SET facility_name=?, description=?, image=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssi', $fname, $fdesc, $fimage, $fid);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE department_facilities SET facility_name=?, description=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssi', $fname, $fdesc, $fid);
        }
    } else {
        $fdeptId = $deptId;
        if ($fimage) {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_facilities (department_id, facility_name, description, image) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isss', $fdeptId, $fname, $fdesc, $fimage);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_facilities (department_id, facility_name, description) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iss', $fdeptId, $fname, $fdesc);
        }
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['success'] = 'Facility saved.';
    redirect('departments.php?facilities=' . $deptId);
}

$editFacility = null;
if (isset($_GET['edit_facility'])) {
    $fid = (int)$_GET['edit_facility'];
    $res = mysqli_query($conn, "SELECT * FROM department_facilities WHERE id = $fid");
    $editFacility = mysqli_fetch_assoc($res);
}
$facilities = getDepartmentFacilities($deptId);
?>
<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Facilities for: <?= sanitizeInput($dept['department_name']) ?></h5>
        <a href="?facilities=<?= $deptId ?>&add_facility=1" class="btn btn-sm btn-primary"><?= $editFacility ? '← Back' : 'Add Facility' ?></a>
    </div>
    <?php if ($editFacility || isset($_GET['add_facility'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;">
        <input type="hidden" name="facility_id" value="<?= $editFacility['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Facility Name *</label>
            <input type="text" name="facility_name" class="form-control" value="<?= sanitizeInput($editFacility['facility_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="facility_desc" class="form-control"><?= sanitizeInput($editFacility['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Image</label>
            <input type="file" name="facility_image" class="form-control" accept="image/*">
            <?php if (!empty($editFacility['image'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($editFacility['image']) ?>" style="max-height:80px;margin-top:5px;">
            <?php endif; ?>
        </div>
        <button type="submit" name="save_facility" class="btn btn-success">Save Facility</button>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Facility Name</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($facilities as $fac): ?>
            <tr>
                <td><strong><?= sanitizeInput($fac['facility_name']) ?></strong></td>
                <td><?= sanitizeInput(substr($fac['description'], 0, 100)) ?></td>
                <td>
                    <a href="?facilities=<?= $deptId ?>&edit_facility=<?= $fac['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?facilities=<?= $deptId ?>&delete_facility=<?= $fac['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete?">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <div style="padding:10px 20px;">
        <a href="departments.php" class="btn btn-sm btn-primary">← Back to Departments</a>
    </div>
</div>
<?php endif; ?>

<?php
// Sections management
if (isset($_GET['sections'])):
$deptId = (int)$_GET['sections'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !canUserAccessDepartment($_SESSION['user_id'], $deptId)) {
    $_SESSION['error'] = 'You do not have access to this department.';
    redirect('departments.php');
}
$dept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM departments WHERE id = $deptId"));
if (!$dept) { redirect('departments.php'); }

// Delete section
if (isset($_GET['delete_section'])) {
    $sid = (int)$_GET['delete_section'];
    mysqli_query($conn, "DELETE FROM department_sections WHERE id = $sid");
    $_SESSION['success'] = 'Unit deleted.';
    redirect('departments.php?sections=' . $deptId);
}

// Save section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_section'])) {
    $s_title = sanitize($_POST['section_title']);
    $s_type = sanitize($_POST['section_type']);
    $s_key = sanitize($_POST['section_key']);
    $s_order = (int)$_POST['sort_order'];
    $sid = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;

    if ($s_type === 'list') {
        $items = [];
        if (!empty($_POST['item_title'])) {
            foreach ($_POST['item_title'] as $i => $itemTitle) {
                $items[] = [
                    'title' => sanitizeInput($itemTitle),
                    'description' => sanitizeInput($_POST['item_desc'][$i] ?? '')
                ];
            }
        }
        $s_content = json_encode($items, JSON_UNESCAPED_UNICODE);
    } elseif ($s_type === 'doctors') {
        $s_content = ''; // doctors auto-populate from department
    } else {
        $s_content = $_POST['content']; // raw HTML for content type
    }

    $s_image = '';
    if (!empty($_FILES['section_image']['name'])) {
        $upload = uploadFile($_FILES['section_image'], UPLOAD_PATH . '/departments');
        if ($upload['success']) $s_image = $upload['path'];
    }

    if ($sid) {
        if ($s_image) {
            $stmt = mysqli_prepare($conn, "UPDATE department_sections SET section_key=?, section_type=?, title=?, content=?, image_path=?, sort_order=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssii', $s_key, $s_type, $s_title, $s_content, $s_image, $s_order, $sid);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE department_sections SET section_key=?, section_type=?, title=?, content=?, sort_order=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssii', $s_key, $s_type, $s_title, $s_content, $s_order, $sid);
        }
    } else {
        if ($s_image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_sections (department_id, section_key, section_type, title, content, image_path, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isssssii', $deptId, $s_key, $s_type, $s_title, $s_content, $s_image, $s_order, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_sections (department_id, section_key, section_type, title, content, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'issssii', $deptId, $s_key, $s_type, $s_title, $s_content, $s_order, $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    $newSid = $sid ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('department_section', $newSid, $_SESSION['user_id']);
        $_SESSION['success'] = 'Section saved and submitted for approval.';
    } else {
        // Admin/Approver auto-publishes
        $pubStatus = 'Published';
        mysqli_query($conn, "UPDATE department_sections SET status='$pubStatus' WHERE id=$newSid");
        $_SESSION['success'] = 'Section saved and published.';
    }
    redirect('departments.php?sections=' . $deptId);
}

$editSection = null;
if (isset($_GET['edit_section'])) {
    $sid = (int)$_GET['edit_section'];
    $editSection = getDepartmentSection($sid);
}
$sections = getDepartmentSections($deptId, 'all'); // fetch all regardless of status
// custom function to fetch all since getDepartmentSections filters by status
$allSections = [];
$r = mysqli_query($conn, "SELECT * FROM department_sections WHERE department_id = $deptId ORDER BY sort_order ASC");
while ($row = mysqli_fetch_assoc($r)) { $allSections[] = $row; }
?>
<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Custom Units for: <?= sanitizeInput($dept['department_name']) ?></h5>
        <a href="?sections=<?= $deptId ?>&add_section=1" class="btn btn-sm btn-primary"><?= $editSection ? '← Back' : 'Add Unit' ?></a>
    </div>
    <?php if ($editSection || isset($_GET['add_section'])): ?>
    <form method="POST" action="" style="padding:20px;">
        <input type="hidden" name="section_id" value="<?= $editSection['id'] ?? 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Section Key *</label>
                <input type="text" name="section_key" class="form-control" value="<?= sanitizeInput($editSection['section_key'] ?? '') ?>" placeholder="e.g. key_services" required>
                <small style="color:#94a3b8;">Unique identifier (lowercase, underscores)</small>
            </div>
            <div class="form-group">
                <label>Section Type *</label>
                <select name="section_type" class="form-control" id="section-type-select" required>
                    <option value="content" <?= ($editSection['section_type'] ?? '') == 'content' ? 'selected' : '' ?>>Content (Heading + HTML)</option>
                    <option value="list" <?= ($editSection['section_type'] ?? '') == 'list' ? 'selected' : '' ?>>List (Items with title + description)</option>
                    <option value="doctors" <?= ($editSection['section_type'] ?? '') == 'doctors' ? 'selected' : '' ?>>Doctors (Auto-populated)</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Section Title *</label>
            <input type="text" name="section_title" class="form-control" value="<?= sanitizeInput($editSection['title'] ?? '') ?>" placeholder="e.g. Key Services" required>
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?= (int)($editSection['sort_order'] ?? 0) ?>" min="0">
        </div>

        <!-- Content type -->
        <div class="form-group section-type-content" <?= ($editSection['section_type'] ?? 'content') != 'content' ? 'style="display:none;"' : '' ?>>
            <label>Content</label>
            <textarea name="content" id="section-content" class="form-control" style="min-height:200px;"><?= sanitizeInput($editSection['content'] ?? '') ?></textarea>
            <small style="color:#94a3b8;">Use the toolbar to format text, create lists, and insert images.</small>
        </div>
        <div class="form-group section-type-content" <?= ($editSection['section_type'] ?? 'content') != 'content' ? 'style="display:none;"' : '' ?>>
            <label>Image (optional)</label>
            <input type="file" name="section_image" class="form-control" accept="image/*">
            <?php if (!empty($editSection['image_path'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($editSection['image_path']) ?>" style="max-height:80px;margin-top:5px;border-radius:4px;">
            <input type="hidden" name="existing_image" value="<?= sanitizeInput($editSection['image_path']) ?>">
            <?php endif; ?>
        </div>

        <!-- List type -->
        <div class="section-type-list" <?= ($editSection['section_type'] ?? '') != 'list' ? 'style="display:none;"' : '' ?>>
            <label style="display:block;margin-bottom:5px;font-weight:500;color:#475569;font-size:14px;">List Items</label>
            <div id="list-items-container">
                <?php
                $items = [];
                if ($editSection && $editSection['section_type'] === 'list' && $editSection['content']) {
                    $items = json_decode($editSection['content'], true) ?: [];
                }
                if (count($items) === 0) $items = [['title' => '', 'description' => '']];
                foreach ($items as $idx => $item):
                ?>
                <div class="list-item" style="border:1px solid #e2e8f0;padding:15px;border-radius:6px;margin-bottom:10px;background:#f8fafc;">
                    <div class="form-row">
                        <div class="form-group" style="flex:1;">
                            <label>Title</label>
                            <input type="text" name="item_title[]" class="form-control" value="<?= sanitizeInput($item['title']) ?>">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Description</label>
                            <textarea name="item_desc[]" class="form-control"><?= sanitizeInput($item['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="addListItem()" style="margin-bottom:15px;">+ Add Item</button>
        </div>

        <button type="submit" name="save_section" class="btn btn-success">Save Unit</button>
        <?php if ($editSection): ?>
        <a href="?sections=<?= $deptId ?>&delete_section=<?= $editSection['id'] ?>" class="btn btn-danger" data-confirm="Delete this unit?" style="margin-left:8px;">Delete Unit</a>
        <?php endif; ?>
    </form>
    <script>
    function addListItem() {
        var container = document.getElementById('list-items-container');
        var div = document.createElement('div');
        div.className = 'list-item';
        div.style.cssText = 'border:1px solid #e2e8f0;padding:15px;border-radius:6px;margin-bottom:10px;background:#f8fafc;';
        div.innerHTML = '<div class="form-row">' +
            '<div class="form-group" style="flex:1;"><label>Title</label><input type="text" name="item_title[]" class="form-control"></div>' +
            '<div class="form-group" style="flex:1;"><label>Description</label><textarea name="item_desc[]" class="form-control"></textarea></div>' +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Remove</button>';
        container.appendChild(div);
    }
    document.getElementById('section-type-select').addEventListener('change', function() {
        document.querySelectorAll('.section-type-content').forEach(function(el) { el.style.display = this.value === 'content' ? 'block' : 'none'; }.bind(this));
        document.querySelector('.section-type-list').style.display = this.value === 'list' ? 'block' : 'none';
    });
    document.addEventListener('DOMContentLoaded', function() { var el = document.getElementById('section-content'); if (el) makeEditor('section-content'); });
    </script>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Section Key</th>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($allSections) > 0): ?>
                <?php foreach ($allSections as $sec): ?>
                <tr>
                    <td><?= (int)$sec['sort_order'] ?></td>
                    <td><code><?= sanitizeInput($sec['section_key']) ?></code></td>
                    <td><strong><?= sanitizeInput($sec['title']) ?></strong></td>
                    <td><span class="badge badge-info"><?= sanitizeInput($sec['section_type']) ?></span></td>
                    <td><span class="badge badge-<?= $sec['status'] == 'Published' ? 'success' : ($sec['status'] == 'Pending' ? 'warning' : 'secondary') ?>"><?= $sec['status'] ?></span></td>
                    <td>
                        <a href="?sections=<?= $deptId ?>&edit_section=<?= $sec['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="?sections=<?= $deptId ?>&delete_section=<?= $sec['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this unit?">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No custom units configured. Click "Add Unit" to create one.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <div style="padding:10px 20px;">
        <a href="departments.php" class="btn btn-sm btn-primary">← Back to Departments</a>
    </div>
</div>
<?php endif; ?>

<?php
// FAQ management
if (isset($_GET['faq'])):
$deptId = (int)$_GET['faq'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !canUserAccessDepartment($_SESSION['user_id'], $deptId)) {
    $_SESSION['error'] = 'You do not have access to this department.';
    redirect('departments.php');
}
$dept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM departments WHERE id = $deptId"));
if (!$dept) { redirect('departments.php'); }

// Delete FAQ
if (isset($_GET['delete_faq'])) {
    $fid = (int)$_GET['delete_faq'];
    mysqli_query($conn, "DELETE FROM department_faqs WHERE id = $fid");
    $_SESSION['success'] = 'FAQ deleted.';
    redirect('departments.php?faq=' . $deptId);
}

// Save FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq'])) {
    $question = sanitize($_POST['faq_question']);
    $answer = $_POST['faq_answer'];
    $order = (int)$_POST['faq_order'];
    $fid = isset($_POST['faq_id']) ? (int)$_POST['faq_id'] : 0;

    if ($fid) {
        $stmt = mysqli_prepare($conn, "UPDATE department_faqs SET question=?, answer=?, sort_order=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssii', $question, $answer, $order, $fid);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO department_faqs (department_id, question, answer, sort_order, created_by) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issii', $deptId, $question, $answer, $order, $_SESSION['user_id']);
    }
    mysqli_stmt_execute($stmt);
    $newFid = $fid ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('department_faq', $newFid, $_SESSION['user_id']);
        $_SESSION['success'] = 'FAQ saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'FAQ saved successfully.';
    }
    redirect('departments.php?faq=' . $deptId);
}

$editFAQ = null;
if (isset($_GET['edit_faq'])) {
    $fid = (int)$_GET['edit_faq'];
    $editFAQ = getDepartmentFAQ($fid);
}
$faqs = getDepartmentFAQs($deptId);
?>
<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>FAQs for: <?= sanitizeInput($dept['department_name']) ?></h5>
        <a href="?faq=<?= $deptId ?>&add_faq=1" class="btn btn-sm btn-primary"><?= $editFAQ ? '← Back' : 'Add FAQ' ?></a>
    </div>
    <?php if ($editFAQ || isset($_GET['add_faq'])): ?>
    <form method="POST" action="" style="padding:20px;">
        <input type="hidden" name="faq_id" value="<?= $editFAQ['id'] ?? 0 ?>">
        <div class="form-group">
            <label>Question *</label>
            <input type="text" name="faq_question" class="form-control" value="<?= sanitizeInput($editFAQ['question'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Answer *</label>
            <textarea name="faq_answer" id="faq-answer" class="form-control" style="min-height:150px;" required><?= sanitizeInput($editFAQ['answer'] ?? '') ?></textarea>
            <small style="color:#94a3b8;">Use the toolbar to format the answer text.</small>
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="faq_order" class="form-control" value="<?= (int)($editFAQ['sort_order'] ?? 0) ?>" min="0">
        </div>
        <button type="submit" name="save_faq" class="btn btn-success">Save FAQ</button>
        <?php if ($editFAQ): ?>
        <a href="?faq=<?= $deptId ?>&delete_faq=<?= $editFAQ['id'] ?>" class="btn btn-danger" data-confirm="Delete this FAQ?" style="margin-left:8px;">Delete FAQ</a>
        <?php endif; ?>
    </form>
    <script>document.addEventListener('DOMContentLoaded', function() { var el = document.getElementById('faq-answer'); if (el) makeEditor('faq-answer'); });</script>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Question</th>
                <th>Answer</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($faqs) > 0): ?>
                <?php foreach ($faqs as $faq): ?>
                <tr>
                    <td><?= (int)$faq['sort_order'] ?></td>
                    <td><strong><?= sanitizeInput($faq['question']) ?></strong></td>
                    <td><?= sanitizeInput(substr(strip_tags($faq['answer']), 0, 120)) ?>...</td>
                    <td>
                        <a href="?faq=<?= $deptId ?>&edit_faq=<?= $faq['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="?faq=<?= $deptId ?>&delete_faq=<?= $faq['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this FAQ?">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No FAQs configured. Click "Add FAQ" to create one.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <div style="padding:10px 20px;">
        <a href="departments.php" class="btn btn-sm btn-primary">← Back to Departments</a>
    </div>
</div>
<?php endif; ?>

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
</script>
<?php require_once 'footer.php'; ?>
