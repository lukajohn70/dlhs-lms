<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM form_teacher_assignment";
		$result = $connection->query($query);
		if(($result->num_rows)>0)
		{
			while($row = $result->fetch_array(MYSQLI_NUM))
			{
				$formTeacherAssignmentId  = $row[0];
				$staffId = $row[1];
				$yearGroupId = $row[2];
				$classId = $row[3];
				
				//Getting the name of the staff
				$query1="SELECT * FROM stafflogin WHERE staffId='$staffId'";
				$result1 = $connection->query($query1);
				$row1 = $result1->fetch_array(MYSQLI_NUM);
				$staffName = $row1[1].' '.$row1[2].' '.$row1[3];
				
				//Getting the name of the year group
				$query2="SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId'";
				$result2 = $connection->query($query2);
				$row2 = $result2->fetch_array(MYSQLI_NUM);
				$yearGroupName = $row2[1];
				
				//Getting the name of the class
				$query3="SELECT * FROM classes WHERE classId='$classId'";
				$result3 = $connection->query($query3);
				$row3 = $result3->fetch_array(MYSQLI_NUM);
				$className = $row3[2];
										
				$return_arr[] = array("formTeacherAssignmentId" => $formTeacherAssignmentId,
								"staffId" => $staffId,
								"classId" => $classId,
								"yearGroupId" => $yearGroupId,
								"staffName" => $staffName,
								"className" => $className,
								"yearGroupName" => $yearGroupName);
			}
		}
		echo json_encode($return_arr);
?>
