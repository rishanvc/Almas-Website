<?php
$pageTitle = 'Manage Home Care';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

// Delete handler
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM home_care WHERE id = $id");
    $_SESSION['success'] = 'Home Care item deleted.';
    redirect('homecare.php');
}

// Save handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $heading = sanitize($_POST['heading'] ?? '');
    $description = $_POST['description'] ?? '';
    $additionalText = $_POST['additional_text'] ?? '';
    $status = sanitize($_POST['status']);
    $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], UPLOAD_PATH . '/homecare');
        if ($upload['success']) $image = $upload['path'];
    }

    // Handle list items
    $listItems = json_decode($_POST['list_items_data'] ?? '[]', true) ?: [];
    $listItemsJson = !empty($listItems) ? json_encode($listItems, JSON_UNESCAPED_UNICODE) : null;

    $imgPath = $image !== '' ? $image : null;
    $userId = (int)$_SESSION['user_id'];

    if ($itemId) {
        if ($image !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE home_care SET heading=?, description=?, list_items=?, additional_text=?, status=?, image=?, created_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssssii', $heading, $description, $listItemsJson, $additionalText, $status, $imgPath, $userId, $itemId);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE home_care SET heading=?, description=?, list_items=?, additional_text=?, status=?, created_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssii', $heading, $description, $listItemsJson, $additionalText, $status, $userId, $itemId);
        }
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO home_care (heading, description, image, list_items, additional_text, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $heading, $description, $imgPath, $listItemsJson, $additionalText, $status, $userId);
    }
    mysqli_stmt_execute($stmt);
    $newId = $itemId ?: mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!hasAnyRole(['Administrator', 'Content Approver'])) {
        createApprovalRequest('home_care', $newId, $_SESSION['user_id']);
        $_SESSION['success'] = 'Home Care item saved and submitted for approval.';
    } else {
        $_SESSION['success'] = 'Home Care item saved successfully.';
    }
    redirect('homecare.php');
}

// Edit mode
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editItem = getHomeCareById($editId);
}
$allItems = getActiveHomeCare();

// Also fetch inactive for admin
$allItemsResult = mysqli_query($conn, "SELECT * FROM home_care ORDER BY created_at DESC");
$allItems = [];
while ($row = mysqli_fetch_assoc($allItemsResult)) {
    $allItems[] = $row;
}

// Decode existing list items for edit
$existingListItems = [];
if ($editItem && !empty($editItem['list_items'])) {
    $existingListItems = json_decode($editItem['list_items'], true) ?: [];
}
?>

<div class="table-container">
    <div class="header">
        <h5><?= $editItem ? 'Edit Home Care Item' : 'Home Care Items' ?></h5>
        <?php if (!$editItem): ?>
        <a href="?add=1" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Add Home Care</a>
        <?php endif; ?>
    </div>

    <?php if ($editItem || isset($_GET['add'])): ?>
    <form method="POST" action="" enctype="multipart/form-data" style="padding:20px;" id="homecare-form">
        <input type="hidden" name="item_id" value="<?= $editItem['id'] ?? 0 ?>">
        <input type="hidden" name="list_items_data" id="list_items_data">

        <div class="form-group">
            <label>Heading</label>
            <input type="text" name="heading" class="form-control" value="<?= sanitizeInput($editItem['heading'] ?? '') ?>" placeholder="Optional heading">
        </div>

        <div class="form-group">
            <label>Description (Text Editor)</label>
            <textarea name="description" id="description-editor" class="form-control" style="min-height:200px;"><?= sanitizeInput($editItem['description'] ?? '') ?></textarea>
            <small style="color:#94a3b8;">Use the toolbar to format text, add lists, and insert links.</small>
        </div>

        <div class="form-group">
            <label>Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if (!empty($editItem['image'])): ?>
            <br><img src="<?= SITE_URL . '/' . sanitizeInput($editItem['image']) ?>" style="max-height:100px;margin-top:8px;border-radius:6px;">
            <?php endif; ?>
        </div>

        <!-- List Items -->
        <div style="margin-top:20px;">
            <label style="font-weight:600;color:#475569;">List Items</label>
            <small style="color:#94a3b8;display:block;margin-bottom:10px;">Add items to display as a list.</small>
            <div id="list-items-container">
                <?php if (!empty($existingListItems)): ?>
                    <?php foreach ($existingListItems as $item): ?>
                    <div class="list-item-block" style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
                        <input type="text" class="form-control list-item-input" value="<?= sanitizeInput($item) ?>" placeholder="List item text" style="flex:1;">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,-1)" title="Move up">▲</button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,1)" title="Move down">▼</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeListItem(this)">✕</button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="addListItem()">+ Add List Item</button>
        </div>

        <!-- Additional Text Editor -->
        <div style="margin-top:20px;">
            <div class="form-group">
                <label>Additional Text (Text Editor)</label>
                <textarea name="additional_text" id="additional-text-editor" class="form-control" style="min-height:150px;"><?= sanitizeInput($editItem['additional_text'] ?? '') ?></textarea>
                <small style="color:#94a3b8;">Optional additional content section.</small>
            </div>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="Active" <?= ($editItem['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= ($editItem['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div style="margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;">
            <button type="submit" name="save" class="btn btn-success">Save Home Care</button>
            <?php if ($editItem): ?>
            <a href="?action=delete&id=<?= $editItem['id'] ?>" class="btn btn-danger" data-confirm="Delete this item?" style="margin-left:8px;">Delete</a>
            <?php endif; ?>
            <a href="homecare.php" class="btn btn-secondary" style="margin-left:8px;">Cancel</a>
        </div>
    </form>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Heading</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($allItems) > 0): ?>
                <?php foreach ($allItems as $item): ?>
                <tr>
                    <td><strong><?= sanitizeInput($item['heading'] ?: 'Untitled') ?></strong></td>
                    <td><span class="badge badge-<?= $item['status'] == 'Active' ? 'success' : 'secondary' ?>"><?= $item['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($item['created_at'])) ?></td>
                    <td>
                        <a href="?edit=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="?action=delete&id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete?">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No home care items yet. Click "Add Home Care" to create one.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
// Initialize text editors
document.addEventListener('DOMContentLoaded', function() {
    if (typeof makeEditor === 'function') {
        makeEditor('description-editor');
        makeEditor('additional-text-editor');
    }
});

// List item management
function addListItem(value) {
    value = value || '';
    var html = '<div class="list-item-block" style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">' +
        '<input type="text" class="form-control list-item-input" value="' + value.replace(/"/g,'&quot;') + '" placeholder="List item text" style="flex:1;">' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,-1)" title="Move up">▲</button>' +
        '<button type="button" class="btn btn-sm btn-secondary" onclick="moveListItem(this,1)" title="Move down">▼</button>' +
        '<button type="button" class="btn btn-sm btn-danger" onclick="removeListItem(this)">✕</button></div>';
    document.getElementById('list-items-container').insertAdjacentHTML('beforeend', html);
}

function removeListItem(btn) {
    btn.closest('.list-item-block').remove();
}

function moveListItem(btn, dir) {
    var block = btn.closest('.list-item-block');
    var parent = block.parentNode;
    var sibling = dir === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (sibling && sibling.classList.contains('list-item-block')) {
        if (dir === -1) parent.insertBefore(block, sibling);
        else parent.insertBefore(sibling, block);
    }
}

// Serialize list items on submit
document.getElementById('homecare-form').addEventListener('submit', function() {
    var items = [];
    document.querySelectorAll('#list-items-container .list-item-input').forEach(function(input) {
        var val = input.value.trim();
        if (val) items.push(val);
    });
    document.getElementById('list_items_data').value = JSON.stringify(items);
});
</script>

<?php require_once 'footer.php'; ?>