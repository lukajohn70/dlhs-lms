<?php
session_start();
$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
session_write_close();

require_once "../../db_connection/dlhs_db_connection.php";

$where = "staffId = $staffId";
if (!empty($_GET['start']) && preg_match('/^\d{4}-\d{2}-\d{2}/', $_GET['start'], $mStart)) {
    $startDate = $connection->real_escape_string($mStart[0]);
    $where .= " AND testDate >= '$startDate'";
}
if (!empty($_GET['end']) && preg_match('/^\d{4}-\d{2}-\d{2}/', $_GET['end'], $mEnd)) {
    $endDate = $connection->real_escape_string($mEnd[0]);
    $where .= " AND testDate <= '$endDate'";
}

// Only fetch columns needed for calendar events
$query = "SELECT testName, testDate, startHour, startMinute, amOrPm
          FROM tests
          WHERE $where
          ORDER BY testDate DESC
          LIMIT 250";
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
header('Content-Type: application/json');
echo json_encode($return_arr);

