<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
error_reporting(0);
	

    if(isset($_POST["selectedTestId"]))
	{
		$testId  = $_POST["selectedTestId"];
		$classId = $_POST["selectedClassId"];
		
		$return_arr = array();

		//Getting the test details
		$getTestDetails="SELECT * FROM tests WHERE testId='$testId'";
		$result = $connection->query($getTestDetails);
		if (!$result || ($result->num_rows) < 1)
		{
			echo json_encode($return_arr);
			exit;
		}

		$row = $result->fetch_assoc();
		$tableNameOfToBeTested = $row['examineesTableName'];
		$academicYearId        = $row['academicYearId'];
		$testDuration          = $row['duration'];
		$answersTableName      = $row['answersTable'];

		//getting the academic Year name
		$getAcademicYearName="SELECT * FROM academic_year WHERE academicYearId='$academicYearId'";
		$result1 = $connection->query($getAcademicYearName);
		$row1 = $result1 ? $result1->fetch_assoc() : null;
		$academicYearName = $row1 ? $row1['academicYearName'] : '';

		//Getting the records of students already added for this selected test
		$getStudentsAlreadyAdded="SELECT * FROM `$tableNameOfToBeTested` WHERE studentClassId='$classId'";
		$result2 = $connection->query($getStudentsAlreadyAdded);
		if ($result2 && ($result2->num_rows) > 0)
		{
			while($row2 = $result2->fetch_assoc())
			{	
				$studentId      = $row2['examineeUserId'];
				$testStatus     = $row2['testStatus'];
				$remainingTime  = "";
				$testStatusText = "";
				$questionsAnswered = 0;
				$totalQuestions    = 0;
				
				if($testStatus == 0)
				{
					$testStatusText="Yet to start";
				}
				elseif($testStatus == 1)
				{
					$testStatusText="In progress";
				}
				elseif($testStatus == 2)
				{
					$testStatusText="Completed";
				}
				
				if($testStatus == 1 || $testStatus == 2)
				{
					$remainingTime = round((($row2['remainingTime'])/60), 2);
				}
				else
				{
					$remainingTime = $testDuration;
				}

				//Getting the students' biodata
				$getStudentBiodata="SELECT * FROM studentlogin WHERE studentId='$studentId'";
				$result3 = $connection->query($getStudentBiodata);
				$row3 = $result3 ? $result3->fetch_assoc() : null;
				$surname    = $row3 ? $row3['surname']    : '';
				$firstName  = $row3 ? $row3['firstName']  : '';
				$middleName = $row3 ? $row3['middleName'] : '';
				
				//Getting the class name
				$getClassName="SELECT * FROM classes WHERE classId='$classId'";
				$result4 = $connection->query($getClassName);
				$row4 = $result4 ? $result4->fetch_assoc() : null;
				$className = $row4 ? $row4['className'] : '';
				
				$return_arr[] = array("testId"          => $testId,
									"studentId"         => $studentId,
									"surname"           => $surname,
									"firstName"         => $firstName,
									"middleName"        => $middleName,
									"studentClass"      => $className,
									"testDuration"      => $testDuration,
									"remainingTime"     => $remainingTime,
									"testStatus"        => $testStatus,
									"testStatusText"    => $testStatusText,
									"testAcademicYear"  => $academicYearName);
			}
		}
		echo json_encode($return_arr);
    }
?>
