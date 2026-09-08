<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;

// Only fetch columns needed for calendar events
$query = "SELECT testName, testDate, startHour, startMinute, amOrPm
          FROM tests
          WHERE staffId = $staffId
          ORDER BY testDate ASC";
$result = $connection->query($query);
$return_arr = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $return_arr[] = array(
            'title' => $row['testName'] . ' [' . $row['startHour'] . ':' . $row['startMinute'] . $row['amOrPm'] . ']',
            'start' => $row['testDate'],
        );
    }
}
echo json_encode($return_arr);
