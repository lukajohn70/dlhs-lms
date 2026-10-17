<?php
session_start();
$studentId = $_SESSION['studentId'] ?? null;
$testedTableName = $_SESSION['testedTableName'] ?? null;
$hasLogin = isset($_SESSION['studentLast_login']) && isset($_SESSION['studentId']);
session_write_close();

header('Content-Type: application/json');

if (!$hasLogin) {
    echo json_encode(array(
        'success' => false,
        'connected' => false,
        'message' => 'Session expired'
    ));
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";

$isPaused = false;

if ($testedTableName && $studentId && preg_match('/^[a-zA-Z0-9_]+$/', $testedTableName)) {
    $stmt = $connection->prepare("SELECT isPaused FROM `$testedTableName` WHERE examineeUserId = ?");
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

$pingResult = $connection->query("SELECT 1 AS pingValue");
echo json_encode(array(
    'success' => (bool) $pingResult,
    'connected' => (bool) $pingResult,
    'isPaused' => $isPaused,
    'serverTime' => date('Y-m-d H:i:s')
));
?>
