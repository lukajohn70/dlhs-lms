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
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/question_authoring_helper.php";

$staffId = (int) $_SESSION['staffId'];
$testId = isset($_POST['testId']) ? (int) $_POST['testId'] : 0;
$stimulusId = isset($_POST['stimulusId']) ? (int) $_POST['stimulusId'] : 0;
$stimulusType = dlhsNormalizeStimulusType(isset($_POST['stimulusType']) ? $_POST['stimulusType'] : '');
$stimulusTitle = trim((string) (isset($_POST['stimulusTitle']) ? $_POST['stimulusTitle'] : ''));
$stimulusContent = isset($_POST['stimulusContent']) ? (string) $_POST['stimulusContent'] : '';
$questionIds = isset($_POST['questionIds']) && is_array($_POST['questionIds']) ? $_POST['questionIds'] : array();

$testRow = dlhsFetchTestRowById($connection, $testId);
if (!$testRow || (int) $testRow['staffId'] !== $staffId) {
    echo json_encode(array('success' => false, 'message' => 'Test not found.'));
    exit;
}

$questionEntryCheck = dlhsCheckQuestionEntryAllowed($connection, $testRow);
if (!$questionEntryCheck['allowed']) {
    echo json_encode(array('success' => false, 'message' => $questionEntryCheck['message']));
    exit;
}

if (!dlhsEnsureQuestionStimulusTables($connection)) {
    echo json_encode(array('success' => false, 'message' => 'Shared material storage could not be prepared.'));
    exit;
}

if (!dlhsEditorHtmlHasContent($stimulusContent)) {
    echo json_encode(array('success' => false, 'message' => 'Enter the shared passage, image, table, or instruction content.'));
    exit;
}

if ($stimulusTitle === '') {
    $stimulusTitle = ucfirst(strtolower($stimulusType)) . ' material';
}

$escapedTitle = mysqli_real_escape_string($connection, $stimulusTitle);
$escapedContent = mysqli_real_escape_string($connection, $stimulusContent);

if ($stimulusId > 0) {
    $existing = $connection->query(
        "SELECT stimulusId
         FROM question_shared_stimuli
         WHERE stimulusId='{$stimulusId}'
           AND testId='{$testId}'
           AND staffId='{$staffId}'
         LIMIT 1"
    );

    if (!$existing || $existing->num_rows < 1) {
        echo json_encode(array('success' => false, 'message' => 'Shared material not found.'));
        exit;
    }

    $saved = $connection->query(
        "UPDATE question_shared_stimuli
         SET stimulusType='{$stimulusType}',
             stimulusTitle='{$escapedTitle}',
             stimulusContent='{$escapedContent}'
         WHERE stimulusId='{$stimulusId}'"
    );
} else {
    $saved = $connection->query(
        "INSERT INTO question_shared_stimuli (testId, staffId, stimulusType, stimulusTitle, stimulusContent)
         VALUES ('{$testId}', '{$staffId}', '{$stimulusType}', '{$escapedTitle}', '{$escapedContent}')"
    );
    if ($saved) {
        $stimulusId = (int) $connection->insert_id;
    }
}

if (!$saved) {
    echo json_encode(array('success' => false, 'message' => 'The shared material could not be saved: ' . $connection->error));
    exit;
}

if (!dlhsReplaceStimulusQuestionLinks($connection, $testRow, $stimulusId, $questionIds)) {
    echo json_encode(array('success' => false, 'message' => 'The shared material was saved, but question links could not be updated.'));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => 'Shared material saved successfully.',
    'stimulusId' => $stimulusId,
    'stimuli' => dlhsFetchSharedStimuliForTest($connection, $testId, $staffId)
));

