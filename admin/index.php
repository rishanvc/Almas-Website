<?php
$pageTitle = 'Dashboard';
require_once 'header.php';

// Stats
$userCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$deptCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM departments"))['c'];
$docCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM doctors"))['c'];
$apptCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM appointments"))['c'];
$pendingApprovals = getPendingApprovalCount();
$pendingAppts = getPendingAppointmentCount();
$pendingEnquiries = getPendingEnquiryCount();
$careerCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM careers WHERE status='Open'"))['c'];
?>

<div class="cards">
    <div class="card-dash blue">
        <h4>Total Users</h4>
        <div class="number"><?= $userCount ?></div>
    </div>
    <div class="card-dash green">
        <h4>Departments</h4>
        <div class="number"><?= $deptCount ?></div>
    </div>
    <div class="card-dash blue">
        <h4>Doctors</h4>
        <div class="number"><?= $docCount ?></div>
    </div>
    <div class="card-dash yellow">
        <h4>Total Appointments</h4>
        <div class="number"><?= $apptCount ?></div>
    </div>
    <div class="card-dash red">
        <h4>Pending Approvals</h4>
        <div class="number"><?= $pendingApprovals ?></div>
    </div>
    <div class="card-dash yellow">
        <h4>Pending Appointments</h4>
        <div class="number"><?= $pendingAppts ?></div>
    </div>
    <div class="card-dash red">
        <h4>Pending Enquiries</h4>
        <div class="number"><?= $pendingEnquiries ?></div>
    </div>
    <div class="card-dash green">
        <h4>Open Positions</h4>
        <div class="number"><?= $careerCount ?></div>
    </div>
</div>

<div class="table-container">
    <div class="header">
        <h5>Recent Appointments</h5>
        <a href="<?= SITE_URL ?>/admin/appointments.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Phone</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = mysqli_query($conn, "SELECT a.*, d.department_name FROM appointments a LEFT JOIN departments d ON a.department_id = d.id ORDER BY a.created_at DESC LIMIT 5");
            while ($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?= sanitizeInput($row['patient_name']) ?></td>
                <td><?= sanitizeInput($row['phone']) ?></td>
                <td><?= date('d M Y', strtotime($row['appointment_date'])) ?></td>
                <td><span class="badge badge-<?= $row['status'] == 'Pending' ? 'warning' : ($row['status'] == 'Confirmed' ? 'success' : ($row['status'] == 'Cancelled' ? 'danger' : 'info')) ?>"><?= $row['status'] ?></span></td>
                <td><a href="<?= SITE_URL ?>/admin/appointments.php" class="btn btn-sm btn-primary">Manage</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="table-container" style="margin-top:20px;">
    <div class="header">
        <h5>Pending Approvals</h5>
        <a href="<?= SITE_URL ?>/admin/approvals.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Entity Type</th>
                <th>Requested By</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = mysqli_query($conn, "SELECT ar.*, u.name as requester FROM approval_requests ar LEFT JOIN users u ON ar.requested_by = u.id WHERE ar.status = 'Pending' ORDER BY ar.request_date DESC LIMIT 5");
            while ($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?= str_replace('_', ' ', ucwords(sanitizeInput($row['entity_type']))) ?></td>
                <td><?= sanitizeInput($row['requester'] ?? 'N/A') ?></td>
                <td><?= date('d M Y', strtotime($row['request_date'])) ?></td>
                <td><span class="badge badge-warning">Pending</span></td>
                <td><a href="<?= SITE_URL ?>/admin/approvals.php" class="btn btn-sm btn-primary">Review</a></td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($result) == 0): ?>
            <tr><td colspan="5" style="text-align:center;">No pending approvals</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>
