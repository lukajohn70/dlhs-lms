<?php
session_start();
require_once 'sessionTime.php';
if ((time() - $_SESSION['adminLast_login']) > $allottedTime)
{	
	require_once 'unsetSessions.php';
	session_write_close();
	echo 0;
	exit;
}

session_write_close();

include "../../db_connection/dlhs_db_connection.php";

if(isset($_POST['classId']) && isset($_POST['checkedStudents']))
{    
	$classId = intval($_POST['classId']);
	$checkStudents = trim($_POST['checkedStudents']);
	
	if ($classId <= 0 || empty($checkStudents)) {
		echo 2;
		exit;
	}

	$str_arr = explode(",", $checkStudents); 
	
	// Getting the yeargroup id of the selected class
	$query = "SELECT classYearGroup FROM classes WHERE classId='$classId'";
	$result = $connection->query($query);
	if ($result && $result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$yearGroupId = intval($row['classYearGroup']);
		
		$connection->begin_transaction();
		try {
			$stmt = $connection->prepare("UPDATE studentlogin SET classId = ?, yearGroupId = ? WHERE studentId = ?");
			foreach($str_arr as $chk1)  
			{  
				$studentId = intval(trim($chk1));
				if ($studentId > 0) {
					$stmt->bind_param("iii", $classId, $yearGroupId, $studentId);
					$stmt->execute();
				}
			}
			$stmt->close();
			$connection->commit();
			echo 1;
		} catch (Exception $e) {
			$connection->rollback();
			echo 2;
		}
	} else {
		echo 2;
	}
}
exit;
?>