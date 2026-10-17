<?php
// save_result_config.php — AJAX controller to save or update school term result configuration (Admin Only)
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== 'yes') {
    echo json_encode(['success' => false, 'msg' => 'Access Denied: Unauthorized.']);
    exit;
}

require_once '../../db_connection/dlhs_db_connection.php';

ob_clean();
header('Content-Type: application/json');

// Gather inputs
$session   = trim($_POST['academicSession']   ?? '');
$term      = intval($_POST['academicTerm']      ?? 0);
$startDate = trim($_POST['termStartDate']     ?? '');
$endDate   = trim($_POST['termEndDate']       ?? '');
$midVac    = trim($_POST['midTermVacationDate'] ?? '');
$midRes    = trim($_POST['midTermResumptionDate'] ?? '');
$nextRes   = trim($_POST['nextTermResumptionDate'] ?? '');

if (empty($session) || !$term) {
    echo json_encode(['success' => false, 'msg' => 'Missing required fields: Academic Session and Term.']);
    exit;
}

// Convert empty strings to NULL for database fields
$startDate = ($startDate === '') ? null : $startDate;
$endDate   = ($endDate === '') ? null : $endDate;
$midVac    = ($midVac === '')   ? null : $midVac;
$midRes    = ($midRes === '')   ? null : $midRes;
$nextRes   = ($nextRes === '')   ? null : $nextRes;

$sql = "INSERT INTO dlhs_result_config 
            (academicSession, academicTerm, termStartDate, termEndDate, midTermVacationDate, midTermResumptionDate, nextTermResumptionDate)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            termStartDate = VALUES(termStartDate),
            termEndDate = VALUES(termEndDate),
            midTermVacationDate = VALUES(midTermVacationDate),
            midTermResumptionDate = VALUES(midTermResumptionDate),
            nextTermResumptionDate = VALUES(nextTermResumptionDate)";

$stmt = $connection->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'msg' => 'Database SQL preparation failed: ' . $connection->error]);
    exit;
}

$stmt->bind_param('sisssss', $session, $term, $startDate, $endDate, $midVac, $midRes, $nextRes);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Database execution error: ' . $stmt->error]);
}

$stmt->close();
$connection->close();
?>
