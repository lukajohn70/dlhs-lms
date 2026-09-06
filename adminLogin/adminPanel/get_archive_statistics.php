<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$oldErrorReporting = error_reporting(0);
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', 0);
include "../../db_connection/dlhs_db_connection.php";
error_reporting($oldErrorReporting);
ini_set('display_errors', $oldDisplayErrors);

// Get statistics
$activeQuery = "SELECT COUNT(*) as count FROM tests WHERE isArchived = 0 OR isArchived IS NULL";
$archivedQuery = "SELECT COUNT(*) as count FROM tests WHERE isArchived = 1";
$totalQuery = "SELECT COUNT(*) as count FROM tests";

$activeResult = $connection->query($activeQuery);
$archivedResult = $connection->query($archivedQuery);
$totalResult = $connection->query($totalQuery);

$stats = array(
    'activeTests' => $activeResult ? $activeResult->fetch_assoc()['count'] : 0,
    'archivedTests' => $archivedResult ? $archivedResult->fetch_assoc()['count'] : 0,
    'totalTests' => $totalResult ? $totalResult->fetch_assoc()['count'] : 0
);

ob_end_clean();
header('Content-Type: application/json');
echo json_encode($stats);
?>
