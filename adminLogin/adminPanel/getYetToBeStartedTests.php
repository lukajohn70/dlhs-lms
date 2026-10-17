<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
	$idToSend = mysqli_real_escape_string($connection, $_POST['idToSend']);
	
	$return_arr = array();
	$query = "SELECT t.testId, t.testName, t.testDate, t.duration, t.startHour, t.startMinute, t.amOrPm,
	                 s.subjectName, y.yearGroupName,
	                 CONCAT_WS(' ', st.surname, st.firstName, st.middleName) as teacherName
	          FROM tests t
	          LEFT JOIN subjects s ON t.subject = s.subjectId
	          LEFT JOIN yeargroup y ON t.yearGroup = y.yearGroupId
	          LEFT JOIN stafflogin st ON t.staffId = st.staffId
	          WHERE t.status = '$idToSend'
	          ORDER BY t.testId DESC";
	$result = $connection->query($query);

	if ($result) {
		while ($row = $result->fetch_assoc()) {
			$startMinute = (string) $row['startMinute'];
			$startMinuteToDisplay = strlen($startMinute) === 1 ? ("0" . $startMinute) : $startMinute;
			$startTime = $row['startHour'] . ':' . $startMinuteToDisplay . ' ' . $row['amOrPm'];

			$return_arr[] = array(
				"testId" => $row['testId'],
				"testName" => $row['testName'],
				"subjectName" => $row['subjectName'] !== null ? $row['subjectName'] : '',
				"yearGroupName" => $row['yearGroupName'] !== null ? $row['yearGroupName'] : '',
				"testDate" => $row['testDate'],
				"startTime" => $startTime,
				"duration" => $row['duration'] . ' minutes',
				"teacherName" => trim($row['teacherName'] !== null ? $row['teacherName'] : '')
			);
		}
	}
	echo json_encode($return_arr);
?>
