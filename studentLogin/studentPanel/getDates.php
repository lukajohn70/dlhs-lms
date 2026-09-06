<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
		$studentYearGroup=$_SESSION['studentYearGroup'];
		$testStatus=0; //status of not yet started test.
		
		$query4 = "SELECT * FROM tests WHERE yearGroup='$studentYearGroup'";
		$result4 = $connection->query($query4);
		$studentLoginUserId=$_SESSION['studentId'];
		$return_arr = array();
		if (($result4->num_rows)>0)
		{
			while($row4 = $result4->fetch_array(MYSQLI_NUM)) 
			{
				$testId=$row4[0];
				$tableName=$row4[13];
				$query44 = "SELECT * FROM $tableName WHERE testId='$testId' AND examineeUserId='$studentLoginUserId'";
				$result44 = $connection->query($query44);
									
				if (($result44->num_rows)>0)
				{
					$return_arr[] = array('title' => $row4[2].' ['.$row4[5].':'.$row4[6].$row4[7].']',
							'start' => $row4[3]);
				}
			}
			
		}
		echo json_encode($return_arr);
?>
