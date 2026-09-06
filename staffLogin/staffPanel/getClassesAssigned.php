<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$staffId = $_SESSION['staffId'];
		
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
		
		$currentYearResultTableName = "";
		
		//Checking if resultTable name has been created for current result year
		$checkIfResultTableCreated = "SELECT * FROM results_table_names WHERE yearId='$currentResultYearId'";
		$result3 = $connection->query($checkIfResultTableCreated);
		if (($result3->num_rows)>0)	//If a result table name has been created for current result year
		{
			$row3 = $result3->fetch_array(MYSQLI_NUM);
			$currentYearResultTableName = $row3[2];
			
			//Checking the form teacher assignmnent table to see if there are more than one or more classes assigned to this form teacher.
			$getClassesAsFormTeacher = "SELECT * FROM form_teacher_assignment WHERE teacherId='$staffId'";
			$result3 = $connection->query($getClassesAsFormTeacher);
			if (($result3->num_rows)>0)	//If there is/are one or more classes assigned to this form master
			{
				while($row3 = $result3->fetch_array(MYSQLI_NUM))
				{
					$classId = $row3[3];
					//Getting the class name
					$getClassName = "SELECT * FROM classes WHERE classId='$classId'";
					$result4 = $connection->query($getClassName);
					$row4 = $result4->fetch_array(MYSQLI_NUM);
					$classYearGroupId = $row4[1];
					$className = $row4[2];
										
					//Getting the class name
					$getYearGroupName = "SELECT * FROM yeargroup WHERE yearGroupId='$classYearGroupId'";
					$result5 = $connection->query($getYearGroupName);
					$row5 = $result5->fetch_array(MYSQLI_NUM);
					$yearGroupName = $row5[1];
												
					//Checking if this staff has entered results of students for this class and for this year and term
					$checkForResultsInResultTable = "SELECT * FROM $currentYearResultTableName WHERE formTeacherId='$staffId' AND studentClassId='$classId' AND academicYearId='$currentResultYearId' AND termId='$currentResultTermId'";
					$result6 = $connection->query($checkForResultsInResultTable);
												
					if(($result6->num_rows) > 0)	//If students of this class have been added to result table name for the current year and term by this form master
					{
						$flagIfCreatedResultTableName = 1;
						$flagIfStudentsExistInCreatedResultTableName = 1;
						$return_arr[] = array("flagIfCreatedResultTableName" => $flagIfCreatedResultTableName,
											   "flagIfStudentsExistInCreatedResultTableName" => $flagIfStudentsExistInCreatedResultTableName,
											   "classId" => $classId,
											   "className" => $className,
											   "classYearGroupId" => $classYearGroupId,
											   "yearGroupName" => $yearGroupName);
					}
					else
					{
						$flagIfCreatedResultTableName = 1;
						$flagIfStudentsExistInCreatedResultTableName = 0;
						$return_arr[] = array("flagIfCreatedResultTableName" => $flagIfCreatedResultTableName,
											   "flagIfStudentsExistInCreatedResultTableName" => $flagIfStudentsExistInCreatedResultTableName,
											   "classId" => $classId,
											   "className" => $className,
											   "classYearGroupId" => $classYearGroupId,
											   "yearGroupName" => $yearGroupName);
											   
					}													
				}
				echo json_encode($return_arr);
			}
		}
		else	//If no result table name has been created for current result year
		{
			$flagIfCreatedResultTableName = 0;
			$flagIfStudentsExistInCreatedResultTableName = 0;
			$return_arr[] = array("flagIfCreatedResultTableName" => $flagIfCreatedResultTableName,
									"flagIfStudentsExistInCreatedResultTableName" => $flagIfStudentsExistInCreatedResultTableName);
			
			echo json_encode($return_arr);
		}
?>
