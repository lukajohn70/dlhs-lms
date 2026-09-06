<?php
// prepareObjectives.php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['studentId']) || !isset($_SESSION['testId'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

$studentId = $_SESSION['studentId'];
$testId = $_SESSION['testId'];

// Get test info
$testQuery = "SELECT * FROM tests WHERE testId='$testId'";
$testResult = $connection->query($testQuery);
if (!$testResult || !$testResult->num_rows) {
    echo json_encode(['success' => false, 'message' => 'Test not found.']);
    exit;
}
$testRow = $testResult->fetch_assoc();

// Get tested table info

// Use correct column names from the tests table
$questionsTableName = $testRow['tableName'] ?? '';
$testedTableName = $testRow['examineesTableName'] ?? '';
$answersTableName = $testRow['answersTable'] ?? '';


if (!$testedTableName || !$answersTableName || !$questionsTableName) {
    echo json_encode(['success' => false, 'message' => 'Test table info missing.']);
    exit;
}

// Get questions array and total questions for this student/test
$studentTestQuery = "SELECT * FROM `$testedTableName` WHERE examineeUserId='$studentId' AND testId='$testId'";
$studentTestResult = $connection->query($studentTestQuery);
if (!$studentTestResult || !$studentTestResult->num_rows) {
    echo json_encode(['success' => false, 'message' => 'Student test record not found.']);
    exit;
}
$studentTestRow = $studentTestResult->fetch_assoc();
$questionsIdsArray = isset($studentTestRow['questionsArray']) ? unserialize($studentTestRow['questionsArray']) : [];
$totalQuestions = is_array($questionsIdsArray) ? count($questionsIdsArray) : 0;

// Set all required session variables for objectives
$_SESSION['idOfTest'] = $testId;
$_SESSION['questionsIdsArray'] = $questionsIdsArray;
$_SESSION['totalQuestions'] = $totalQuestions;
$_SESSION['questTableName'] = $questionsTableName;
$_SESSION['answersTableName'] = $answersTableName;
$_SESSION['testedTableName'] = $testedTableName;

// Optionally, set any other session variables needed by exam_all.php

echo json_encode(['success' => true]);
