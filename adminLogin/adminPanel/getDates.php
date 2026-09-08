<?php
session_start();
error_reporting(0);
require_once "../../db_connection/dlhs_db_connection.php";

// Only fetch the columns needed for calendar events (was SELECT * previously)
$query = "SELECT testName, testDate, startHour, startMinute, amOrPm FROM tests ORDER BY testDate ASC";
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
