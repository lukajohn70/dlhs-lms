<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['addStudentsToResultTable']))
		{    
			$checkStudents = $_POST['checkedStudents'];
			$formTeacherId = $_SESSION['staffId'];
			
			$str_arr = explode (",", $checkStudents); 
			
			$idToSelect = 1;
			//Getting the current result year
			$getCurrentResultYear = "SELECT * FROM set_result_year WHERE id='$idToSelect'";
			$result = $connection->query($getCurrentResultYear);
			$row = $result->fetch_array(MYSQLI_NUM);
			$currentResultYearId = $row[1];
			$currentResultYearName = $row[2];
					
			//Getting the current result term
			$getCurrentResultTerm = "SELECT * FROM result_set_current_term WHERE id='$idToSelect'";
			$result1 = $connection->query($getCurrentResultTerm);
			$row1 = $result1->fetch_array(MYSQLI_NUM);
			$currentResultTermId = $row1[1];
			$currentResultTerm = $row1[2];
			
			//Getting the resultTable name for current result year
			$getResultYearTableName = "SELECT * FROM results_table_names WHERE yearId='$currentResultYearId'";
			$result11 = $connection->query($getResultYearTableName);
			$row11 = $result11->fetch_array(MYSQLI_NUM);
			$resultTableName = $row11[2];
			
			foreach($str_arr as $chk1)  
			{  
				$studentId = $chk1;
				
				//Getting the studentClassIdd, yearGroupId houseId, and sportActivityId from the studentLogin table.
				$query2 = "SELECT * FROM studentlogin WHERE studentId='$studentId'";
				$result2 = $connection->query($query2);
				$row2 = $result2->fetch_array(MYSQLI_NUM);
				$yearGroupId = $row2[8];
				$classId = $row2[9];
				$houseId = $row2[12];
				$sportId = $row2[13];
				
				//Getting the sectionId from the yeargroup table
				$query3 = "SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId' LIMIT 1";
				$result3 = $connection->query($query3);
				$row3 = $result3->fetch_array(MYSQLI_NUM);
				$sectionId = $row3[2];
				
				//Updating each selected student with the new class id
				$query4 = "INSERT INTO $resultTableName (studentId, academicYearId, termId, studentClassId, formTeacherId, studentYearGroupId, sectionId, houseId, sportActivityId) VALUES('{$studentId}', '{$currentResultYearId}', '{$currentResultTermId}', '{$classId}', '{$formTeacherId}', '{$yearGroupId}', '{$sectionId}', '{$houseId}', '{$sportId}')";
				$result4 = $connection->query($query4);
			}
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
?>