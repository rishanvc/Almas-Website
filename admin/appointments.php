<?php
$pageTitle = 'Manage Appointments';
require_once 'header.php';
if (!hasAnyRole(['Administrator', 'Content Creator', 'Content Approver'])) { redirect('index.php'); }

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['action']);
    if (in_array($status, ['Confirmed', 'Cancelled', 'Completed'])) {
        $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['success'] = "Appointment status updated to $status.";
    }
    redirect('appointments.php');
}

$result = mysqli_query($conn, "SELECT a.*, d.department_name, doc.name as doctor_name FROM appointments a LEFT JOIN departments d ON a.department_id = d.id LEFT JOIN doctors doc ON a.doctor_id = doc.id ORDER BY a.created_at DESC");
?>
<div class="table-container">
    <div class="header">
        <h5>All Appointments</h5>
    </div>
    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= sanitizeInput($row['patient_name']) ?></strong><br><small><?= sanitizeInput($row['email'] ?? '') ?></small></td>
                <td><?= sanitizeInput($row['phone']) ?></td>
                <td><?= sanitizeInput($row['department_name'] ?? '-') ?></td>
                <td><?= sanitizeInput($row['doctor_name'] ?? '-') ?></td>
                <td><?= date('d M Y', strtotime($row['appointment_date'])) ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Pending' ? 'warning' : ($row['status'] == 'Confirmed' ? 'success' : ($row['status'] == 'Cancelled' ? 'danger' : 'info')) ?>"><?= $row['status'] ?></span></td>
                <td>
                    <?php if ($row['message']): ?><span title="<?= sanitizeInput($row['message']) ?>" style="cursor:help;">📝</span><?php endif; ?>
                    <?php if ($row['status'] == 'Pending'): ?>
                    <a href="?action=Confirmed&id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Confirm</a>
                    <a href="?action=Cancelled&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Cancel</a>
                    <?php elseif ($row['status'] == 'Confirmed'): ?>
                    <a href="?action=Completed&id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Complete</a>
                    <a href="?action=Cancelled&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Cancel</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>
