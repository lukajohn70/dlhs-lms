<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	

    if(isset($_POST["selectedTestId"]))
	{
		// Capture selected year group
		$testId = $_POST["selectedTestId"];
		$classId = $_POST["selectedClassId"];
		$staffId=$_SESSION['staffId'];
		
		$return_arr = array();

		//Getting the test details (owned by this staff member)
		$getTestDetails="SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
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
				$studentId   = $row2['examineeUserId'];
				$testStatus  = $row2['testStatus'];
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
				
				// Count questions answered by this student
				if($testStatus == 1 || $testStatus == 2)
				{
					$questionsArray = unserialize($row2['questArray']);
					if($questionsArray !== false && is_array($questionsArray))
					{
						$totalQuestions = count($questionsArray);
						
						// Count how many questions the student has answered
						foreach($questionsArray as $questionId)
						{
							$checkAnswer = "SELECT * FROM `$answersTableName` WHERE userLoginId='$studentId' AND questionId='$questionId' AND selectedOption != '0'";
							$answerResult = $connection->query($checkAnswer);
							if($answerResult && $answerResult->num_rows > 0)
							{
								$questionsAnswered++;
							}
						}
					}
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
				
				$return_arr[] = array("testId"            => $testId,
									"studentId"           => $studentId,
									"surname"             => $surname,
									"firstName"           => $firstName,
									"middleName"          => $middleName,
									"studentClass"        => $className,
									"testDuration"        => $testDuration,
									"remainingTime"       => $remainingTime,
									"testStatus"          => $testStatus,
									"testStatusText"      => $testStatusText,
									"testAcademicYear"    => $academicYearName,
									"questionsAnswered"   => $questionsAnswered,
									"totalQuestions"      => $totalQuestions);
			}
		}
		echo json_encode($return_arr);
    }
?>
