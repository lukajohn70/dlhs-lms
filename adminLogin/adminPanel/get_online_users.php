<?php
session_start();
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/online_tracking_schema.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    die('Unauthorized');
}

dlhsEnsureOnlineTrackingTables($connection);

$userType = $_GET['type'] ?? 'student';

// Get online users
if ($userType == 'all') {
    // Get all users
    $query = "
        SELECT 
            userId,
            userType,
            fullName,
            currentPage,
            ipAddress,
            lastActivity
        FROM online_users
        WHERE isActive = 1
        ORDER BY lastActivity DESC
    ";
    $stmt = $connection->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Get users by specific type
    $query = "
        SELECT 
            userId,
            userType,
            fullName,
            currentPage,
            ipAddress,
            lastActivity
        FROM online_users
        WHERE userType = ? AND isActive = 1
        ORDER BY lastActivity DESC
    ";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $userType);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows == 0) {
    echo '<div class="no-users">
            <i class="fa fa-user-times" style="font-size: 48px; color: #cbd5e0; margin-bottom: 10px;"></i>
            <p>No ' . htmlspecialchars($userType) . 's are currently online</p>
          </div>';
    exit;
}

while ($row = $result->fetch_assoc()) {
    $initials = getInitials($row['fullName']);
    $timeAgo = getTimeAgo($row['lastActivity']);
    $pageName = str_replace('.php', '', $row['currentPage']);
    $pageName = ucwords(str_replace('_', ' ', $pageName));
    
    // Add user type badge if showing all users
    $typeBadge = '';
    if ($userType == 'all') {
        $badgeClass = 'badge-' . $row['userType'];
        $typeBadge = '<span class="badge ' . $badgeClass . '">' . ucfirst($row['userType']) . '</span> ';
    }
    
    echo '<div class="user-row">
            <div class="user-info">
                <div class="user-avatar">' . htmlspecialchars($initials) . '</div>
                <div class="user-details">
                    <h4>' . $typeBadge . htmlspecialchars($row['fullName']) . '</h4>
                    <p><i class="fa fa-file-text"></i> ' . htmlspecialchars($pageName) . ' &nbsp;|&nbsp; <i class="fa fa-map-marker"></i> ' . htmlspecialchars($row['ipAddress']) . '</p>
                </div>
            </div>
            <div class="user-status">
                <div class="time-ago"><i class="fa fa-clock-o"></i> ' . $timeAgo . '</div>
            </div>
          </div>';
}

$stmt->close();

function getInitials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' min ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, g:i A', $time);
    }
}
?>
