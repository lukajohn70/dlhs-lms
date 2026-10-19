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
	
	// Getting the yeargroupId of the selected test
	$getYearGroupId = "SELECT yearGroup FROM tests WHERE testId='$selectedTestId' LIMIT 1";
	$result1 = $connection->query($getYearGroupId);
	$row1 = $result1 ? $result1->fetch_assoc() : null;
	$yearGroupId = $row1 ? (int)$row1['yearGroup'] : 0;
	
	if ($yearGroupId > 0 && !empty($selectedClassId))
	{
		$studentsQuery = "SELECT studentId, surname, firstName, middleName 
		                  FROM studentlogin 
		                  WHERE yearGroupId='$yearGroupId' AND classId='$selectedClassId' 
		                  ORDER BY surname ASC, firstName ASC";
		$result = $connection->query($studentsQuery);
		if ($result && ($result->num_rows) > 0)
		{	
			while ($row = $result->fetch_assoc())
			{	
				$return_arr[] = array(
					"studentId"  => $row['studentId'],
					"surname"   => $row['surname'],
					"firstName" => $row['firstName'],
					"middleName"=> $row['middleName']
				);
			}
		}
	}
}

echo json_encode($return_arr);
?>

