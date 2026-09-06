<?php
session_start();
	require_once __DIR__ . '/sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
	{ 
		require_once __DIR__ . '/../../db_connection/dlhs_db_connection.php';
		echo 0;
	}
	else
	{
		require_once __DIR__ . '/../../db_connection/dlhs_db_connection.php';
		require_once __DIR__ . '/../../scripts/test_workflow_helper.php';
		//post for change of password
		if(isset($_POST['testName']))
		{    
		dlhsEnsureTestsCustomTypeColumn($connection);
		dlhsEnsureTestsMockPaperColumn($connection);
		$testDate = mysqli_real_escape_string($connection, $_POST['testDate']);
		$testDuration = mysqli_real_escape_string($connection, $_POST['testDuration']);
		$startHour = mysqli_real_escape_string($connection, isset($_POST['startHour']) && $_POST['startHour'] !== '' ? $_POST['startHour'] : '8');
		$startMinute = mysqli_real_escape_string($connection, isset($_POST['startMinute']) && $_POST['startMinute'] !== '' ? $_POST['startMinute'] : '0');
		$amOrPm = mysqli_real_escape_string($connection, isset($_POST['amOrPm']) && $_POST['amOrPm'] !== '' ? $_POST['amOrPm'] : 'AM');
		$subject = mysqli_real_escape_string($connection, $_POST['subject']);
		$yearGroup = mysqli_real_escape_string($connection, $_POST['yearGroup']);
		$classId = isset($_POST['classId']) ? (int) $_POST['classId'] : 0;
		// Resolve the class arm name for use in the generated test name
		$classArmName = '';
		if ($classId > 0) {
			$classRow = $connection->query("SELECT className FROM classes WHERE classId='{$classId}' LIMIT 1");
			if ($classRow && $classRow->num_rows > 0) {
				$classArmName = $classRow->fetch_assoc()['className'];
			}
		} elseif (isset($_POST['classId']) && $_POST['classId'] === 'all') {
			$classArmName = 'ALL';
			$classId = 0;
		}
		$reviewOption = mysqli_real_escape_string($connection, $_POST['reviewOption']);
		$essayOption = mysqli_real_escape_string($connection, $_POST['essayOption']);
		$theEssayTime = mysqli_real_escape_string($connection, $_POST['theEssayTime']);
		$randomizeQuestions = mysqli_real_escape_string($connection, $_POST['randomizeQuestions']);
		$randomizeOptions = mysqli_real_escape_string($connection, $_POST['randomizeOptions']);
		$testType = dlhsNormalizeTestType(isset($_POST['testType']) ? $_POST['testType'] : '');
		$customTestType = dlhsNormalizeCustomTestType(isset($_POST['customTestType']) ? $_POST['customTestType'] : '');
		$mockPaperLabel = dlhsNormalizeMockPaperLabel(isset($_POST['mockPaperLabel']) ? $_POST['mockPaperLabel'] : '');
		$academicSession = dlhsResolveAcademicSessionName($connection, isset($_POST['academicSession']) ? $_POST['academicSession'] : '');
		$status=0;
		$staffId=$_SESSION['staffId'];

			if ($testType === '')
			{
				echo json_encode(array('status' => 5));
				exit;
			}

			if ($academicSession === '')
			{
				echo json_encode(array('status' => 6));
				exit;
			}

			if ($testType === 'OTHER' && $customTestType === '')
			{
				echo json_encode(array('status' => 8));
				exit;
			}

			if ($testType === 'MOCK' && $mockPaperLabel === '')
			{
				echo json_encode(array('status' => 9));
				exit;
			}

			$generatedTestName = dlhsBuildGeneratedTestNameFromIds(
				$connection,
				$academicSession,
				(int) $yearGroup,
				(int) $subject,
				$testType,
				$customTestType,
				$mockPaperLabel
			);
			// Append the arm name so each arm gets a unique test name
			if ($classArmName !== '') {
				$generatedTestName = trim($generatedTestName . ' ' . strtoupper($classArmName));
			}
			if ($generatedTestName === '')
			{
				echo json_encode(array('status' => 7));
				exit;
			}
			$generatedTestName = mysqli_real_escape_string($connection, $generatedTestName);
			$customTestTypeEscaped = mysqli_real_escape_string($connection, $customTestType);
			$mockPaperLabelEscaped = mysqli_real_escape_string($connection, $mockPaperLabel);
			$testNameToInsertInTable = htmlspecialchars($generatedTestName, ENT_QUOTES);
			
			$tableName1 = preg_replace('/[^A-Za-z0-9]/', '', $generatedTestName); // Removes special chars.
			// Truncate prefix to ensure total table name (including suffixes and rand) stays well under MySQL's 64-char limit
			$tableName1 = substr($tableName1, 0, 40);

			$tableName = strtolower($tableName1."Q".substr(uniqid(), -4));
			$examineeTableName = strtolower($tableName1."T".substr(uniqid(), -4));
			$answersTableName = strtolower($tableName1."A".substr(uniqid(), -4));
			
			if($startHour==12 && $amOrPm=="AM")
			{
				$startHour=0;
			}
			if($startMinute==60)
			{
				$startMinute=00;
			}
			
			$query = "SELECT * FROM tests WHERE nameWithoutRand='$tableName1'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo json_encode(array('status' => 4));
			}
			else
			{
				
				//Creating the table for a test questions using the name of the test as the name of the table.
				$query1 = "CREATE TABLE IF NOT EXISTS `$tableName` (
							questionId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
							question TEXT NOT NULL,
							optionA TEXT NOT NULL,
							optionB TEXT NOT NULL,
							optionC TEXT NOT NULL,
							optionD TEXT NOT NULL,
							optionE TEXT NOT NULL,
							correctOption VARCHAR(10) NOT NULL,
							markForQuestion FLOAT NOT NULL,
							solvedSolution TEXT NOT NULL,
							questionSeriaNo INT NOT NULL
						)ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
				$result1 = $connection->query($query1);
				
				//Creating the table for examinees of a test.
				$query3 = "CREATE TABLE IF NOT EXISTS `$examineeTableName` (
							examineeId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
							testId INT NOT NULL,
							examineeUserId INT NOT NULL,
							studentClassId INT NOT NULL,
							studentYearGroupId INT NOT NULL,
							testStatus INT NOT NULL,
							questArray TEXT NOT NULL, 
							remainingTime INT NOT NULL DEFAULT 0,
							totalToBeEarned DECIMAL NOT NULL DEFAULT 0,
							totalEarned DECIMAL NOT NULL DEFAULT 0,
							noOfQuestions INT NOT NULL DEFAULT 0,
							noCorrect INT NOT NULL DEFAULT 0,
							timeStartedTest VARCHAR(10) NOT NULL DEFAULT '',
							timeSubmittedTest VARCHAR(10) NOT NULL DEFAULT '',
							attendance INT(1) NOT NULL DEFAULT 0,
							isStarted INT(1) NOT NULL DEFAULT 0,
							isPaused INT(1) NOT NULL DEFAULT 0
						)ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
				$result3 = $connection->query($query3);
				
				//Creating the table for answers of a test.
				$query4 = "CREATE TABLE IF NOT EXISTS `$answersTableName` (
							testAnswerId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
							userLoginId INT NOT NULL,
							testId INT NOT NULL,
							questionId INT NOT NULL,
							selectedOption VARCHAR(10) NOT NULL,
							correctOption VARCHAR(10) NOT NULL,
							markForQuestion FLOAT NOT NULL
						)ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
				$result4 = $connection->query($query4);
				if (!$result4) {
					echo json_encode(array('status' => 2, 'message' => 'Failed to create examinees table: ' . $connection->error));
					exit;
				}
				
				//Getting the Academic year
				$academicYearIdToSelect = 1;
				$query5 = "SELECT * FROM set_academic_year WHERE id='$academicYearIdToSelect'";
				$result5 = $connection->query($query5);
				if (!$result5) die($connection->error);
				$row5 = $result5->fetch_array(MYSQLI_NUM);
				$academicYearId = $row5[1];
				
				$query2 = "INSERT INTO tests(staffId, testName, testDate, duration, startHour, startMinute, amOrPm, subject, yearGroup, classId, status, tableName, examineesTableName, nameWithoutRand, answersTable, academicYearId, reviewOption, essayOption, essayTime, randomizeQuestions, randomizeOptions, academicSession, testType, customTestType, mockPaperLabel) VALUES('{$staffId}', '{$testNameToInsertInTable}', '{$testDate}', '{$testDuration}', '{$startHour}', '{$startMinute}', '{$amOrPm}', '{$subject}', '{$yearGroup}', '{$classId}', '{$status}', '{$tableName}', '{$examineeTableName}', '{$tableName1}', '{$answersTableName}', '{$academicYearId}', '{$reviewOption}', '{$essayOption}', '{$theEssayTime}', '{$randomizeQuestions}', '{$randomizeOptions}', '{$academicSession}', '{$testType}', '{$customTestTypeEscaped}', '{$mockPaperLabelEscaped}');";
				$result2 = $connection->query($query2);
				
				
				if ($result2)
				{	
					echo json_encode(array(
						'status' => 1,
						'testId' => (int) $connection->insert_id
					));
				}
				else
				{
					echo json_encode(array('status' => 2));
				}
			}
		}
	}
?>
