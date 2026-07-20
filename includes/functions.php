<?php
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function escape($data) {
    global $conn;
    return mysqli_real_escape_string($conn, $data);
}

function getSetting($key = null) {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM website_settings WHERE id = 1 LIMIT 1");
    return mysqli_fetch_assoc($result);
}

function getPublishedContent($pageName) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM website_contents WHERE page_name = ? AND status = 'Published' ORDER BY updated_at DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $pageName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data;
}

function getActiveDepartments() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM departments WHERE status = 'Active' ORDER BY department_name");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getActiveDoctors($departmentId = null) {
    global $conn;
    if ($departmentId) {
        $stmt = mysqli_prepare($conn, "SELECT d.*, dep.department_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.status = 'Active' AND d.department_id = ? ORDER BY d.name");
        mysqli_stmt_bind_param($stmt, 'i', $departmentId);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT d.*, dep.department_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.status = 'Active' ORDER BY d.name");
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $data;
}

function getDepartmentFacilities($departmentId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM department_facilities WHERE department_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $departmentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $data;
}

function getPublishedBlogs() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM blogs WHERE status = 'Active' ORDER BY COALESCE(posted_date, created_at) DESC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getActiveCareers() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM careers WHERE status = 'Open' ORDER BY created_at DESC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getActiveBranches() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM branches WHERE status = 'Active' ORDER BY branch_name");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getActivePackages() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM health_packages WHERE status = 'Active' ORDER BY package_name");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $time);
}

function uploadFile($file, $targetDir, $allowedTypes = ['jpg','jpeg','png','gif','webp','pdf','doc','docx']) {
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    $targetPath = $targetDir . '/' . $fileName;
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) return ['success' => false, 'error' => 'Invalid file type'];
    if ($file['size'] > 5242880) return ['success' => false, 'error' => 'File too large (max 5MB)'];
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'uploads/' . basename($targetDir) . '/' . $fileName];
    }
    return ['success' => false, 'error' => 'Upload failed'];
}

function createApprovalRequest($entityType, $entityId, $requestedBy) {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO approval_requests (entity_type, entity_id, requested_by, status) VALUES (?, ?, ?, 'Pending')");
    mysqli_stmt_bind_param($stmt, 'sii', $entityType, $entityId, $requestedBy);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

function getPendingApprovalCount() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM approval_requests WHERE status = 'Pending'");
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

function getPendingAppointmentCount() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status = 'Pending'");
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

function getPendingEnquiryCount() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM contact_enquiries WHERE status = 'Pending'");
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

function getDepartmentSections($departmentId, $status = 'Published') {
    global $conn;
    if ($status === 'all') {
        $stmt = mysqli_prepare($conn, "SELECT * FROM department_sections WHERE department_id = ? ORDER BY sort_order ASC");
        mysqli_stmt_bind_param($stmt, 'i', $departmentId);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM department_sections WHERE department_id = ? AND status = ? ORDER BY sort_order ASC");
        mysqli_stmt_bind_param($stmt, 'is', $departmentId, $status);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $data;
}

function getDepartmentSection($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM department_sections WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data;
}

function getUserAssignedDepartments($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT department_id FROM user_departments WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = $row['department_id'];
    }
    mysqli_stmt_close($stmt);
    return $ids;
}

function getUserAssignAllStatus($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT assign_all_departments FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['assign_all_departments'] : 0;
}

function getAccessibleDepartments($userId) {
    if (getUserAssignAllStatus($userId)) {
        return getActiveDepartments();
    }
    $assignedIds = getUserAssignedDepartments($userId);
    if (empty($assignedIds)) return [];
    global $conn;
    $ids = implode(',', array_map('intval', $assignedIds));
    $result = mysqli_query($conn, "SELECT * FROM departments WHERE id IN ($ids) AND status = 'Active' ORDER BY department_name");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getDepartmentFAQs($departmentId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM department_faqs WHERE department_id = ? ORDER BY sort_order ASC");
    mysqli_stmt_bind_param($stmt, 'i', $departmentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $data;
}

function getDepartmentFAQ($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM department_faqs WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data;
}

function canUserAccessDepartment($userId, $deptId) {
    if (getUserAssignAllStatus($userId)) return true;
    $assignedIds = getUserAssignedDepartments($userId);
    return in_array((int)$deptId, $assignedIds);
}

function nl2p($text) {
    $trimmed = trim($text);
    if ($trimmed === '') return '';
    // If content already contains block-level HTML, output as-is (it's already formatted)
    if (preg_match('/<(p|div|h[1-6]|ul|ol|li|table|section|figure|blockquote|pre)\b/i', $trimmed)) {
        return $trimmed;
    }
    $paragraphs = preg_split('/\n\s*\n/', $trimmed);
    $output = '';
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p !== '') {
            $output .= '<p>' . nl2br($p) . '</p>';
        }
    }
    return $output;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function displayMessage() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . sanitizeInput($_SESSION['success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . sanitizeInput($_SESSION['error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error']);
    }
}
