<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
	{	
		require_once 'dlhs_db_connection.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
		require_once "../../scripts/test_workflow_helper.php";
	
		//post for change of password
		if(isset($_POST['testName']))
		{    
			dlhsEnsureTestsCustomTypeColumn($connection);
			dlhsEnsureTestsMockPaperColumn($connection);
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$testDate = mysqli_real_escape_string($connection, $_POST['testDate']);
			$testDuration = mysqli_real_escape_string($connection, $_POST['testDuration']);
			$startHour = mysqli_real_escape_string($connection, isset($_POST['startHour']) && $_POST['startHour'] !== '' ? $_POST['startHour'] : '8');
			$startMinute = mysqli_real_escape_string($connection, isset($_POST['startMinute']) && $_POST['startMinute'] !== '' ? $_POST['startMinute'] : '0');
			$amOrPm = mysqli_real_escape_string($connection, isset($_POST['amOrPm']) && $_POST['amOrPm'] !== '' ? $_POST['amOrPm'] : 'AM');
			$subjectId = mysqli_real_escape_string($connection, $_POST['subjectId']);
			$yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
			$reviewOption = mysqli_real_escape_string($connection, $_POST['reviewOption']);
			$essayOption = mysqli_real_escape_string($connection, $_POST['essayOption']);
			$theEssayTime = mysqli_real_escape_string($connection, $_POST['theEssayTime']);
			$randomizeQuestions = mysqli_real_escape_string($connection, isset($_POST['randomizeQuestions']) ? $_POST['randomizeQuestions'] : '');
			$randomizeOptions = mysqli_real_escape_string($connection, isset($_POST['randomizeOptions']) ? $_POST['randomizeOptions'] : '');
			$testType = dlhsNormalizeTestType(isset($_POST['testType']) ? $_POST['testType'] : '');
			$customTestType = dlhsNormalizeCustomTestType(isset($_POST['customTestType']) ? $_POST['customTestType'] : '');
			$mockPaperLabel = dlhsNormalizeMockPaperLabel(isset($_POST['mockPaperLabel']) ? $_POST['mockPaperLabel'] : '');
			$academicSession = dlhsResolveAcademicSessionName($connection, isset($_POST['academicSession']) ? $_POST['academicSession'] : '');

			if ($testType === '')
			{
				echo 3;
				exit;
			}

			if ($academicSession === '')
			{
				echo 4;
				exit;
			}

			if ($testType === 'OTHER' && $customTestType === '')
			{
				echo 6;
				exit;
			}

			if ($testType === 'MOCK' && $mockPaperLabel === '')
			{
				echo 7;
				exit;
			}

			$generatedTestName = dlhsBuildGeneratedTestNameFromIds(
				$connection,
				$academicSession,
				(int) $yearGroupId,
				(int) $subjectId,
				$testType,
				$customTestType,
				$mockPaperLabel
			);
			if ($generatedTestName === '')
			{
				echo 5;
				exit;
			}
			$nameWithoutRand = preg_replace('/[^A-Za-z0-9]/', '', $generatedTestName);
			$generatedTestName = mysqli_real_escape_string($connection, $generatedTestName);
			$nameWithoutRand = mysqli_real_escape_string($connection, $nameWithoutRand);
			$customTestTypeEscaped = mysqli_real_escape_string($connection, $customTestType);
			$mockPaperLabelEscaped = mysqli_real_escape_string($connection, $mockPaperLabel);
			
			if($startHour==12 && $amOrPm=="AM")
			{
				$startHour=0;
			}
			if($startMinute==60)
			{
				$startMinute=00;
			}
			
			$query = "UPDATE tests SET testName='$generatedTestName', testDate='$testDate', duration='$testDuration', startHour='$startHour', startMinute='$startMinute', amOrPm='$amOrPm', subject='$subjectId', yearGroup='$yearGroupId', reviewOption='$reviewOption', essayOption='$essayOption', essayTime='$theEssayTime', randomizeQuestions='$randomizeQuestions', randomizeOptions='$randomizeOptions', academicSession='$academicSession', testType='$testType', customTestType='$customTestTypeEscaped', mockPaperLabel='$mockPaperLabelEscaped', nameWithoutRand='$nameWithoutRand' WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if ($result)
			{
				echo 1;
			}
			else
			{
				echo 2;
			}
		}
		else
		{
			echo 0;
		}
	}
?>
