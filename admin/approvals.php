<?php
$pageTitle = 'Content Approvals';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Approver'])) { redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = (int)$_POST['request_id'];
    $action = sanitize($_POST['action']);
    $comments = sanitize($_POST['comments'] ?? '');

    if (in_array($action, ['Approved', 'Rejected'])) {
        $stmt = mysqli_prepare($conn, "UPDATE approval_requests SET status=?, approved_by=?, comments=?, approval_date=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sisi', $action, $_SESSION['user_id'], $comments, $requestId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Update the related entity status
        $req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM approval_requests WHERE id = $requestId"));
        if ($req && $action === 'Approved') {
            $table = '';
            $statusField = 'status';
            switch ($req['entity_type']) {
                case 'website_content': $table = 'website_contents'; break;
                case 'department': $table = 'departments'; break;
                case 'department_section': $table = 'department_sections'; break;
                case 'doctor': $table = 'doctors'; break;
                case 'gallery': $table = 'gallery'; break;
                case 'career': $table = 'careers'; break;
                case 'branch': $table = 'branches'; break;
            }
            if ($table) {
                $approvedById = $_SESSION['user_id'];
                if (in_array($table, ['website_contents', 'department_sections'])) {
                    // Check if this is a delete request
                    if ($table === 'website_contents' && strpos($req['comments'] ?? '', '__DELETE__') === 0) {
                        mysqli_query($conn, "DELETE FROM website_contents WHERE id={$req['entity_id']}");
                    } else {
                        mysqli_query($conn, "UPDATE $table SET status='Published', approved_by=$approvedById WHERE id={$req['entity_id']}");
                    }
                } elseif (in_array($req['entity_type'], ['department', 'doctor', 'gallery', 'career', 'branch'])) {
                    mysqli_query($conn, "UPDATE $table SET approved_by=$approvedById WHERE id={$req['entity_id']}");
                }
            }
        }
        // Handle rejection of delete requests - restore original status
        if ($req && $action === 'Rejected' && $req['entity_type'] === 'website_content') {
            $originalComments = $req['comments'] ?? '';
            if (strpos($originalComments, '__DELETE__') === 0) {
                $parts = explode('|', $originalComments);
                $prevStatus = $parts[1] ?? 'Draft';
                mysqli_query($conn, "UPDATE website_contents SET status='$prevStatus' WHERE id={$req['entity_id']}");
            }
        }
        $_SESSION['success'] = "Request $action successfully.";
    }
    redirect('approvals.php');
}

// Get pending approvals
$pendingResult = mysqli_query($conn, "SELECT ar.*, u.name as requester FROM approval_requests ar LEFT JOIN users u ON ar.requested_by = u.id WHERE ar.status = 'Pending' ORDER BY ar.request_date DESC");
// Get history
$historyResult = mysqli_query($conn, "SELECT ar.*, u1.name as requester, u2.name as approver FROM approval_requests ar LEFT JOIN users u1 ON ar.requested_by = u1.id LEFT JOIN users u2 ON ar.approved_by = u2.id WHERE ar.status != 'Pending' ORDER BY ar.approval_date DESC LIMIT 20");
?>
<div class="table-container">
    <div class="header">
        <h5>Pending Approval Requests</h5>
    </div>
    <table>
        <thead>
            <tr>
                <th>Entity Type</th>
                <th>Entity ID</th>
                <th>Requested By</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($pendingResult) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($pendingResult)): ?>
                <tr>
                    <td><span class="badge badge-info"><?= str_replace('_', ' ', ucwords(sanitizeInput($row['entity_type']))) ?></span></td>
                    <td>#<?= $row['entity_id'] ?></td>
                    <td><?= sanitizeInput($row['requester'] ?? 'N/A') ?></td>
                    <td><?= date('d M Y h:i A', strtotime($row['request_date'])) ?></td>
                    <td>
                        <button onclick="document.getElementById('review-<?= $row['id'] ?>').style.display='block'" class="btn btn-sm btn-primary">Review</button>
                    </td>
                </tr>
                <tr id="review-<?= $row['id'] ?>" style="display:none;">
                    <td colspan="5">
                        <div style="padding:15px;background:#f8fafc;border-radius:5px;">
                            <strong>Content Preview:</strong>
                            <?php
                            $entityData = null;
                            if ($row['entity_type'] == 'website_content') {
                                $res = mysqli_query($conn, "SELECT * FROM website_contents WHERE id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            } elseif ($row['entity_type'] == 'department') {
                                $res = mysqli_query($conn, "SELECT * FROM departments WHERE id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            } elseif ($row['entity_type'] == 'doctor') {
                                $res = mysqli_query($conn, "SELECT * FROM doctors WHERE id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            } elseif ($row['entity_type'] == 'career') {
                                $res = mysqli_query($conn, "SELECT * FROM careers WHERE id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            } elseif ($row['entity_type'] == 'branch') {
                                $res = mysqli_query($conn, "SELECT * FROM branches WHERE id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            } elseif ($row['entity_type'] == 'department_section') {
                                $res = mysqli_query($conn, "SELECT ds.*, d.department_name FROM department_sections ds JOIN departments d ON ds.department_id = d.id WHERE ds.id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            } elseif ($row['entity_type'] == 'gallery') {
                                $res = mysqli_query($conn, "SELECT * FROM gallery WHERE id = {$row['entity_id']}");
                                $entityData = mysqli_fetch_assoc($res);
                            }
                            if ($entityData): ?>
                            <pre style="background:#fff;padding:10px;border:1px solid #e2e8f0;border-radius:5px;margin:10px 0;max-height:200px;overflow:auto;font-size:13px;"><?php
                                foreach ($entityData as $key => $val) {
                                    if ($key != 'password' && $key != 'created_at' && $key != 'updated_at' && !str_contains($key, 'approved_by')) {
                                        echo sanitizeInput("$key: ") . (strlen($val) > 200 ? sanitizeInput(substr($val, 0, 200)) . '...' : sanitizeInput($val)) . "\n";
                                    }
                                }
                            ?></pre>
                            <?php endif; ?>
                            <form method="POST" action="" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                <input type="text" name="comments" placeholder="Comments (optional)" class="form-control" style="flex:1;min-width:200px;">
                                <button type="submit" name="action" value="Approved" class="btn btn-sm btn-success">Approve</button>
                                <button type="submit" name="action" value="Rejected" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No pending approval requests.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Approval History</h5>
    </div>
    <table>
        <thead>
            <tr>
                <th>Entity</th>
                <th>Requester</th>
                <th>Approver</th>
                <th>Status</th>
                <th>Comments</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($historyResult) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($historyResult)): ?>
                <tr>
                    <td><span class="badge badge-info"><?= str_replace('_', ' ', ucwords(sanitizeInput($row['entity_type']))) ?> #<?= $row['entity_id'] ?></span></td>
                    <td><?= sanitizeInput($row['requester'] ?? 'N/A') ?></td>
                    <td><?= sanitizeInput($row['approver'] ?? 'N/A') ?></td>
                    <td><span class="badge badge-<?= $row['status'] == 'Approved' ? 'success' : 'danger' ?>"><?= $row['status'] ?></span></td>
                    <td><?= sanitizeInput($row['comments'] ?? '-') ?></td>
                    <td><?= date('d M Y', strtotime($row['approval_date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No history available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>
