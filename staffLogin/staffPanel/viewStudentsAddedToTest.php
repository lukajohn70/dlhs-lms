<?php
session_start();
	$staffId = isset($_SESSION['staffId']) ? $_SESSION['staffId'] : 0;
	session_write_close();

	include "../../db_connection/dlhs_db_connection.php";

    if(isset($_POST["selectedTestId"]))
	{
		// Capture selected year group
		$testId = mysqli_real_escape_string($connection, $_POST["selectedTestId"]);
		$classId = mysqli_real_escape_string($connection, $_POST["selectedClassId"]);
		
		$return_arr = array();

		// Getting the test details (owned by this staff member)
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

		// Getting the academic Year name
		$academicYearName = '';
		if (!empty($academicYearId)) {
			$getAcademicYearName="SELECT academicYearName FROM academic_year WHERE academicYearId='$academicYearId' LIMIT 1";
			$result1 = $connection->query($getAcademicYearName);
			if ($result1 && $row1 = $result1->fetch_assoc()) {
				$academicYearName = $row1['academicYearName'];
			}
		}

		// Getting the class name ONCE before the loop
		$className = '';
		if (!empty($classId)) {
			$getClassName="SELECT className FROM classes WHERE classId='$classId' LIMIT 1";
			$result4 = $connection->query($getClassName);
			if ($result4 && $row4 = $result4->fetch_assoc()) {
				$className = $row4['className'];
			}
		}

		// Pre-aggregate answered question counts for all students in ONE single query
		$answeredCounts = array();
		if (!empty($answersTableName)) {
			$tableCheck = $connection->query("SHOW TABLES LIKE '$answersTableName'");
			if ($tableCheck && $tableCheck->num_rows > 0) {
				$ansRes = $connection->query("SELECT userLoginId, COUNT(*) as cnt 
				                              FROM `$answersTableName` 
				                              WHERE selectedOption != '0' 
				                              GROUP BY userLoginId");
				if ($ansRes) {
					while ($ar = $ansRes->fetch_assoc()) {
						$answeredCounts[$ar['userLoginId']] = (int)$ar['cnt'];
					}
				}
			}
		}

		// Getting the records of students already added for this selected test WITH student biodata joined
		$getStudentsAlreadyAdded="SELECT e.*, st.surname, st.firstName, st.middleName 
		                          FROM `$tableNameOfToBeTested` e
		                          LEFT JOIN studentlogin st ON e.examineeUserId = st.studentId
		                          WHERE e.studentClassId='$classId'";
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
				
				// Count questions answered by this student from pre-aggregated map
				if($testStatus == 1 || $testStatus == 2)
				{
					$questionsArray = @unserialize($row2['questArray']);
					if($questionsArray !== false && is_array($questionsArray))
					{
						$totalQuestions = count($questionsArray);
					}
					$questionsAnswered = isset($answeredCounts[$studentId]) ? $answeredCounts[$studentId] : 0;
				}
				
				if($testStatus == 1 || $testStatus == 2)
				{
					$remainingTime = round((($row2['remainingTime'])/60), 2);
				}
				else
				{
					$remainingTime = $testDuration;
				}

				$surname    = $row2['surname'] ?? '';
				$firstName  = $row2['firstName'] ?? '';
				$middleName = $row2['middleName'] ?? '';
				
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
