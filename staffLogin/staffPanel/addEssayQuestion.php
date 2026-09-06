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
		require_once "../../scripts/test_workflow_helper.php";
	
		if(isset($_POST['testId']))
		{    
			$staffId = $_SESSION['staffId'];
			function dataready($data) 
			{
				$data = trim(preg_replace('/[\s\t\n\r\s]+/', '', $data));
				$data = stripslashes($data);
				$data = htmlspecialchars($data);
				return $data;
			} 
			
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$testRow = dlhsFetchTestRowById($connection, $testId);
			if (!$testRow)
			{
				echo 3;
				exit;
			}

			$questionEntryCheck = dlhsCheckQuestionEntryAllowed($connection, $testRow);
			if (!$questionEntryCheck['allowed'])
			{
				echo 4;
				exit;
			}

			// Store the editor HTML as-is (escaped for DB). Do not run htmlspecialchars here,
			// so staff preview and formatted views render correctly. Student-facing endpoints
			// continue to receive plain text (they strip tags).
			$essayQuestion = $connection->real_escape_string($_POST['essayQuestion']);
						
			$query = "SELECT * FROM essay_questions WHERE testId='$testId' AND staffId='$staffId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				$query1 = "UPDATE essay_questions SET question='$essayQuestion' WHERE testId='$testId' AND staffId='$staffId'";
				$result1 = $connection->query($query1);
				
				if ($result1)
				{	
					echo 2;
				}
				else
				{
					echo 3;
				}
			}
			else
			{
				$query1 = "INSERT INTO essay_questions(testId, staffId, question) VALUES('{$testId}', '{$staffId}', '{$essayQuestion}')";
				$result1 = $connection->query($query1);
				
				if ($result1)
				{	
					echo 1;
				}
				else
				{
					echo 3;
				}
			}
		}
		else
		{
			echo 0;
		}
	}
?>
