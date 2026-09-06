<?php
session_start();
	error_reporting(0);
include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";

$staffId = (int) $_SESSION['staffId'];
$return_arr = array();
$query = "SELECT * FROM tests WHERE invigilatorId='{$staffId}' ORDER BY testDate DESC, testName ASC";
$result = $connection->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjectName = '';
        $subjectResult = $connection->query("SELECT * FROM subjects WHERE subjectId='" . mysqli_real_escape_string($connection, $row['subject']) . "' LIMIT 1");
        if ($subjectResult && $subjectResult->num_rows > 0) {
            $subjectData = $subjectResult->fetch_array(MYSQLI_NUM);
            $subjectName = $subjectData[1];
        }

        $academicYearName = '';
        $academicYearResult = $connection->query("SELECT * FROM academic_year WHERE academicYearId='" . mysqli_real_escape_string($connection, $row['academicYearId']) . "' LIMIT 1");
        if ($academicYearResult && $academicYearResult->num_rows > 0) {
            $academicYearData = $academicYearResult->fetch_array(MYSQLI_NUM);
            $academicYearName = $academicYearData[1];
        }

        $yearGroupName = '';
        $yearGroupResult = $connection->query("SELECT * FROM yeargroup WHERE yearGroupId='" . mysqli_real_escape_string($connection, $row['yearGroup']) . "' LIMIT 1");
        if ($yearGroupResult && $yearGroupResult->num_rows > 0) {
            $yearGroupData = $yearGroupResult->fetch_array(MYSQLI_NUM);
            $yearGroupName = $yearGroupData[1];
        }

        $invigilatorName = '';
        if ((int) $row['invigilatorId'] > 0) {
            $invigilatorResult = $connection->query("SELECT * FROM stafflogin WHERE staffId='" . (int) $row['invigilatorId'] . "' LIMIT 1");
            if ($invigilatorResult && $invigilatorResult->num_rows > 0) {
                $invigilatorData = $invigilatorResult->fetch_array(MYSQLI_NUM);
                $invigilatorName = trim($invigilatorData[1] . ' ' . $invigilatorData[2] . ' ' . $invigilatorData[3]);
            }
        }

        $startMinute = (string) $row['startMinute'];
        if (strlen($startMinute) === 1) {
            $startMinute = '0' . $startMinute;
        }

        $return_arr[] = array(
            "testId" => (int) $row['testId'],
            "testName" => $row['testName'],
            "testDate" => $row['testDate'],
            "duration" => $row['duration'],
            "timeToDisplay" => $row['startHour'] . ':' . $startMinute . ' ' . $row['amOrPm'],
            "subjectName" => $subjectName,
            "yearGroupId" => $row['yearGroup'],
            "yearGroupName" => $yearGroupName,
            "testYear" => $academicYearName,
            "reviewOption" => $row['reviewOption'],
            "essayOption" => $row['essayOption'],
            "testType" => dlhsGetEffectiveTestType(isset($row['testType']) ? $row['testType'] : ''),
            "invigilatorId" => (int) $row['invigilatorId'],
            "invigilatorName" => $invigilatorName,
            "status" => $row['status']
        );
    }
}

dlhsSortTestsByTypeAndDate($return_arr);
echo json_encode($return_arr);
?>
