<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
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

$activeOnly = isset($_GET['activeOnly']) && $_GET['activeOnly'] === '1';

$query = "SELECT * FROM academic_sessions";
if ($activeOnly) {
    $query .= " WHERE isActive = 1";
}
$query .= " ORDER BY startDate DESC";

$result = $connection->query($query);
$return_arr = array();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $return_arr[] = array(
            'sessionId' => (int) $row['sessionId'],
            'sessionName' => $row['sessionName'],
            'startDate' => $row['startDate'],
            'endDate' => $row['endDate'],
            'isCurrentSession' => (int) $row['isCurrentSession'],
            'isActive' => isset($row['isActive']) ? (int) $row['isActive'] : 1,
            'displayOrder' => isset($row['displayOrder']) ? (int) $row['displayOrder'] : 0
        );
    }
}

ob_end_clean();
header('Content-Type: application/json');
echo json_encode($return_arr);
