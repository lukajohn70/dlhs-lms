<?php
// get_result_config.php — AJAX controller to fetch existing school term result configuration (Admin Only)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== 'yes') {
    echo json_encode(['success' => false, 'msg' => 'Access Denied: Unauthorized.']);
    exit;
}

require_once '../../db_connection/dlhs_db_connection.php';

header('Content-Type: application/json');

$session = trim($_REQUEST['academicSession'] ?? '');
$term    = intval($_REQUEST['academicTerm'] ?? 0);

if (empty($session) || !$term) {
    echo json_encode(['success' => false, 'msg' => 'Missing session or term.']);
    exit;
}

$sql = "SELECT * FROM dlhs_result_config WHERE academicSession = ? AND academicTerm = ? LIMIT 1";
$stmt = $connection->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'msg' => 'Prepare failed.']);
    exit;
}

$stmt->bind_param('si', $session, $term);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => true, 'data' => null]);
}

$stmt->close();
$connection->close();
?>
