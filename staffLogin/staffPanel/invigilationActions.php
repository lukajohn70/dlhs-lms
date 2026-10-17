<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

// Suppress raw errors for AJAX stability, but keep log access
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Use output buffering to catch any stray whitespaces or warnings
ob_start();

if (!isset($_SESSION['staffId']) && !isset($_SESSION['adminLoggedIn'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$action = $_POST['action'] ?? '';
$testId = $_POST['testId'] ?? '';

$tableName = '';
$getTest = $connection->query("SELECT examineesTableName FROM tests WHERE testId='$testId'");
$testRow = $getTest ? $getTest->fetch_assoc() : null;
$tableName = $testRow['examineesTableName'] ?? '';

if (empty($tableName)) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Test table not found for ID: $testId"]);
    exit;
}

function runQuery($sql) {
    global $connection;
    $res = $connection->query($sql);
    if (!$res) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "SQL Error: " . $connection->error, "sql" => $sql]);
        exit;
    }
    return $res;
}

switch ($action) {

    case 'global_test_control':
        $status = (int)($_POST['status'] ?? -1);
        if (!in_array($status, [0, 1, 2])) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Invalid status: $status"]);
            exit;
        }
        runQuery("UPDATE tests SET status='$status' WHERE testId='$testId'");
        if ($status === 1) {
            // Global Start: Mark all as present and started
        runQuery("UPDATE `$tableName` SET isStarted=1 WHERE testId='$testId'");
        } elseif ($status === 2) {
            // Global Stop: Force submit for all
            runQuery("UPDATE `$tableName` SET testStatus=2, timeSubmittedTest='" . date("H:i") . "' WHERE testId='$testId' AND testStatus != 2");
        }
        break;

    // mark_attendance action removed — attendance gate eliminated.
    // Students can now start as soon as the global test status is open.

    case 'mark_all_present':
        $staffId = $_SESSION['staffId'] ?? 0;
        $classIds = [];
        $invQ = runQuery("SELECT classId FROM test_class_invigilators WHERE testId='$testId' AND invigilatorId='$staffId'");
        while ($iRow = $invQ->fetch_assoc()) { $classIds[] = (int)$iRow['classId']; }
        
        $condition = !empty($classIds) ? " AND studentClassId IN (" . implode(',', $classIds) . ")" : "";
        runQuery("UPDATE `$tableName` SET isStarted=1, isPaused=0 WHERE testId='$testId' $condition");
        break;

    case 'mark_selected_present':
        $studentIds = $_POST['studentIds'] ?? [];
        if (!is_array($studentIds) || empty($studentIds)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "No students selected"]);
            exit;
        }
        $safeIds = array_map(function($id) use ($connection) { return "'" . $connection->real_escape_string($id) . "'"; }, $studentIds);
        $inList = implode(',', $safeIds);
        runQuery("UPDATE `$tableName` SET isStarted=1, isPaused=0 WHERE examineeUserId IN ($inList)");
        break;

    case 'toggle_pause':
        $studentId = $connection->real_escape_string($_POST['studentId'] ?? '');
        $status    = (int)($_POST['status'] ?? 0);
        runQuery("UPDATE `$tableName` SET isPaused='$status' WHERE examineeUserId='$studentId'");
        break;

    case 'unlock_student':
        $studentId = $connection->real_escape_string($_POST['studentId'] ?? '');
        runQuery("UPDATE `$tableName` SET isStarted=1, isPaused=0 WHERE examineeUserId='$studentId'");
        break;

    case 'add_time':
        $studentId = $connection->real_escape_string($_POST['studentId'] ?? '');
        $minutes   = (int)($_POST['minutes'] ?? 0);
        $seconds   = $minutes * 60;
        runQuery("UPDATE `$tableName` SET remainingTime = GREATEST(0, remainingTime + $seconds) WHERE examineeUserId='$studentId'");
        break;

    case 'force_submit':
        $studentId = $connection->real_escape_string($_POST['studentId'] ?? '');
        runQuery("UPDATE `$tableName` SET testStatus=2, timeSubmittedTest='" . date("H:i") . "' WHERE examineeUserId='$studentId'");
        break;

    case 'arm_test_control':
        $status  = (int)($_POST['status'] ?? -1);
        $staffId = $_SESSION['staffId'] ?? 0;
        $classes = [];
        $invQuery = runQuery("SELECT classId FROM test_class_invigilators WHERE testId='$testId' AND invigilatorId='$staffId'");
        while ($iRow = $invQuery->fetch_assoc()) { $classes[] = (int)$iRow['classId']; }
        
        $classCondition = !empty($classes) ? " AND studentClassId IN (" . implode(',', $classes) . ")" : "";

        if ($status == 1) {
            runQuery("UPDATE `$tableName` SET isStarted=1, isPaused=0 WHERE testId='$testId' $classCondition");
        } elseif ($status == 0) {
            runQuery("UPDATE `$tableName` SET isPaused=1 WHERE testId='$testId' $classCondition AND testStatus=1");
        } elseif ($status == 2) {
            runQuery("UPDATE `$tableName` SET testStatus=2, timeSubmittedTest='" . date("H:i") . "' WHERE testId='$testId' $classCondition AND testStatus != 2");
        }
        break;

    default:
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Invalid action: $action"]);
        exit;
}

// Clean up any buffered output and send clean JSON
ob_end_clean();
header('Content-Type: application/json');
echo json_encode(["status" => "success"]);
exit;
?>
