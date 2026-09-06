<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM classes";
		$result = $connection->query($query);
		if(($result->num_rows)>0)
		{
			while($row = $result->fetch_array(MYSQLI_NUM))
			{
				$classId  = $row[0];
				$classYearGroupId = $row[1];
				$className = $row[2];
				
				//Getting the name of the year group
				$query1="SELECT * FROM yeargroup WHERE yearGroupId='$classYearGroupId'";
				$result1 = $connection->query($query1);
				$row1 = $result1->fetch_array(MYSQLI_NUM);
				$yearGroupName = $row1[1];
										
				$return_arr[] = array("classId" => $classId,
								"classYearGroupId" => $classYearGroupId,
								"className" => $className,
								"yearGroupName" => $yearGroupName);
			}
		}
		echo json_encode($return_arr);
?>
