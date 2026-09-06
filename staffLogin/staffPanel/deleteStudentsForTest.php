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
		require_once "../../db_connection/dlhs_db_connection.php";
	
		//post for change of password
		if(isset($_POST['checkedForDelete']))
		{    
			$checkedForDelete = mysqli_real_escape_string($connection, $_POST['checkedForDelete']);
			
			//exploding the array into elements based on the use of the comma
			$str_arr = explode (",", $checkedForDelete); 
			
			foreach($str_arr as $chk1)  
			{  
				//Splitting the each element into the two components of the testId and the examineeId.
				$splittedstring=explode("n",$chk1);
				$testId=$splittedstring[0];
				$examineeId=$splittedstring[1];
				$examineeUserId=$splittedstring[2];
				
				//getting the email of the student.
				$query3 = "SELECT * FROM studentlogin WHERE studentId='$examineeUserId'";
				$result3 = $connection->query($query3);
				$row3 = $result3->fetch_array(MYSQLI_NUM);
				$studentEmail = $row3[10];
				$studentName = $row3[2];
				
				
				//Getting the name of the table the selected examinees were saved.
				$query1 = "SELECT * FROM tests WHERE testId='$testId'";
				$result1 = $connection->query($query1);
				$row1 = $result1->fetch_array(MYSQLI_NUM);
				$examineeTestTableName=$row1[13];
				$testTime=$row1[5].":".$row1[6]." ".$row1[7];
				
				$date=$row1[3];
				$dayOfWeek = date("l", strtotime($date));
				$day = date("d", strtotime($date));
				$month= date("F", strtotime($date));
				$year=date("yy", strtotime($date));
				$testDate="$dayOfWeek $day $month, $year";
				
				$subjectId=$row1[8];
				$testName=$row1[2];
				
				//Getting the subject name
				$query4 = "SELECT * FROM subjects WHERE subjectId='$subjectId'";
				$result4 = $connection->query($query4);
				$row4 = $result4->fetch_array(MYSQLI_NUM);
				$subjectName=$row4[1];
				
				$query2 = "DELETE FROM $examineeTestTableName WHERE examineeId='$examineeId'";
				$result2 = $connection->query($query2);
				
				//Sending email to student for deleted test
					$to =$studentEmail;
					$subject = 'Cancelled Test';
					$from = 'Zamani College<kennykayock@gmail.com>';
					$replyTo='kennykayock@gmail.com';
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					// Create email headers
					$headers .= 'From: '.$from."\r\n".
						'Reply-To: '.$replyTo."\r\n" .
						'X-Mailer: PHP/' . phpversion();
					// Compose a simple HTML email message
					$message = '<html><body style="width: 70%; margin: 0 auto; padding-top: 25px; padding-right: 15px; padding-left: 15px; border-style:ridge; border-width:6px; border-color:blue; border-radius:6px;">';
					$message .= '<p><center><img src="https://schools.com.ng/staffLogin/staffPanel/img/sschoolNg.jpg" style="width:50%;" /></center></p>';
					$message .= '<h1>Hello '.$studentName.',</h1>';
					$message .= '<p style="font-size:18px;">Your <i>'.$subjectName.'</i> test <strong>'.$testName.'</strong> scheduled for '.$testTime.' '.$testDate.' has been cancelled.</p>';
					$message .= '<p style="font-size:18px;">We apologize for the inconvenience, and we will inform you of new plans as they become available</p>';
					$message .= '<p style="font-size:18px;"><b>Best regards: Schools NG.</b></p>';
					$message .= '</body></html>';

					// Sending email
					mail($to, $subject, $message, $headers);
				
			}
			echo 1;
		}
	}
?>