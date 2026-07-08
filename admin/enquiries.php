<?php
$pageTitle = 'Manage Enquiries';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && $_GET['action'] === 'resolve' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE contact_enquiries SET status='Resolved' WHERE id=$id");
    $_SESSION['success'] = 'Enquiry marked as resolved.';
    redirect('enquiries.php');
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM contact_enquiries WHERE id=$id");
    $_SESSION['success'] = 'Enquiry deleted.';
    redirect('enquiries.php');
}

$result = mysqli_query($conn, "SELECT * FROM contact_enquiries ORDER BY created_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5>Contact Enquiries</h5>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= sanitizeInput($row['name']) ?></strong><br><small><?= sanitizeInput($row['email'] ?? '') ?><?= $row['phone'] ? ' | '.sanitizeInput($row['phone']) : '' ?></small></td>
                <td><span class="badge badge-info"><?= sanitizeInput($row['enquiry_type']) ?></span></td>
                <td><?= sanitizeInput($row['subject']) ?></td>
                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Pending' ? 'warning' : 'success' ?>"><?= $row['status'] ?></span></td>
                <td>
                    <button onclick="alert('<?= sanitizeInput(addslashes($row['message'])) ?>')" class="btn btn-sm btn-info">View</button>
                    <?php if ($row['status'] == 'Pending'): ?>
                    <a href="?action=resolve&id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Resolve</a>
                    <?php endif; ?>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete?">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>
