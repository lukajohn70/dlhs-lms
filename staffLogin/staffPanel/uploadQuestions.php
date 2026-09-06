<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		header('location:../index.php');
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
		require_once "../../scripts/test_workflow_helper.php";
		require_once "../../scripts/question_authoring_helper.php";

		if(isset($_POST['testName']))
		{
			$testId = $_POST['testName'];
			$qstring = "";

			$testRow = dlhsFetchTestRowById($connection, $testId);
			if (!$testRow) {
				header("location:addQuestionForm.php?status=err");
				exit;
			}

			$questionEntryCheck = dlhsCheckQuestionEntryAllowed($connection, $testRow);
			if (!$questionEntryCheck['allowed']) {
				header("location:addQuestionForm.php?status=students_required");
				exit;
			}
			
			// Allowed mime types
			$csvMimes = array(
				'text/x-comma-separated-values', 
				'text/comma-separated-values', 
				'application/octet-stream', 
				'application/vnd.ms-excel', 
				'application/x-csv', 
				'text/x-csv', 
				'text/csv', 
				'application/csv', 
				'application/excel', 
				'application/vnd.msexcel', 
				'text/plain'
			);
			
			$fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
			
			// Validate whether selected file is a CSV file
			if(!empty($_FILES['file']['name']) && (in_array($_FILES['file']['type'], $csvMimes) || strtolower($fileExtension) == 'csv'))
			{
				
				// If the file is uploaded
				if(is_uploaded_file($_FILES['file']['tmp_name']))
				{		
					//Getting the questions table name from the test table
					$getTestQuestionsTableName="SELECT * FROM tests WHERE testId='$testId'";
					$result = $connection->query($getTestQuestionsTableName);
					$row = $result->fetch_assoc();
					$questionsTableName = $row['tableName'];
						
					$questionsTableSql = dlhsEscapeIdentifier($questionsTableName);

					// Open uploaded CSV file with read-only mode
					$csvFile = fopen($_FILES['file']['tmp_name'], 'r');
					
					// Validate header columns
					$header = fgetcsv($csvFile);
					if (is_array($header)) {
						$header = array_map('dlhsNormalizeImportedText', $header);
					}
					if ($header === FALSE || count($header) < 8) {
						fclose($csvFile);
						$qstring = '?status=missing_columns';
						header("location:addQuestionForm.php".$qstring);
						exit;
					}
					
					$holdUpdateSuccess=0;
					$holdNewInsertionSuccess=0;
					$invalidPlaceholderOptions = false;
					
					// Parse data from CSV file line by line
					while(($line = fgetcsv($csvFile)) !== FALSE)
					{
						// Skip completely empty rows
						if ($line === NULL) {
							continue;
						}
						// Ensure indexes 0..7 exist to avoid undefined key notices
						for ($i = 0; $i <= 7; $i++) {
							if (!isset($line[$i])) {
								$line[$i] = '';
							}
							$line[$i] = dlhsNormalizeImportedText($line[$i]);
						}
						// Get row data
						$questionSerialNo = mysqli_real_escape_string($connection, (string)$line[0]);
						$question = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$line[1]));
						$optionA = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$line[2]));
						$optionB = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$line[3]));
						$optionC = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$line[4]));
						$optionD = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$line[5]));
            $correctOption = trim(strtoupper((string)$line[6]));
						$markRaw = trim((string)($line[7]));
						$markForQuestion = mysqli_real_escape_string($connection, $markRaw === '' ? '1' : $markRaw);
						// Skip row if required fields are missing
						if ($questionSerialNo === '' || $question === '' || $correctOption === '') {
							continue;
						}

						if (dlhsOptionLooksLikePlaceholder($optionA) ||
							dlhsOptionLooksLikePlaceholder($optionB) ||
							dlhsOptionLooksLikePlaceholder($optionC) ||
							dlhsOptionLooksLikePlaceholder($optionD)) {
							$invalidPlaceholderOptions = true;
							break;
						}
												
						//Checking if this record has already been uploaded before
						$checkIfAlreadyUploaded="SELECT * FROM {$questionsTableSql} WHERE questionSeriaNo='$questionSerialNo'";
						$result1 = $connection->query($checkIfAlreadyUploaded);
						if (($result1->num_rows)>0)	
						{
							$updateRecord="UPDATE {$questionsTableSql} SET question='$question', optionA='$optionA', optionB='$optionB', optionC='$optionC', optionD='$optionD', correctOption='$correctOption', markForQuestion='$markForQuestion' WHERE questionSeriaNo='$questionSerialNo'";
							$result2 = $connection->query($updateRecord);
							$updatedOrNotStatus=1;
							if($result2)
							{
								$holdUpdateSuccess = 1;
							}
						}
						else
						{
							// Insert questions data - including empty strings for optionE and solvedSolution to satisfy NOT NULL constraints
							$query2 = "INSERT INTO {$questionsTableSql}(question, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion, solvedSolution, questionSeriaNo) VALUES('{$question}', '{$optionA}', '{$optionB}', '{$optionC}', '{$optionD}', '', '{$correctOption}', '{$markForQuestion}', '', '{$questionSerialNo}')";
							$result2 = $connection->query($query2);
							if($result2)
							{
								$holdNewInsertionSuccess = 1;
							}
							else
							{
								// Log error for debugging if needed
								error_log("Insert failed: " . $connection->error);
							}
						}						
					}
					// Close opened CSV file
					fclose($csvFile);
					if ($invalidPlaceholderOptions) {
						$qstring = '?status=invalid_option_placeholders';
						header("location:addQuestionForm.php".$qstring);
						exit;
					}
					if(($holdUpdateSuccess == 1) && ($holdNewInsertionSuccess == 1))
					{
						$qstring = '?status=succ_update';
					}
					elseif(($holdUpdateSuccess == 0) && ($holdNewInsertionSuccess == 1))
					{
						$qstring = '?status=succ_insert';
					}
					elseif(($holdUpdateSuccess == 1) && ($holdNewInsertionSuccess == 0))
					{
						$qstring = '?status=succ_update';
					}
					elseif(($holdUpdateSuccess == 0) && ($holdNewInsertionSuccess == 0))
					{
						$qstring = '?status=err';
					}
				}
				else
				{
					$qstring = '?status=err';
				}
			}
			else
			{
				$qstring = '?status=invalid_file';
			}
			// Redirect to the addQuestionForm.php page with the status message
			header("location:addQuestionForm.php".$qstring);
		}		
	}
