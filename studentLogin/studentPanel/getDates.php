<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

$studentYearGroup = isset($_SESSION['studentYearGroup']) ? $connection->real_escape_string((string) $_SESSION['studentYearGroup']) : '';
$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;

$return_arr = array();

if ($studentId > 0 && $studentYearGroup !== '') {
    $query = "SELECT testId, testName, testDate, startHour, startMinute, amOrPm, examineesTableName
              FROM tests
              WHERE yearGroup = '$studentYearGroup'
              ORDER BY testDate ASC";
    $result = $connection->query($query);
    
    if ($result) {
        $tests = array();
        $byTable = array();
        while ($row = $result->fetch_assoc()) {
            $examTable = preg_replace('/[^A-Za-z0-9_]/', '', (string) $row['examineesTableName']);
            if ($examTable !== '') {
                $tests[] = $row;
                $byTable[$examTable][] = (int) $row['testId'];
            }
        }

        // Batch query enrolled test IDs per unique examinees table
        $enrolledMap = array();
        foreach ($byTable as $examTable => $testIds) {
            $testIds = array_filter(array_map('intval', $testIds));
            if (empty($testIds)) {
                continue;
            }
            $idsList = implode(',', $testIds);
            $checkRes = $connection->query("SELECT testId FROM `{$examTable}` WHERE testId IN ({$idsList}) AND examineeUserId = {$studentId}");
            if ($checkRes) {
                while ($cRow = $checkRes->fetch_assoc()) {
                    $enrolledMap[(int) $cRow['testId']] = true;
                }
            }
        }

        foreach ($tests as $row) {
            $testId = (int) $row['testId'];
            if (!isset($enrolledMap[$testId])) {
                continue;
            }
            $return_arr[] = array(
                'title' => $row['testName'] . ' [' . $row['startHour'] . ':' . $row['startMinute'] . $row['amOrPm'] . ']',
                'start' => $row['testDate'],
            );
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($return_arr);
