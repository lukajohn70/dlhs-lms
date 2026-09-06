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
$stimulusId = isset($_POST['stimulusId']) ? (int) $_POST['stimulusId'] : 0;
$testRow = dlhsFetchTestRowById($connection, $testId);

if (!$testRow || (int) $testRow['staffId'] !== $staffId) {
    echo json_encode(array('success' => false, 'message' => 'Test not found.'));
    exit;
}

if (!dlhsEnsureQuestionStimulusTables($connection)) {
    echo json_encode(array('success' => false, 'message' => 'Shared material storage could not be prepared.'));
    exit;
}

if ($stimulusId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Select a shared material to delete.'));
    exit;
}

$connection->query("DELETE FROM question_shared_stimulus_links WHERE testId='{$testId}' AND stimulusId='{$stimulusId}'");
$deleted = $connection->query(
    "DELETE FROM question_shared_stimuli
     WHERE stimulusId='{$stimulusId}'
       AND testId='{$testId}'
       AND staffId='{$staffId}'"
);

if (!$deleted) {
    echo json_encode(array('success' => false, 'message' => 'The shared material could not be deleted.'));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => 'Shared material deleted successfully.',
    'stimuli' => dlhsFetchSharedStimuliForTest($connection, $testId, $staffId)
));
