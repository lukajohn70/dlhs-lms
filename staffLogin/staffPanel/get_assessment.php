<?php
// get_assessment.php — AJAX endpoint to fetch a student's term assessments
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) { 
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']); 
    exit; 
}
require_once '../../db_connection/dlhs_db_connection.php';

header('Content-Type: application/json');

$studentId = intval($_REQUEST['studentId']      ?? 0);
$term      = intval($_REQUEST['academicTerm']   ?? 0);
$session   = trim($_REQUEST['academicSession']  ?? '');

if (!$studentId || !$term || !$session) {
    echo json_encode(['success' => false, 'msg' => 'Missing required fields']);
    exit;
}

$sql = "SELECT * FROM dlhs_student_assessments 
        WHERE studentId = ? AND academicTerm = ? AND academicSession = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param('iis', $studentId, $term, $session);
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
