<?php
session_start();
require_once 'sessionTime.php';

header('Content-Type: application/json');

if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
    require_once 'unsetSessions.php';
    echo json_encode(array('success' => false, 'message' => 'Session expired.'));
    exit;
}

if (!isset($_SESSION['staffId'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized request.'));
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/question_authoring_helper.php";

$staffId = (int) $_SESSION['staffId'];
$testId = isset($_POST['testId']) ? (int) $_POST['testId'] : 0;
$testRow = dlhsFetchTestRowById($connection, $testId);

if (!$testRow || (int) $testRow['staffId'] !== $staffId) {
    echo json_encode(array('success' => false, 'message' => 'Test not found.'));
    exit;
}

$stimuli = dlhsFetchSharedStimuliForTest($connection, $testId, $staffId);
echo json_encode(array('success' => true, 'stimuli' => $stimuli));

