<?php
session_start();
	error_reporting(0);
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/online_tracking_schema.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

dlhsEnsureOnlineTrackingTables($connection);

header('Content-Type: application/json');

// Get online users count by type
$statsQuery = "
    SELECT 
        userType,
        COUNT(*) as count
    FROM online_users
    WHERE isActive = 1
    GROUP BY userType
";

$stats = [
    'student' => 0,
    'staff' => 0,
    'admin' => 0,
    'total' => 0,
    'testTakers' => 0
];

$result = $connection->query($statsQuery);
while ($row = $result->fetch_assoc()) {
    $stats[$row['userType']] = (int)$row['count'];
    $stats['total'] += (int)$row['count'];
}

// Get active test takers count
$activeTestsQuery = "SELECT COUNT(DISTINCT studentId) as count FROM active_test_takers WHERE isActive = 1";
$result = $connection->query($activeTestsQuery);
$stats['testTakers'] = (int)($result->fetch_assoc()['count'] ?? 0);

echo json_encode($stats);
?>
