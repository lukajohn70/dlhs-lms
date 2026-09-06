<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['studentLast_login']) || !isset($_SESSION['studentId']) || !isset($_SESSION['idOfTest']) || !isset($_SESSION['testedTableName'])) {
	echo json_encode([ 'success' => false, 'message' => 'Unauthorized', 'remainingTime' => 0, 'shouldSubmit' => true ]);
	exit;
}

$studentId = $_SESSION['studentId'];
$testId = $_SESSION['idOfTest'];
$testedTableName = $_SESSION['testedTableName'];

// Fetch current remaining time and pause status from DB
$query = "SELECT remainingTime, isPaused FROM `$testedTableName` WHERE examineeUserId = ? AND testId = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("ii", $studentId, $testId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
	echo json_encode([ 'success' => false, 'message' => 'Test status not found', 'remainingTime' => 0, 'shouldSubmit' => true ]);
	exit;
}

$remainingTime = (int)$row['remainingTime'];
$isPaused = (int)($row['isPaused'] ?? 0);

// Compute elapsed since last tick using session startTime reference
$now = time();
if (!isset($_SESSION['startTime'])) {
	// Initialize reference to avoid large jumps
	$_SESSION['startTime'] = $now;
}

$elapsed = $now - (int)$_SESSION['startTime'];

if ($isPaused == 1) {
	// Reset reference so no elapsed time is accumulated when unpaused
	$_SESSION['startTime'] = $now;
} else if ($elapsed > 0) {
	$remainingTime = max(0, $remainingTime - $elapsed);
	// Persist the new remaining time
	$upd = $connection->prepare("UPDATE `$testedTableName` SET remainingTime = ? WHERE examineeUserId = ? AND testId = ?");
	$upd->bind_param("iii", $remainingTime, $studentId, $testId);
	$upd->execute();
}

// Reset reference for next tick
$_SESSION['startTime'] = $now;

$shouldSubmit = ($remainingTime <= 0);

echo json_encode([
	'success' => true,
	'remainingTime' => $remainingTime,
	'shouldSubmit' => $shouldSubmit
]);
exit;
?>









