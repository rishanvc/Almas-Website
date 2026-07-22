<?php
$pageTitle = 'Manage Departments';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

// === ALL POST/DELETE/REDIRECT HANDLERS (before any HTML output) ===
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM departments WHERE id = $id");
    $_SESSION['success'] = 'Department deleted.';
    redirect('departments.php');
}

// Department save
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

    // Intro image
    $introImgs = [];
    if ($deptId) {
        $oldDept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT description_images FROM departments WHERE id = $deptId"));
        if ($oldDept && $oldDept['description_images']) {
            $introImgs = json_decode($oldDept['description_images'], true) ?: [];
        }
    }
    if (!empty($_FILES['intro_image']['name'])) {
        $upload = uploadFile($_FILES['intro_image'], UPLOAD_PATH . '/departments');
        if ($upload['success']) $introImgs = [$upload['path']];
    }
    if (isset($_POST['remove_intro_image']) && $_POST['remove_intro_image']) {
        $introImgs = [];
    }
    $descriptionImages = !empty($introImgs) ? json_encode($introImgs) : null;

    if (!hasRole('Administrator') && !hasRole('Content Approver')) $status = 'Active';

    if ($deptId) {
        $setParts = ['department_name=?', 'description=?', 'description_images=?'];
        $params   = [$name, $desc, $descriptionImages];
        $types    = 'sss';
        if ($image) {
            $setParts[] = 'image=?';
            $params[]   = $image;
            $types     .= 's';
        }
        $setParts[] = 'status=?';
        $params[]   = $status;
        $types     .= 's';
        $setParts[] = 'created_by=?';
        $params[]   = $_SESSION['user_id'];
        $types     .= 'i';
        $params[]   = $deptId;
        $types     .= 'i';
        $stmt = mysqli_prepare($conn, "UPDATE departments SET " . implode(', ', $setParts) . " WHERE id=?");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO departments (department_name, description, description_images, image, created_by) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $desc, $descriptionImages, $image ?: null, $_SESSION['user_id']);
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

// Facilities delete
if (isset($_GET['facilities']) && isset($_GET['delete_facility'])) {
    $deptId = (int)$_GET['facilities'];
    $fid = (int)$_GET['delete_facility'];
    mysqli_query($conn, "DELETE FROM department_facilities WHERE id = $fid");
    $_SESSION['success'] = 'Facility deleted.';
    redirect('departments.php?facilities=' . $deptId);
}

// Facilities save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_facility'])) {
    $deptId = (int)$_GET['facilities'];
    $fname = sanitize($_POST['facility_name']);
    $fdesc = sanitize($_POST['facility_desc']);
    $fid = isset($_POST['facility_id']) ? (int)$_POST['facility_id'] : 0;
    $fimage = '';

    if (!empty($_FILES['facility_image']['name'])) {
        $upload = uploadFile($_FILES['facility_image'], UPLOAD_PATH . '/departments');
        if ($upload['success']) $fimage = $upload['path'];
    }

    // Build content JSON: paragraphs + list items
    $paragraphs = json_decode($_POST['paragraphs_data'] ?? '[]', true) ?: [];
    $listItems  = json_decode($_POST['list_items_data'] ?? '[]', true) ?: [];
    $fcontent   = json_encode(['paragraphs' => $paragraphs, 'items' => $listItems], JSON_UNESCAPED_UNICODE);

    if ($fid) {
        if ($fimage) {
            $stmt = mysqli_prepare($conn, "UPDATE department_facilities SET facility_name=?, description=?, content=?, image=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $fname, $fdesc, $fcontent, $fimage, $fid);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE department_facilities SET facility_name=?, description=?, content=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssi', $fname, $fdesc, $fcontent, $fid);
        }
    } else {
        $fdeptId = $deptId;
        if ($fimage) {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_facilities (department_id, facility_name, description, content, image) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'issss', $fdeptId, $fname, $fdesc, $fcontent, $fimage);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_facilities (department_id, facility_name, description, content) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isss', $fdeptId, $fname, $fdesc, $fcontent);
        }
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['success'] = 'Facility saved.';
    redirect('departments.php?facilities=' . $deptId);
}

// === Unit (Section) Delete ===
if (isset($_GET['sections']) && isset($_GET['delete_section'])) {
    $deptId = (int)$_GET['sections'];
    $sid = (int)$_GET['delete_section'];
    mysqli_query($conn, "DELETE FROM department_sections WHERE id = $sid");
    $_SESSION['success'] = 'Unit deleted.';
    redirect('departments.php?sections=' . $deptId);
}

// === Unit (Section) Save ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_section'])) {
    $deptId = (int)$_GET['sections'];
    $s_title = sanitize($_POST['section_title']);
    $s_type = sanitize($_POST['section_type']);
    $s_key = sanitize($_POST['section_key'] ?? '');
    $s_subtitle = sanitize($_POST['section_subtitle'] ?? '');
    $s_button_text = sanitize($_POST['button_text'] ?? '');
    $s_button_url = sanitize($_POST['button_url'] ?? '');
    $s_order = (int)$_POST['sort_order'];
    $sid = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;

    if (empty($s_key)) {
        $s_key = preg_replace('/[^a-z0-9]+/', '_', strtolower($s_title));
        $s_key = trim($s_key, '_');
    }

    // Build content JSON based on layout type
    $s_content = '';
    if ($s_type === 'list') {
        $listData = json_decode($_POST['list_items_data'] ?? '[]', true) ?: [];
        $s_content = json_encode(['items' => $listData], JSON_UNESCAPED_UNICODE);
    } elseif ($s_type === 'gallery') {
        $existingImages = json_decode($_POST['existing_gallery_data'] ?? '[]', true) ?: [];
        $newImages = [];
        if (!empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $i => $name) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $name,
                        'type' => $_FILES['gallery_images']['type'][$i],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                        'error' => $_FILES['gallery_images']['error'][$i],
                        'size' => $_FILES['gallery_images']['size'][$i]
                    ];
                    $upload = uploadFile($file, UPLOAD_PATH . '/departments');
                    if ($upload['success']) $newImages[] = $upload['path'];
                }
            }
        }
        $allImages = array_merge($existingImages, $newImages);
        $s_content = json_encode(['images' => $allImages], JSON_UNESCAPED_UNICODE);
    } elseif ($s_type !== 'doctors') {
        $paragraphs = json_decode($_POST['paragraphs_data'] ?? '[]', true) ?: [];
        $s_content = json_encode(['paragraphs' => $paragraphs], JSON_UNESCAPED_UNICODE);
    }

    // Handle unit image
    $s_image = '';
    if (!empty($_FILES['section_image']['name'])) {
        $upload = uploadFile($_FILES['section_image'], UPLOAD_PATH . '/departments');
        if ($upload['success']) $s_image = $upload['path'];
    }

    if ($sid) {
        if ($s_image) {
            $stmt = mysqli_prepare($conn, "UPDATE department_sections SET section_key=?, section_type=?, title=?, subtitle=?, content=?, image_path=?, button_text=?, button_url=?, sort_order=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssssssii', $s_key, $s_type, $s_title, $s_subtitle, $s_content, $s_image, $s_button_text, $s_button_url, $s_order, $sid);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE department_sections SET section_key=?, section_type=?, title=?, subtitle=?, content=?, button_text=?, button_url=?, sort_order=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssssii', $s_key, $s_type, $s_title, $s_subtitle, $s_content, $s_button_text, $s_button_url, $s_order, $sid);
        }
    } else {
        if ($s_image) {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_sections (department_id, section_key, section_type, title, subtitle, content, image_path, button_text, button_url, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'issssssssi', $deptId, $s_key, $s_type, $s_title, $s_subtitle, $s_content, $s_image, $s_button_text, $s_button_url, $s_order, $_SESSION['user_id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO department_sections (department_id, section_key, section_type, title, subtitle, content, button_text, button_url, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'issssssssi', $deptId, $s_key, $s_type, $s_title, $s_subtitle, $s_content, $s_button_text, $s_button_url, $s_order, $_SESSION['user_id']);
        }
    }
    mysqli_stmt_execute($stmt);
    $newSid = $sid ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('department_section', $newSid, $_SESSION['user_id']);
        $_SESSION['success'] = 'Unit saved and submitted for approval.';
    } else {
        $pubStatus = 'Published';
        mysqli_query($conn, "UPDATE department_sections SET status='$pubStatus' WHERE id=$newSid");
        $_SESSION['success'] = 'Unit saved and published.';
    }
    redirect('departments.php?sections=' . $deptId);
}

// FAQ delete
if (isset($_GET['faq']) && isset($_GET['delete_faq'])) {
    $deptId = (int)$_GET['faq'];
    $fid = (int)$_GET['delete_faq'];
    mysqli_query($conn, "DELETE FROM department_faqs WHERE id = $fid");
    $_SESSION['success'] = 'FAQ deleted.';
    redirect('departments.php?faq=' . $deptId);
}

// FAQ save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq'])) {
    $deptId = (int)$_GET['faq'];
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

// === DISPLAY LOGIC ===

// Facilities page
if (isset($_GET['facilities'])):
$deptId = (int)$_GET['facilities'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !canUserAccessDepartment($_SESSION['user_id'], $deptId)) {
    $_SESSION['error'] = 'You do not have access to this department.';
    redirect('departments.php');
}
$dept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM departments WHERE id = $deptId"));
if (!$dept) { redirect('departments.php'); }

$editFacility = null;
if (isset($_GET['edit_facility'])) {
    $fid = (int)$_GET['edit_facility'];
    $res = mysqli_query($conn, "SELECT * FROM department_facilities WHERE id = $fid");
    $editFacility = mysqli_fetch_assoc($res);
}
$facilities = getDepartmentFacilities($deptId);

// Decode existing content for edit form
$facExistingParagraphs = [['content' => '']];
$facExistingItems = [];
if ($editFacility) {
    $facContentData = json_decode($editFacility['content'] ?? '', true);
    if (is_array($facContentData)) {
        if (!empty($facContentData['paragraphs'])) {
            $facExistingParagraphs = $facContentData['paragraphs'];
        }
        if (!empty($facContentData['items'])) {
            $facExistingItems = $facContentData['items'];
        }
    } elseif ($editFacility['description'] && !$facContentData) {
        $facExistingParagraphs = [['content' => $editFacility['description']]];
    }
}
?>
<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Facilities for: <?= sanitizeInput($dept['department_name']) ?></h5>
        <a href="?facilities=<?= $deptId ?>&add_facility=1" class="btn btn-sm btn-primary"><?= $editFacility ? '← Back' : 'Add Facility' ?></a>
    </div>
    <?php if ($editFacility || isset($_GET['add_facility'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;" id="facility-form">
        <input type="hidden" name="facility_id" value="<?= $editFacility['id'] ?? 0 ?>">
        <input type="hidden" name="paragraphs_data" id="fac_paragraphs_data">
        <input type="hidden" name="list_items_data" id="fac_list_items_data">

        <div class="form-group">
            <label>Facility Name *</label>
            <input type="text" name="facility_name" class="form-control" value="<?= sanitizeInput($editFacility['facility_name'] ?? '') ?>" placeholder="Optional facility name">
        </div>
        <div class="form-group">
            <label>Short Description</label>
            <textarea name="facility_desc" class="form-control" rows="2" placeholder="Brief summary shown in listings"><?= sanitizeInput($editFacility['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Image</label>
            <input type="file" name="facility_image" class="form-control" accept="image/*">
            <?php if (!empty($editFacility['image'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($editFacility['image']) ?>" style="max-height:80px;margin-top:5px;border-radius:6px;">
            <input type="hidden" name="existing_image" value="<?= sanitizeInput($editFacility['image']) ?>">
            <?php endif; ?>
        </div>

        <!-- Paragraphs -->
        <div style="margin-top:15px;">
            <label style="font-weight:600;color:#475569;">Content Paragraphs</label>
            <small style="color:#94a3b8;display:block;margin-bottom:10px;">Add multiple paragraphs of content for the facility detail view.</small>
            <div id="fac-paragraphs-container">
                <?php foreach ($facExistingParagraphs as $para): ?>
                <div class="fac-paragraph-block" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">
                    <textarea name="fac_para_content[]" class="form-control" rows="3" placeholder="Paragraph content..." style="flex:1;min-height:60px;"><?= sanitizeInput($para['content'] ?? '') ?></textarea>
                    <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="facMovePara(this,-1)" title="Move up">▲</button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="facMovePara(this,1)" title="Move down">▼</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="facRemovePara(this)" title="Remove">✕</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="facAddParagraph()">+ Add Paragraph</button>
        </div>

        <!-- List Items -->
        <div style="margin-top:20px;">
            <label style="font-weight:600;color:#475569;">List Items</label>
            <small style="color:#94a3b8;display:block;margin-bottom:10px;">Add structured list items with optional nested sub-items.</small>
            <div id="fac-list-items-container">
                <?php if (!empty($facExistingItems)): ?>
                    <?php foreach ($facExistingItems as $item): ?>
                    <div class="fac-list-item-block">
                        <div style="border:1px solid #e2e8f0;padding:15px;border-radius:6px;margin-bottom:10px;background:#f8fafc;">
                            <div class="form-row">
                                <div class="form-group" style="flex:1;">
                                    <label>Title</label>
                                    <input type="text" class="form-control fac-item-title" value="<?= sanitizeInput($item['title'] ?? '') ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Description</label>
                                    <textarea class="form-control fac-item-desc" rows="2"><?= sanitizeInput($item['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="fac-sub-items-container" style="margin-left:20px;margin-top:10px;padding:10px;background:#fff;border:1px dashed #e2e8f0;border-radius:4px;">
                                <label style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Sub-items</label>
                                <?php if (!empty($item['children'])): ?>
                                    <?php foreach ($item['children'] as $child): ?>
                                    <div class="fac-sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">
                                        <input type="text" class="form-control fac-sub-title" placeholder="Sub-item title" value="<?= sanitizeInput($child['title'] ?? '') ?>" style="flex:1;">
                                        <input type="text" class="form-control fac-sub-desc" placeholder="Sub-item description" value="<?= sanitizeInput($child['description'] ?? '') ?>" style="flex:2;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="facRemoveSubItem(this)">✕</button>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="fac-sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">
                                        <input type="text" class="form-control fac-sub-title" placeholder="Sub-item title" style="flex:1;">
                                        <input type="text" class="form-control fac-sub-desc" placeholder="Sub-item description" style="flex:2;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="facRemoveSubItem(this)">✕</button>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="facAddSubItem(this)" style="margin-top:8px;">+ Sub-item</button>
                            </div>
                            <div style="margin-top:10px;display:flex;gap:6px;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="facMoveListItem(this,-1)">▲</button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="facMoveListItem(this,1)">▼</button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="facRemoveListItem(this)">✕ Remove</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="facAddListItem()">+ Add Item</button>
        </div>

        <div style="margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;">
            <button type="submit" name="save_facility" class="btn btn-success">Save Facility</button>
            <?php if ($editFacility): ?>
            <a href="?facilities=<?= $deptId ?>&delete_facility=<?= $editFacility['id'] ?>" class="btn btn-danger" data-confirm="Delete this facility?" style="margin-left:8px;">Delete</a>
            <?php endif; ?>
        </div>
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

<script>
// === Facility Paragraph Management ===
function facAddParagraph(content) {
    content = content || '';
    var html = '<div class="fac-paragraph-block" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">' +
        '<textarea name="fac_para_content[]" class="form-control" rows="3" placeholder="Paragraph content..." style="flex:1;min-height:60px;">' + content.replace(/</g,'&lt;') + '</textarea>' +
        '<div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="facMovePara(this,-1)" title="Move up">▲</button>' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="facMovePara(this,1)" title="Move down">▼</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="facRemovePara(this)" title="Remove">✕</button>' +
        '</div></div>';
    document.getElementById('fac-paragraphs-container').insertAdjacentHTML('beforeend', html);
}
function facRemovePara(btn) { btn.closest('.fac-paragraph-block').remove(); }
function facMovePara(btn, dir) {
    var block = btn.closest('.fac-paragraph-block');
    var parent = block.parentNode;
    var sibling = dir === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (sibling && sibling.classList.contains('fac-paragraph-block')) {
        if (dir === -1) parent.insertBefore(block, sibling);
        else parent.insertBefore(sibling, block);
    }
}

// === Facility List Item Management ===
function facAddListItem(title, desc, children) {
    title = title || '';
    desc = desc || '';
    children = children || [];
    var subHtml = '';
    if (children.length === 0) {
        subHtml = '<div class="fac-sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">' +
            '<input type="text" class="form-control fac-sub-title" placeholder="Sub-item title" style="flex:1;">' +
            '<input type="text" class="form-control fac-sub-desc" placeholder="Sub-item description" style="flex:2;">' +
            '<button type="button" class="btn btn-sm btn-danger" onclick="facRemoveSubItem(this)">✕</button></div>';
    } else {
        children.forEach(function(child) {
            subHtml += '<div class="fac-sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">' +
                '<input type="text" class="form-control fac-sub-title" placeholder="Sub-item title" value="' + (child.title || '').replace(/"/g,'&quot;') + '" style="flex:1;">' +
                '<input type="text" class="form-control fac-sub-desc" placeholder="Sub-item description" value="' + (child.description || '').replace(/"/g,'&quot;') + '" style="flex:2;">' +
                '<button type="button" class="btn btn-sm btn-danger" onclick="facRemoveSubItem(this)">✕</button></div>';
        });
    }
    var html = '<div class="fac-list-item-block"><div style="border:1px solid #e2e8f0;padding:15px;border-radius:6px;margin-bottom:10px;background:#f8fafc;">' +
        '<div class="form-row"><div class="form-group" style="flex:1;"><label>Title</label><input type="text" class="form-control fac-item-title" value="' + title.replace(/"/g,'&quot;') + '"></div>' +
        '<div class="form-group" style="flex:1;"><label>Description</label><textarea class="form-control fac-item-desc" rows="2">' + desc.replace(/</g,'&lt;') + '</textarea></div></div>' +
        '<div class="fac-sub-items-container" style="margin-left:20px;margin-top:10px;padding:10px;background:#fff;border:1px dashed #e2e8f0;border-radius:4px;">' +
        '<label style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Sub-items</label>' + subHtml +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="facAddSubItem(this)" style="margin-top:8px;">+ Sub-item</button></div>' +
        '<div style="margin-top:10px;display:flex;gap:6px;">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="facMoveListItem(this,-1)">▲</button>' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="facMoveListItem(this,1)">▼</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="facRemoveListItem(this)">✕ Remove</button></div></div></div>';
    document.getElementById('fac-list-items-container').insertAdjacentHTML('beforeend', html);
}
function facAddSubItem(btn) {
    var container = btn.parentNode;
    var html = '<div class="fac-sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">' +
        '<input type="text" class="form-control fac-sub-title" placeholder="Sub-item title" style="flex:1;">' +
        '<input type="text" class="form-control fac-sub-desc" placeholder="Sub-item description" style="flex:2;">' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="facRemoveSubItem(this)">✕</button></div>';
    container.insertAdjacentHTML('beforeend', html);
}
function facRemoveSubItem(btn) { btn.closest('.fac-sub-item-block').remove(); }
function facRemoveListItem(btn) { btn.closest('.fac-list-item-block').remove(); }
function facMoveListItem(btn, dir) {
    var block = btn.closest('.fac-list-item-block');
    var parent = block.parentNode;
    var sibling = dir === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (sibling && sibling.classList.contains('fac-list-item-block')) {
        if (dir === -1) parent.insertBefore(block, sibling);
        else parent.insertBefore(sibling, block);
    }
}

// === Facility Form Serialization ===
document.getElementById('facility-form').addEventListener('submit', function() {
    // Serialize paragraphs
    var paragraphs = [];
    document.querySelectorAll('#fac-paragraphs-container .fac-paragraph-block').forEach(function(block) {
        var content = block.querySelector('textarea').value.trim();
        if (content) paragraphs.push({content: content});
    });
    document.getElementById('fac_paragraphs_data').value = JSON.stringify(paragraphs);

    // Serialize list items
    var items = [];
    document.querySelectorAll('#fac-list-items-container .fac-list-item-block').forEach(function(block) {
        var item = {
            title: block.querySelector('.fac-item-title').value,
            description: block.querySelector('.fac-item-desc').value,
            children: []
        };
        block.querySelectorAll('.fac-sub-item-block').forEach(function(sub) {
            var t = sub.querySelector('.fac-sub-title').value;
            var d = sub.querySelector('.fac-sub-desc').value;
            if (t) item.children.push({title: t, description: d});
        });
        items.push(item);
    });
    document.getElementById('fac_list_items_data').value = JSON.stringify(items);
});
</script>
<?php
// Units (Sections) page
elseif (isset($_GET['sections'])):
$deptId = (int)$_GET['sections'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !canUserAccessDepartment($_SESSION['user_id'], $deptId)) {
    $_SESSION['error'] = 'You do not have access to this department.';
    redirect('departments.php');
}
$dept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM departments WHERE id = $deptId"));
if (!$dept) { redirect('departments.php'); }

$editSection = null;
if (isset($_GET['edit_section'])) {
    $sid = (int)$_GET['edit_section'];
    $editSection = getDepartmentSection($sid);
}
$allSections = [];
$r = mysqli_query($conn, "SELECT * FROM department_sections WHERE department_id = $deptId ORDER BY sort_order ASC");
while ($row = mysqli_fetch_assoc($r)) { $allSections[] = $row; }

// Decode existing content for the edit form
$existingParagraphs = [['content' => '']];
$existingItems = [];
$existingGalleryImages = [];
$existingButtonType = 'text';
if ($editSection) {
    $contentData = json_decode($editSection['content'], true);
    if (is_array($contentData)) {
        if (isset($contentData['paragraphs'])) {
            $existingParagraphs = $contentData['paragraphs'];
            if (empty($existingParagraphs)) $existingParagraphs = [['content' => '']];
        } elseif (isset($contentData['items'])) {
            $existingItems = $contentData['items'];
        } elseif (isset($contentData['images'])) {
            $existingGalleryImages = $contentData['images'];
        }
    } elseif ($editSection['content'] && $editSection['section_type'] === 'text') {
        $existingParagraphs = [['content' => $editSection['content']]];
    }
}
?>
<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Content Units for: <?= sanitizeInput($dept['department_name']) ?></h5>
        <a href="?sections=<?= $deptId ?>&add_section=1" class="btn btn-sm btn-primary"><?= $editSection ? '← Back' : 'Add Unit' ?></a>
    </div>
    <?php if ($editSection || isset($_GET['add_section'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;" id="unit-form">
        <input type="hidden" name="section_id" value="<?= $editSection['id'] ?? 0 ?>">
        <input type="hidden" name="paragraphs_data" id="paragraphs_data">
        <input type="hidden" name="list_items_data" id="list_items_data">
        <input type="hidden" name="existing_gallery_data" id="existing_gallery_data">

        <div class="form-row">
            <div class="form-group" style="flex:2;">
                <label>Layout Type *</label>
                <select name="section_type" id="section-layout-select" class="form-control" required>
                    <option value="text" <?= ($editSection['section_type'] ?? 'text') == 'text' ? 'selected' : '' ?>>Text</option>
                    <option value="image_text" <?= ($editSection['section_type'] ?? '') == 'image_text' ? 'selected' : '' ?>>Image + Text</option>
                    <option value="text_image" <?= ($editSection['section_type'] ?? '') == 'text_image' ? 'selected' : '' ?>>Text + Image</option>
                    <option value="list" <?= ($editSection['section_type'] ?? '') == 'list' ? 'selected' : '' ?>>List</option>
                    <option value="gallery" <?= ($editSection['section_type'] ?? '') == 'gallery' ? 'selected' : '' ?>>Gallery</option>
                    <option value="cta" <?= ($editSection['section_type'] ?? '') == 'cta' ? 'selected' : '' ?>>Call to Action</option>
                    <option value="doctors" <?= ($editSection['section_type'] ?? '') == 'doctors' ? 'selected' : '' ?>>Doctors (Auto)</option>
                </select>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($editSection['sort_order'] ?? 0) ?>" min="0">
            </div>
        </div>

        <div class="form-group">
            <label>Section Key</label>
            <input type="text" name="section_key" class="form-control" value="<?= sanitizeInput($editSection['section_key'] ?? '') ?>" placeholder="Auto-generated from title if empty">
        </div>
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="section_title" id="section-title-input" class="form-control" value="<?= sanitizeInput($editSection['title'] ?? '') ?>" placeholder="e.g. Our Services" required>
        </div>
        <div class="form-group">
            <label>Subtitle</label>
            <div id="unit-subtitle-plain">
                <input type="text" name="section_subtitle" class="form-control" value="<?= sanitizeInput($editSection['subtitle'] ?? '') ?>" placeholder="Optional subtitle below the title">
            </div>
            <div id="unit-subtitle-editor" style="display:none;">
                <textarea id="list-subtitle-editor" class="form-control" style="min-height:150px;"><?= sanitizeInput($editSection['subtitle'] ?? '') ?></textarea>
                <small style="color:#94a3b8;">Use the toolbar to format the subtitle text.</small>
            </div>
        </div>

        <!-- Description Editor (Text layout only) -->
        <div id="unit-text-description-section" class="unit-layout-field" style="margin-top:15px;display:none;">
            <div class="form-group">
                <label style="font-weight:600;color:#475569;">Description *</label>
                <textarea name="section_description" id="unit-text-description" class="form-control" style="min-height:200px;"><?= sanitizeInput($existingParagraphs[0]['content'] ?? '') ?></textarea>
                <small style="color:#94a3b8;">Use the toolbar to format text, add lists, and insert links.</small>
            </div>
        </div>

        <!-- Paragraphs (image_text, text_image, cta) -->
        <div id="unit-paragraphs-section" class="unit-layout-field" style="margin-top:15px;">
            <label style="font-weight:600;color:#475569;">Content Paragraphs</label>
            <small style="color:#94a3b8;display:block;margin-bottom:10px;">Add multiple paragraphs of content. Each paragraph block is rendered separately.</small>
            <div id="paragraphs-container">
                <?php foreach ($existingParagraphs as $pIdx => $para): ?>
                <div class="paragraph-block" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">
                    <textarea name="para_content[]" class="form-control" rows="3" placeholder="Paragraph content..." style="flex:1;min-height:60px;"><?= sanitizeInput($para['content'] ?? '') ?></textarea>
                    <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="movePara(this,-1)" title="Move up">▲</button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="movePara(this,1)" title="Move down">▼</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removePara(this)" title="Remove">✕</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="addParagraph()">+ Add Paragraph</button>
        </div>

        <!-- Image (image_text, text_image) -->
        <div id="unit-image-section" class="unit-layout-field" style="margin-top:15px;">
            <div class="form-group">
                <label>Unit Image</label>
                <input type="file" name="section_image" class="form-control" accept="image/*">
                <?php if (!empty($editSection['image_path'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editSection['image_path']) ?>" style="max-height:100px;margin-top:8px;border-radius:6px;">
                <input type="hidden" name="existing_image" value="<?= sanitizeInput($editSection['image_path']) ?>">
                <?php endif; ?>
            </div>
        </div>

        <!-- Gallery Images (gallery) -->
        <div id="unit-gallery-section" class="unit-layout-field" style="margin-top:15px;">
            <label style="font-weight:600;color:#475569;">Gallery Images</label>
            <small style="color:#94a3b8;display:block;margin-bottom:10px;">Upload multiple images for the gallery grid.</small>
            <div id="gallery-images-container" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
                <?php foreach ($existingGalleryImages as $gImg): ?>
                <div class="gallery-img-item" style="position:relative;width:120px;height:90px;border-radius:6px;overflow:hidden;border:1px solid #e2e8f0;">
                    <img src="<?= SITE_URL . '/' . sanitizeInput($gImg) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeGalleryImage(this)" style="position:absolute;top:2px;right:2px;padding:1px 5px;font-size:11px;">✕</button>
                    <input type="hidden" class="gallery-img-path" value="<?= sanitizeInput($gImg) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <input type="file" name="gallery_images[]" id="gallery-images-input" class="form-control" accept="image/*" multiple>
        </div>

        <!-- Button (cta) -->
        <div id="unit-button-section" class="unit-layout-field" style="margin-top:15px;">
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>Button Text</label>
                    <input type="text" name="button_text" class="form-control" value="<?= sanitizeInput($editSection['button_text'] ?? '') ?>" placeholder="e.g. Learn More">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Button URL</label>
                    <input type="text" name="button_url" class="form-control" value="<?= sanitizeInput($editSection['button_url'] ?? '') ?>" placeholder="e.g. /contact.php or https://...">
                </div>
            </div>
        </div>

        <!-- List Items (list) -->
        <div id="unit-list-section" class="unit-layout-field" style="margin-top:15px;">
            <label style="font-weight:600;color:#475569;">List Items</label>
            <small style="color:#94a3b8;display:block;margin-bottom:10px;">Add items with optional nested sub-items.</small>
            <div id="list-items-container">
                <?php if (!empty($existingItems)): ?>
                    <?php foreach ($existingItems as $item): ?>
                    <div class="list-item-block">
                        <div style="border:1px solid #e2e8f0;padding:15px;border-radius:6px;margin-bottom:10px;background:#f8fafc;">
                            <div class="form-row">
                                <div class="form-group" style="flex:1;">
                                    <label>Title</label>
                                    <input type="text" class="form-control item-title" value="<?= sanitizeInput($item['title'] ?? '') ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Description</label>
                                    <textarea class="form-control item-desc" rows="2"><?= sanitizeInput($item['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="sub-items-container" style="margin-left:20px;margin-top:10px;padding:10px;background:#fff;border:1px dashed #e2e8f0;border-radius:4px;">
                                <label style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Sub-items</label>
                                <?php if (!empty($item['children'])): ?>
                                    <?php foreach ($item['children'] as $child): ?>
                                    <div class="sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">
                                        <input type="text" class="form-control sub-title" placeholder="Sub-item title" value="<?= sanitizeInput($child['title'] ?? '') ?>" style="flex:1;">
                                        <input type="text" class="form-control sub-desc" placeholder="Sub-item description" value="<?= sanitizeInput($child['description'] ?? '') ?>" style="flex:2;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeSubItem(this)">✕</button>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">
                                        <input type="text" class="form-control sub-title" placeholder="Sub-item title" style="flex:1;">
                                        <input type="text" class="form-control sub-desc" placeholder="Sub-item description" style="flex:2;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeSubItem(this)">✕</button>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addSubItem(this)" style="margin-top:8px;">+ Sub-item</button>
                            </div>
                            <div style="margin-top:10px;display:flex;gap:6px;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,-1)">▲</button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,1)">▼</button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeListItem(this)">✕ Remove</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="addListItem()">+ Add Item</button>
        </div>

        <div style="margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;">
            <button type="submit" name="save_section" class="btn btn-success">Save Unit</button>
            <?php if ($editSection): ?>
            <a href="?sections=<?= $deptId ?>&delete_section=<?= $editSection['id'] ?>" class="btn btn-danger" data-confirm="Delete this unit?" style="margin-left:8px;">Delete Unit</a>
            <?php endif; ?>
        </div>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Layout</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($allSections) > 0): ?>
                <?php foreach ($allSections as $sec): ?>
                <tr>
                    <td><?= (int)$sec['sort_order'] ?></td>
                    <td><strong><?= sanitizeInput($sec['title']) ?></strong><?php if ($sec['subtitle']): ?><br><small style="color:#94a3b8;"><?= sanitizeInput($sec['subtitle']) ?></small><?php endif; ?></td>
                    <td><span class="badge badge-info"><?= sanitizeInput($sec['section_type']) ?></span></td>
                    <td><span class="badge badge-<?= $sec['status'] == 'Published' ? 'success' : ($sec['status'] == 'Pending' ? 'warning' : 'secondary') ?>"><?= $sec['status'] ?></span></td>
                    <td>
                        <a href="?sections=<?= $deptId ?>&edit_section=<?= $sec['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="?sections=<?= $deptId ?>&delete_section=<?= $sec['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this unit?">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No units yet. Click "Add Unit" to create one.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <div style="padding:10px 20px;">
        <a href="departments.php" class="btn btn-sm btn-primary">← Back to Departments</a>
    </div>
</div>
<?php
// FAQ page
elseif (isset($_GET['faq'])):
$deptId = (int)$_GET['faq'];
if (!hasAnyRole(['Administrator', 'Content Approver']) && !canUserAccessDepartment($_SESSION['user_id'], $deptId)) {
    $_SESSION['error'] = 'You do not have access to this department.';
    redirect('departments.php');
}
$dept = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM departments WHERE id = $deptId"));
if (!$dept) { redirect('departments.php'); }

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
<?php
// Main departments page
else:
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
                <label>Banner Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($editDept['image'])): ?>
                <br><img src="<?= SITE_URL . '/' . sanitizeInput($editDept['image']) ?>" style="max-height:80px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Introduction Image</label>
                <input type="file" name="intro_image" class="form-control" accept="image/*">
                <?php
                $introImgs = [];
                if (!empty($editDept['description_images'])) {
                    $introImgs = json_decode($editDept['description_images'], true) ?: [];
                }
                if (!empty($introImgs[0])):
                ?>
                <div style="margin-top:8px;position:relative;display:inline-block;">
                    <img src="<?= SITE_URL . '/' . sanitizeInput($introImgs[0]) ?>" style="max-height:80px;border-radius:8px;border:1px solid #e2e8f0;">
                    <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:13px;cursor:pointer;color:#dc2626;">
                        <input type="checkbox" name="remove_intro_image" value="1"> Remove
                    </label>
                </div>
                <?php endif; ?>
                <small style="color:#94a3b8;display:block;margin-top:4px;">Shows side-by-side with department intro text.</small>
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

// === Unit CMS Dynamic Builder ===

function updateLayoutFields() {
    var type = document.getElementById('section-layout-select').value;
    document.querySelectorAll('.unit-layout-field').forEach(function(el) { el.style.display = 'none'; });
    if (type === 'text') {
        document.getElementById('unit-text-description-section').style.display = 'block';
    }
    // Toggle subtitle field
    if (type === 'list') {
        document.getElementById('unit-subtitle-plain').style.display = 'none';
        document.getElementById('unit-subtitle-editor').style.display = 'block';
    } else {
        document.getElementById('unit-subtitle-plain').style.display = 'block';
        document.getElementById('unit-subtitle-editor').style.display = 'none';
    }
    if (type === 'image_text' || type === 'text_image' || type === 'cta') {
        document.getElementById('unit-paragraphs-section').style.display = 'block';
    }
    if (type === 'image_text' || type === 'text_image') {
        document.getElementById('unit-image-section').style.display = 'block';
    }
    if (type === 'gallery') {
        document.getElementById('unit-gallery-section').style.display = 'block';
    }
    if (type === 'cta') {
        document.getElementById('unit-button-section').style.display = 'block';
    }
    if (type === 'list') {
        document.getElementById('unit-list-section').style.display = 'block';
    }
}

document.getElementById('section-layout-select').addEventListener('change', updateLayoutFields);
updateLayoutFields();

if (document.getElementById('unit-text-description')) {
    makeEditor('unit-text-description');
}
if (document.getElementById('list-subtitle-editor')) {
    makeEditor('list-subtitle-editor');
}

// Auto-generate section key from title
document.getElementById('section-title-input').addEventListener('input', function() {
    var keyInput = document.querySelector('input[name="section_key"]');
    if (!keyInput.dataset.manual) {
        keyInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    }
});
document.querySelector('input[name="section_key"]').addEventListener('input', function() {
    this.dataset.manual = '1';
});

// === Paragraph Management ===
function addParagraph(content) {
    content = content || '';
    var html = '<div class="paragraph-block" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;">' +
        '<textarea name="para_content[]" class="form-control" rows="3" placeholder="Paragraph content..." style="flex:1;min-height:60px;">' + content.replace(/</g,'&lt;') + '</textarea>' +
        '<div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="movePara(this,-1)" title="Move up">▲</button>' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="movePara(this,1)" title="Move down">▼</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="removePara(this)" title="Remove">✕</button>' +
        '</div></div>';
    document.getElementById('paragraphs-container').insertAdjacentHTML('beforeend', html);
}
function removePara(btn) { btn.closest('.paragraph-block').remove(); }
function movePara(btn, dir) {
    var block = btn.closest('.paragraph-block');
    var parent = block.parentNode;
    var sibling = dir === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (sibling && sibling.classList.contains('paragraph-block')) {
        if (dir === -1) parent.insertBefore(block, sibling);
        else parent.insertBefore(sibling, block);
    }
}

// === List Item Management ===
function addListItem(title, desc, children) {
    title = title || '';
    desc = desc || '';
    children = children || [];
    var subHtml = '';
    if (children.length === 0) {
        subHtml = '<div class="sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">' +
            '<input type="text" class="form-control sub-title" placeholder="Sub-item title" style="flex:1;">' +
            '<input type="text" class="form-control sub-desc" placeholder="Sub-item description" style="flex:2;">' +
            '<button type="button" class="btn btn-sm btn-danger" onclick="removeSubItem(this)">✕</button></div>';
    } else {
        children.forEach(function(child) {
            subHtml += '<div class="sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">' +
                '<input type="text" class="form-control sub-title" placeholder="Sub-item title" value="' + (child.title || '').replace(/"/g,'&quot;') + '" style="flex:1;">' +
                '<input type="text" class="form-control sub-desc" placeholder="Sub-item description" value="' + (child.description || '').replace(/"/g,'&quot;') + '" style="flex:2;">' +
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeSubItem(this)">✕</button></div>';
        });
    }
    var html = '<div class="list-item-block"><div style="border:1px solid #e2e8f0;padding:15px;border-radius:6px;margin-bottom:10px;background:#f8fafc;">' +
        '<div class="form-row"><div class="form-group" style="flex:1;"><label>Title</label><input type="text" class="form-control item-title" value="' + title.replace(/"/g,'&quot;') + '"></div>' +
        '<div class="form-group" style="flex:1;"><label>Description</label><textarea class="form-control item-desc" rows="2">' + desc.replace(/</g,'&lt;') + '</textarea></div></div>' +
        '<div class="sub-items-container" style="margin-left:20px;margin-top:10px;padding:10px;background:#fff;border:1px dashed #e2e8f0;border-radius:4px;">' +
        '<label style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Sub-items</label>' + subHtml +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="addSubItem(this)" style="margin-top:8px;">+ Sub-item</button></div>' +
        '<div style="margin-top:10px;display:flex;gap:6px;">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,-1)">▲</button>' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,1)">▼</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="removeListItem(this)">✕ Remove</button></div></div></div>';
    document.getElementById('list-items-container').insertAdjacentHTML('beforeend', html);
}
function addSubItem(btn) {
    var container = btn.parentNode;
    var html = '<div class="sub-item-block" style="display:flex;gap:8px;align-items:flex-start;margin-top:8px;">' +
        '<input type="text" class="form-control sub-title" placeholder="Sub-item title" style="flex:1;">' +
        '<input type="text" class="form-control sub-desc" placeholder="Sub-item description" style="flex:2;">' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="removeSubItem(this)">✕</button></div>';
    container.insertAdjacentHTML('beforeend', html);
}
function removeSubItem(btn) { btn.closest('.sub-item-block').remove(); }
function removeListItem(btn) { btn.closest('.list-item-block').remove(); }
function moveListItem(btn, dir) {
    var block = btn.closest('.list-item-block');
    var parent = block.parentNode;
    var sibling = dir === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (sibling && sibling.classList.contains('list-item-block')) {
        if (dir === -1) parent.insertBefore(block, sibling);
        else parent.insertBefore(sibling, block);
    }
}

// === Gallery Image Management ===
function removeGalleryImage(btn) { btn.closest('.gallery-img-item').remove(); }

// === Form Serialization ===
document.getElementById('unit-form').addEventListener('submit', function() {
    syncAllEditors();

    var type = document.getElementById('section-layout-select').value;

    // Serialize paragraphs
    if (type === 'text') {
        var editorEl = document.querySelector('#unit-text-description-section .editor-content');
        var descContent = editorEl ? editorEl.innerHTML.trim() : document.getElementById('unit-text-description').value.trim();
        var paragraphs = descContent ? [{content: descContent}] : [];
        document.getElementById('paragraphs_data').value = JSON.stringify(paragraphs);
    }
    if (type === 'image_text' || type === 'text_image' || type === 'cta') {
        var paragraphs = [];
        document.querySelectorAll('#paragraphs-container .paragraph-block').forEach(function(block) {
            var content = block.querySelector('textarea').value.trim();
            if (content) paragraphs.push({content: content});
        });
        document.getElementById('paragraphs_data').value = JSON.stringify(paragraphs);
    }

    // Serialize list items
    if (type === 'list') {
        var items = [];
        document.querySelectorAll('#list-items-container .list-item-block').forEach(function(block) {
            var item = {
                title: block.querySelector('.item-title').value,
                description: block.querySelector('.item-desc').value,
                children: []
            };
            block.querySelectorAll('.sub-item-block').forEach(function(sub) {
                var t = sub.querySelector('.sub-title').value;
                var d = sub.querySelector('.sub-desc').value;
                if (t) item.children.push({title: t, description: d});
            });
            items.push(item);
        });
        document.getElementById('list_items_data').value = JSON.stringify(items);

        // Serialize subtitle from editor
        var subEditor = document.querySelector('#unit-subtitle-editor .editor-content');
        if (subEditor) {
            document.querySelector('#unit-subtitle-plain input').value = subEditor.innerHTML;
        }
    }

    // Serialize existing gallery images
    if (type === 'gallery') {
        var galleryImages = [];
        document.querySelectorAll('#gallery-images-container .gallery-img-path').forEach(function(input) {
            galleryImages.push(input.value);
        });
        document.getElementById('existing_gallery_data').value = JSON.stringify(galleryImages);
    }
});
</script>
<?php require_once 'footer.php'; ?>
