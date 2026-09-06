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

$query = "SELECT * FROM exam_types";
if ($activeOnly) {
    $query .= " WHERE isActive = 1";
}
$query .= " ORDER BY isDefault DESC, displayOrder ASC, examTypeName ASC";

$result = $connection->query($query);
$return_arr = array();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $return_arr[] = array(
            'examTypeId' => (int) $row['examTypeId'],
            'examTypeName' => $row['examTypeName'],
            'isActive' => (int) $row['isActive'],
            'isDefault' => (int) $row['isDefault'],
            'displayOrder' => (int) $row['displayOrder']
        );
    }
}

ob_end_clean();
header('Content-Type: application/json');
echo json_encode($return_arr);
