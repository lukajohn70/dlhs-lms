<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
session_write_close();

include __DIR__ . "/../../db_connection/dlhs_db_connection.php";

$return_arr = array();

if (isset($_POST["selectedTestId"]) && isset($_POST["selectedClassId"]))
{
	$selectedTestId = mysqli_real_escape_string($connection, $_POST["selectedTestId"]);
	$selectedClassId = mysqli_real_escape_string($connection, $_POST["selectedClassId"]);
	
	// Getting the yeargroup and subject of the selected test
	$getTestInfo = "SELECT yearGroup, subject FROM tests WHERE testId='$selectedTestId' LIMIT 1";
	$result1 = $connection->query($getTestInfo);
	$row1 = $result1 ? $result1->fetch_assoc() : null;
	$yearGroupId = $row1 ? (int)$row1['yearGroup'] : 0;
	$subjectId = $row1 ? (int)$row1['subject'] : 0;
	
	if ($yearGroupId > 0 && !empty($selectedClassId))
	{
		if ($selectedClassId === 'all') {
			if ($staffId > 0 && !isset($_SESSION['adminLoggedIn'])) {
				$classSub = "SELECT c.classId FROM classes c 
				             INNER JOIN subject_teacher_assignment sta ON c.classId = sta.classId 
				             WHERE c.classYearGroup = '$yearGroupId' 
				             AND sta.subjectId = '$subjectId' 
				             AND sta.teacherId = '$staffId'";
				$studentsQuery = "SELECT s.studentId, s.surname, s.firstName, s.middleName, c.className 
				                  FROM studentlogin s 
				                  INNER JOIN classes c ON s.classId = c.classId 
				                  WHERE s.classId IN ($classSub) 
				                  ORDER BY c.className ASC, s.surname ASC, s.firstName ASC";
			} else {
				$studentsQuery = "SELECT s.studentId, s.surname, s.firstName, s.middleName, c.className 
				                  FROM studentlogin s 
				                  INNER JOIN classes c ON s.classId = c.classId 
				                  WHERE s.yearGroupId='$yearGroupId' 
				                  ORDER BY c.className ASC, s.surname ASC, s.firstName ASC";
			}
		} else {
			$studentsQuery = "SELECT s.studentId, s.surname, s.firstName, s.middleName, c.className 
			                  FROM studentlogin s 
			                  LEFT JOIN classes c ON s.classId = c.classId
			                  WHERE s.yearGroupId='$yearGroupId' AND s.classId='$selectedClassId' 
			                  ORDER BY s.surname ASC, s.firstName ASC";
		}
		$result = $connection->query($studentsQuery);
		if ($result && ($result->num_rows) > 0)
		{	
			while ($row = $result->fetch_assoc())
			{	
				$return_arr[] = array(
					"studentId"  => $row['studentId'],
					"surname"    => $row['surname'],
					"firstName"  => $row['firstName'],
					"middleName" => $row['middleName'],
					"className"  => isset($row['className']) ? $row['className'] : ''
				);
			}
		}
	}
}

echo json_encode($return_arr);
?>

