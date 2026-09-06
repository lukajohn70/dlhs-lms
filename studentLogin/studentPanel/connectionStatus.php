<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['studentLast_login']) || !isset($_SESSION['studentId'])) {
    echo json_encode(array(
        'success' => false,
        'connected' => false,
        'message' => 'Session expired'
    ));
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";

$isPaused = false;

if (isset($_SESSION['testedTableName']) && isset($_SESSION['studentId'])) {
    $tableName = $_SESSION['testedTableName'];
    $studentId = $_SESSION['studentId'];
    
    // Only query if the table name looks valid
    if (preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        $stmt = $connection->prepare("SELECT isPaused FROM `$tableName` WHERE userLoginId = ?");
        if ($stmt) {
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $isPaused = (bool) $row['isPaused'];
            }
            $stmt->close();
        }
    }
}

$pingResult = $connection->query("SELECT 1 AS pingValue");
echo json_encode(array(
    'success' => (bool) $pingResult,
    'connected' => (bool) $pingResult,
    'isPaused' => $isPaused,
    'serverTime' => date('Y-m-d H:i:s')
));
?>
