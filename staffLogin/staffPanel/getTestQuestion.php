<?php
session_start();
$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
$isAdmin = isset($_SESSION['adminLoggedIn']);
session_write_close();

header('Content-Type: application/json');

if (!$isAdmin && $staffId <= 0) {
    echo json_encode(array());
    exit;
}

$testId = isset($_POST['testId']) ? (int) $_POST['testId'] : 0;
if ($testId <= 0) {
    echo json_encode(array());
    exit;
}

include "../../db_connection/dlhs_db_connection.php";

$query = "SELECT tableName FROM tests WHERE testId = '{$testId}'" . ($isAdmin ? "" : " AND staffId = '{$staffId}'") . " LIMIT 1";
$result = $connection->query($query);
if (!$result || $result->num_rows === 0) {
    echo json_encode(array());
    exit;
}

$row = $result->fetch_assoc();
$questionsTableName = trim((string) $row['tableName']);

if ($questionsTableName === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $questionsTableName)) {
    echo json_encode(array());
    exit;
}

$getQuestions = "SELECT questionId, question, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion, solvedSolution FROM `{$questionsTableName}` ORDER BY questionId ASC";
$result1 = $connection->query($getQuestions);
if (!$result1 || $result1->num_rows === 0) {
    echo json_encode(array());
    exit;
}

$rawQuestions = array();
$questionIds = array();
while ($row1 = $result1->fetch_assoc()) {
    $qId = (int) $row1['questionId'];
    $questionIds[] = $qId;
    $rawQuestions[] = $row1;
}

$stimulusMap = array();
if (!empty($questionIds)) {
    $idList = implode(',', $questionIds);
    $stimQuery = "
        SELECT l.questionId, s.stimulusId, s.stimulusType, s.stimulusTitle, s.stimulusContent
        FROM question_shared_stimulus_links l
        INNER JOIN question_shared_stimuli s ON s.stimulusId = l.stimulusId
        WHERE l.testId = '{$testId}' AND l.questionId IN ({$idList})
    ";
    $stimResult = @$connection->query($stimQuery);
    if ($stimResult && $stimResult->num_rows > 0) {
        while ($sRow = $stimResult->fetch_assoc()) {
            $qId = (int) $sRow['questionId'];
            $stType = isset($sRow['stimulusType']) ? strtoupper(trim((string) $sRow['stimulusType'])) : 'PASSAGE';
            $stTitle = html_entity_decode((string) $sRow['stimulusTitle'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $stimulusMap[$qId] = array(
                'stimulusId' => (int) $sRow['stimulusId'],
                'stimulusType' => $stType,
                'stimulusTitle' => $stTitle,
                'stimulusContent' => html_entity_decode((string) $sRow['stimulusContent'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'stimulusLabel' => trim(($stTitle !== '' ? $stTitle : 'Shared material') . ' (' . $stType . ')')
            );
        }
    }
}

$return_arr = array();
foreach ($rawQuestions as $r) {
    $qId = (int) $r['questionId'];
    $stimulusMeta = isset($stimulusMap[$qId]) ? $stimulusMap[$qId] : null;

    $return_arr[] = array(
        "questionId"      => $qId,
        "question"        => htmlspecialchars_decode($r['question']),
        "optionA"         => htmlspecialchars_decode($r['optionA']),
        "optionB"         => htmlspecialchars_decode($r['optionB']),
        "optionC"         => htmlspecialchars_decode($r['optionC']),
        "optionD"         => htmlspecialchars_decode($r['optionD']),
        "optionE"         => htmlspecialchars_decode($r['optionE']),
        "correctOption"   => $r['correctOption'],
        "markForQuestion" => $r['markForQuestion'],
        "solvedSolution"  => htmlspecialchars_decode($r['solvedSolution']),
        "stimulusId"      => $stimulusMeta ? $stimulusMeta['stimulusId'] : 0,
        "stimulusTitle"   => $stimulusMeta ? $stimulusMeta['stimulusTitle'] : '',
        "stimulusType"    => $stimulusMeta ? $stimulusMeta['stimulusType'] : '',
        "stimulusContent" => $stimulusMeta ? $stimulusMeta['stimulusContent'] : '',
        "stimulusLabel"   => $stimulusMeta ? $stimulusMeta['stimulusLabel'] : ''
    );
}

echo json_encode($return_arr);
?>
