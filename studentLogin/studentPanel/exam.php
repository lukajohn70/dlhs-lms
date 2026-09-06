<?php
session_start();
	if (!isset($_SESSION['studentLast_login']))
	{	
		header("location:logout.php");
		exit();
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
		require_once "../../track_test_activity.php";
		require_once "../../scripts/essay_timer_helper.php";
		unset($_SESSION['studentEmail']);
		unset($_SESSION['pages']);
		unset($_SESSION['setNext']);
		unset($_SESSION['questionsIdsArray']);
		unset($_SESSION['nextQuestIdToSelect']);
		unset($_SESSION['nextIndexId']);
		unset($_SESSION['prevIndexId']);
		unset($_SESSION['questTableName']);
		unset($_SESSION['totalQuestions']);
		unset($_SESSION['correctOption']);
			
		//date_default_timezone_set("Africa/Lagos");	//setting default time zone.
			
		//Fetching test details
	$testId = $_SESSION['idOfTest'];
	$studentId = $_SESSION['studentId'];
	
	// Get test info including randomization settings (use column names for reliability)
	$query = "SELECT *, 
	          COALESCE(randomizeQuestions, 'No') as randomizeQuestions,
	          COALESCE(randomizeOptions, 'No') as randomizeOptions 
	          FROM tests WHERE testId=?";
	$stmt = $connection->prepare($query);
	$stmt->bind_param("s", $testId);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();  // Use associative array for named columns
	
	if(!$row) {
		die("Test not found");
	}
	
	$testedTableName = $row['examineesTableName'];
	$questionsTableName = $row['tableName'];
	$answersTableName = $row['answersTable'];
	$randomizeQuestions = $row['randomizeQuestions'];  // Now using named column
		
$testTime = 0;
			
	//Checking test status of candidate
	$query1 = "SELECT * FROM $testedTableName WHERE testId=? AND examineeUserId=?";
	$stmt1 = $connection->prepare($query1);
	$stmt1->bind_param("ss", $testId, $studentId);
	$stmt1->execute();
	$result1 = $stmt1->get_result();
	$row1 = $result1->fetch_array(MYSQLI_NUM);
	
	if(!$row1) {
		die("Test status not found for student");
	}

	$essayExists = dlhsTestHasEssay($connection, (int) $testId) || (isset($row['essayOption']) && $row['essayOption'] === 'Yes');
	$essaySubmitted = $essayExists ? dlhsHasSubmittedEssay($connection, (int) $testId, (int) $studentId) : false;
	
	if ($essayExists && !$essaySubmitted) {
		// Check if the essay timer has already expired as a fallback mitigation
		$essayMinutes = isset($row['essayTime']) ? (int) $row['essayTime'] : 0;
		if ($essayMinutes <= 0) {
			$essayMinutes = isset($row['duration']) ? (int) $row['duration'] : 0;
		}
		
		if ($essayMinutes > 0) {
			$essayAttempt = dlhsGetOrCreateEssayAttempt($connection, $testId, $studentId, $essayMinutes);
			if ($essayAttempt && dlhsGetEssayRemainingSeconds($essayAttempt) <= 0) {
				// The essay timer has expired! Perform self-healing in the database.
				$createTableQuery = "CREATE TABLE IF NOT EXISTS essay_answers (
					answerId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
					testId INT NOT NULL,
					studentId INT NOT NULL,
					essayAnswer TEXT NOT NULL,
					submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					INDEX idx_test_student (testId, studentId)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8";
				$connection->query($createTableQuery);
				
				$checkQuery = "SELECT 1 FROM essay_answers WHERE testId='{$testId}' AND studentId='{$studentId}' LIMIT 1";
				$checkResult = $connection->query($checkQuery);
				if ($checkResult && $checkResult->num_rows === 0) {
					$insertQuery = "INSERT INTO essay_answers (testId, studentId, essayAnswer) VALUES ('{$testId}', '{$studentId}', 'Essay completed (Expired Fallback)')";
					$connection->query($insertQuery);
				}
				
				dlhsMarkEssaySubmitted($connection, $testId, $studentId);
				$essaySubmitted = true;
			}
		}
	}
	
	if ($essayExists && !$essaySubmitted && (int) $row1[5] == 0) {
		header("Location: viewEssay.php?testId=" . urlencode($testId));
		exit();
	}
	
	$arrayProgressButtonsIds = [];	//declaration of array to hold IDs of already answered questions.
	
	if($row1[5]==0)		//if exam has never been started before
	{
		$arrayQuestionsIds = [];							//Array to hold all the questions Ids
		
		//calculating the remaining time.
		$hourToStart = (int)$row['startHour'];
		$minuteToStart = (int)$row['startMinute'];
		$isAmOrPm = $row['amOrPm'];
		$secondToStart = 0;
		$dateToStart = $row['testDate'];
		$hourOf12 = 12;
		if(($isAmOrPm=="PM") && ($hourToStart > $hourOf12 || $hourToStart < $hourOf12))
		{
			$hourToStart = $hourToStart + 12;
		}
		$timeToStart = strtotime("$dateToStart $hourToStart:$minuteToStart:$secondToStart");
				
		$testTime = $row['duration'] * 60;
		
		$query2 = "SELECT * FROM $questionsTableName";
		$stmt2 = $connection->prepare($query2);
		$stmt2->execute();
		$result2 = $stmt2->get_result();
		$totalQuestions = $result2->num_rows;
		
		while($row2 = $result2->fetch_array(MYSQLI_NUM)) 	//Looping to put all the questionsIds into an array
		{	
			$arrayQuestionsIds[] = $row2[0];
		}
		
		// Apply randomization if enabled (SAFE: Only shuffles question IDs, grading uses questionId)
		if ($randomizeQuestions === 'Yes') {
			shuffle($arrayQuestionsIds);
		}
			
			$_SESSION['questionsIdsArray'] = $arrayQuestionsIds;
			$_SESSION['totalQuestions'] = $totalQuestions;
			$_SESSION['questTableName'] = $questionsTableName;
			$_SESSION['answersTableName'] = $answersTableName;
			$_SESSION['testedTableName'] = $testedTableName;
			
			//Saving the selected question Ids the questArrays.
			$arrayQuestionsIds1 = serialize($arrayQuestionsIds);
			$testTimeInSecs = $testTime;
			
			$timeStartedTest = date('h:i A');
			
			$query3 = "UPDATE $testedTableName SET questArray=?, remainingTime=?, timeStartedTest=? WHERE examineeUserId=?";
			$stmt3 = $connection->prepare($query3);
			$stmt3->bind_param("siss", $arrayQuestionsIds1, $testTimeInSecs, $timeStartedTest, $studentId);
			if(!$stmt3->execute()) {
				die("Failed to update test status: " . $connection->error);
			}
			
			//setting session of current time to track time covered in next or previous page
			$_SESSION['startTime'] = time();
			
			//updating the test status of candidate in the tested table
			$progressStatus = 1;
			$query4 = "UPDATE $testedTableName SET testStatus=? WHERE examineeUserId=?";
			$stmt4 = $connection->prepare($query4);
			$stmt4->bind_param("is", $progressStatus, $studentId);
			if(!$stmt4->execute()) {
				die("Failed to update test progress: " . $connection->error);
			}
		}
		else if($row1[5]==1)		//if test had been started, i.e., test in progress.
		{
			$arrayQuestionsIds = [];							//Array to hold all the questions Ids
			
			$query2 = "SELECT * FROM $questionsTableName";
			$stmt2 = $connection->prepare($query2);
			$stmt2->execute();
			$result2 = $stmt2->get_result();
			$totalQuestions = $result2->num_rows;
			
			$query3 = "SELECT * FROM $testedTableName WHERE testId=? AND examineeUserId=?";
			$stmt3 = $connection->prepare($query3);
			$stmt3->bind_param("ss", $testId, $studentId);
			$stmt3->execute();
			$result3 = $stmt3->get_result();
			$row3 = $result3->fetch_array(MYSQLI_NUM);
			$testTime = $row3[7];	//remaining time (in seconds) of this student
			
			$arrayAnsweredIds = [];	
			//Array to hold all the questions Ids of already answered questions from the answers table
			$testForZero = "0";
			$query5 = "SELECT * FROM $answersTableName WHERE userLoginId=? AND testId=? AND selectedOption !=?";
			$stmt5 = $connection->prepare($query5);
			$stmt5->bind_param("sss", $studentId, $testId, $testForZero);
			$stmt5->execute();
			$result5 = $stmt5->get_result();
			
			while($row5 = $result5->fetch_array(MYSQLI_NUM)) {
				$arrayAnsweredIds[] = $row5[3];
			}

			//setting session of current time to track time covered in next or previous page
			$_SESSION['startTime'] = time();
			
			$arrayQuestionsIds = unserialize($row1[6]);
			
			// If questArray is missing or corrupted, rebuild from the questions table as a fallback
			if($arrayQuestionsIds === false || !is_array($arrayQuestionsIds) || count($arrayQuestionsIds) == 0) {
				$arrayQuestionsIds = array();
				$queryQ = "SELECT * FROM $questionsTableName";
				$stmtQ = $connection->prepare($queryQ);
				if($stmtQ) {
					$stmtQ->execute();
					$resQ = $stmtQ->get_result();
					while($rQ = $resQ->fetch_array(MYSQLI_NUM)) {
						$arrayQuestionsIds[] = $rQ[0];
					}
					$stmtQ->close();
					// Apply randomization if enabled and we don't have a previous ordering
					if ($randomizeQuestions === 'Yes') {
						shuffle($arrayQuestionsIds);
					}
					// Persist reconstructed array back to tested table so next loads work
					$serialized = serialize($arrayQuestionsIds);
					$upd = "UPDATE $testedTableName SET questArray=? WHERE testId=? AND examineeUserId=?";
					$ustmt = $connection->prepare($upd);
					if($ustmt) {
						$ustmt->bind_param('sis', $serialized, $testId, $studentId);
						@$ustmt->execute();
						$ustmt->close();
					}
				}
			}
			
			$noOfItemsInArrayAnsweredIds = count($arrayAnsweredIds);
			for($i = 0; $i < $noOfItemsInArrayAnsweredIds; $i++) {
				$questIdOfAnswered = $arrayAnsweredIds[$i];
				$theKey = array_search($questIdOfAnswered, $arrayQuestionsIds);
				if($theKey !== false) {
					$arrayProgressButtonsIds[] = $theKey;
				}
			}
			
			$_SESSION['questionsIdsArray'] = $arrayQuestionsIds;
			$_SESSION['totalQuestions'] = $totalQuestions;
			$_SESSION['questTableName'] = $questionsTableName;
			$_SESSION['answersTableName'] = $answersTableName;
			$_SESSION['testedTableName'] = $testedTableName;
		}

		// Redirect to exam_all.php after setting up all required session variables
		header("Location: exam_all.php");
		exit();
	}
	
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>DLHS | Online Quiz</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images1/icons/favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor1/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor1/animate/animate.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor1/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css1/util.css">
	<link rel="stylesheet" type="text/css" href="css1/main.css">
	<style>
		.modal1, .modal2 {
		  display: none; /* Hidden by default */
		  position: fixed; /* Stay in place */
		  z-index: 10005; /* Sit on top of everything, including sticky button */
		  padding-top: 100px; /* Location of the box */
		  left: 0;
		  top: 0;
		  width: 80%; /* Full width */
		  height: 100%; /* Full height */
		  overflow: auto; /* Enable scroll if needed */
		  background-color: rgb(0,0,0); /* Fallback color */
		  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
		}

		.modal-content {
		border-radius:7px;
		  background-color: #fefefe;
		  margin: auto;
		  padding: 20px;
		  border: 1px solid #888;
		  width: 80%;
		  color:black;
		}

		/* The Close Button */
		.close1{
		  color: #aaaaaa;
		  float: right;
		  font-size: 28px;
		  font-weight: bold;
		}

		.close1:hover, .close1:focus {
		  color: #000;
		  text-decoration: none;
		  cursor: pointer;
		}
		ol.instructions li{
			padding-left:1em;
			padding-top:5px;
			margin-left:20px;
		}
		ol.courses li{
			padding-left:1em;
			margin-left:20px;
			padding-bottom:10px;
		}
		ul.course4 li{
			padding-left:1em;
			margin-left:20px;
			padding-bottom:10px;
			list-style-type:circle;
		}
		#myBtn1:hover {
			cursor: pointer;
		}

		/* Sticky Floating Submit Button */
		#submitTest {
			position: fixed;
			bottom: 24px;
			right: 24px;
			z-index: 9999;
			box-shadow: 0 8px 24px rgba(255, 0, 0, 0.4);
			border: 2px solid #fff;
			border-radius: 50px;
			padding: 10px 30px;
			font-size: 16px;
			font-weight: bold;
			background-color: #FF0000;
			color: #fff;
			cursor: pointer;
			transition: all 0.3s ease;
		}
		#submitTest:hover {
			background-color: #cc0000;
			box-shadow: 0 12px 30px rgba(255, 0, 0, 0.6);
			transform: translateY(-2px);
		}
	</style>
	<script src="jQuery3.3.1.js"></script>
	
<!--===============================================================================================-->



<style>
	.theProgress:hover {
			cursor: pointer;
		}
</style>
</head>
<body onload="displayQuestion(<?php echo $testId; ?>, <?php echo $totalQuestions; ?>)">
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title">
					<span class="login100-form-title-1">
						<img src="img/dlhslogo2.jpg" style="border-radius:50%; border:2px solid #ffffff;" width="55" alt="DLHS logo"/> - Deeper Life High School, Kaduna 
					</span>
				</div>
				<div class="row">
					<div id="testName" style="padding-left:40px; font-size:18px" ></div>&nbsp; <span align="center" style="font-size:24px; background:#C9EAF3; padding:1px 10px 0px 10px; margin-left:40px; border-radius:0px 0px 7px 7px;" id="hms"></span> &nbsp; 
					<span style="padding-left:40px;">Welcome <strong><?php echo $_SESSION['studentName']; ?></strong></span>
					<hr style="border:1px solid #C0C0C0; width:100%; margin-left:40px; margin-right:40px;">
					<div id="theProgress" style="padding-left:40px;"></div>
					
				</div>
				<div class="row">
					<div class="col-lg-6">
						<br>
						<div class="messsage2" style="color:red; padding-left:40px;"></div>
						<form class="login100-form">
							<h5>QUESTION [ <font id="currentNumber"></font>/<font id="totalQuestions"></font> ]</h5>
							<hr>							
							<table border="0" cellpadding="4" width="100%" style="margin-left:3px;">
								<tr>
									<td width="100%" id="theQuestion"></td>
								</tr>
							</table>
							
						</form>
					</div>
					<div class="col-lg-6">
						<form class="login100-form1 validate-form">
							<h5>OPTIONS</h5><br>
							
							<table border="0" cellpadding="4" width="100%" style="margin-left:3px;">
								<tr>
									<td width="5%"><input type="radio" name="options" id="a" value="A" /></td>
									<td width="95%"><div id="optionA"></div></td>													
								</tr>
								<tr>
									<td width="5%"><input type="radio" name="options" id="b" value="B" /></td>
									<td width="95%"><font id="optionB"></font></td>													
								</tr>
								<tr>
									<td width="5%"><input type="radio" name="options" id="c" value="C"></td>
									<td width="95%"><font id="optionC"></font></td>													
								</tr>
								<tr>
									<td width="5%"><input type="radio" name="options" id="d" value="D"></td>
									<td width="95%"><font id="optionD"></font></td>													
								</tr>
							</table>
							<br><br>
							<button type="button" class="login100-form-btn" id="previous" onclick="clickedPrevious()"><i class="fa fa-chevron-left"></i> Previous</button> &nbsp;
							<button type="button" class="login100-form-btn" id="next" onclick="clickedNext()">Next <i class="fa fa-chevron-right"></i></button> &nbsp;
							<button type="button" class="login100-form-btn1" id="submitTest" onclick="submitTheTest()">Submit</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--Modal for submitting test-->
	<div id="myModal1" class="modal1">
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px;" class="bg-success"><center style="font-size:22px;"><i class="fa fa-warning" aria-hidden="true"></i> Please confirm</center></div>
            <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                <div class="form-group">
                    <label for="exampleFormControlInput1" style="color:#000000; margin-bottom:20px;"><center>Are you sure you wish to submit? Note that you will not be able to restart or continue this test after submitting.</center></label>
                    <center><button class="btn btn-primary" type="button" onclick="clickedSubmit()" style="padding:10px 40px 10px 40px;background-color:blue; width:200px; border-radius:5px; margin-bottom:20px;"><i class="fa fa-check" aria-hidden="true"></i> Yes</button></center>
					<center><button class="btn btn-primary" type="button" onclick="closeModal()" style="padding:10px 40px 10px 40px;background-color:red; width:200px; border-radius:5px; border-color:red;"><i class="fa fa-times" aria-hidden="true"></i> No</button></center>
                </div>
            </div>
			<br>
			<br>
			<hr>
		</div>	<!-- End of modal content-->
	</div>  <!--Mend of modal-->
	
	<!--Modal for checking if all questions had been answered-->
	<div id="myModal2" class="modal2">
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px;" class="bg-success"><center style="font-size:22px;"><i class="fa fa-warning" aria-hidden="true"></i> Important information</center></div>
            <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                <div class="form-group">
                    <label for="exampleFormControlInput1" style="color:#000000; margin-bottom:20px;"><center>Please note that you have to attempt all questions before submitting. Click <b>Ok</b> to continue test.</center></label>
                    <center><button class="btn btn-primary" type="button" onclick="continueTest()" style="padding:10px 40px 10px 40px;background-color:blue; width:200px; border-radius:5px; margin-bottom:20px;"><i class="fa fa-check" aria-hidden="true"></i> Ok</button></center>
                </div>
            </div>
			<br>
			<br>
			<hr>
		</div>	<!-- End of modal content-->
	</div>  <!--Mend of modal-->
	<script src="minimizeWindowAjax.js"></script>
	<script>
		function countDown(countDown)
		{
			
			
			var i = setInterval(function () {
			  
			  var b1 = document.getElementById('hms');
			  			  
			  if((countDown === 1) || (countDown < 1)) {
				clearInterval(i);
				clickedSubmit();
			  }
			  
			  countDown--;
			  
			  var hour=Math.floor(countDown/3600);
			  var min=Math.floor(countDown%3600/60);
			  var sec=Math.floor(countDown%3600%60);
			  
			  b1.innerHTML = (hour=hour<10?"0"+hour:hour) + " : " + (min=min<10?"0"+min:min) + " : " + (sec=sec<10?"0"+sec:sec);

			}, 1000);
		}
		var timeInSecs = <?php echo $testTime; ?>;
		countDown(timeInSecs);
		
	</script>
	
	<script src="displayQuestionAjax.js"></script>
	<script>
		//displaying the progress buttons according to the number of questions
		var i;
		var theTotal=<?php echo $totalQuestions; ?>;
		for (i = 0; i < theTotal; i++) { 
			$('#theProgress').append("<input type='button' id='p"+(i)+"' class='theProgress' onclick='gotToQuestion(this.value)' style='width:5%; font-size:14px; padding-right:20px; padding-left:15px; border-radius:5px; color:#ffffff; background-color:red;' value='"+(i+1)+"'>"+"&nbsp;");
		}
		
		var passedArray =  <?php echo json_encode($arrayProgressButtonsIds); ?>; 	
		// Display the array elements 
		for(var i = 0; i < passedArray.length; i++){ 
			var valueOfIndex=passedArray[i];
			$("#p"+valueOfIndex).css({'background':'green', 'color':'white'});
		}
		
		
		function submitTheTest()
		{	
			var selectedAnswer;	//getting value of checked option
			if($('input[name=options]:checked').length > 0)
			{
				$.ajax({
					url: "checkForAllAnswered.php",
					type: "GET",
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	//if all questions have been answered
						{                              
							var modal1 = document.getElementById("myModal1");
							modal1.style.display = "block";
						}
						else if (html==2)	//If there is/are still one/more question(s) yet to be answered
						{                              
							var modal2 = document.getElementById("myModal2");
							modal2.style.display = "block";
						}
					}					
				});
			}
			else
			{
				var modal2 = document.getElementById("myModal2");
				modal2.style.display = "block";
			}           
		}
		
		// Get the <span> element that closes the modal
		var span1 = document.getElementsByClassName("close1")[0];
		
		// When the user clicks on <span> (x), close the modal2
		span1.onclick = function() {
		  modal1.style.display = "none";
		}
		
		// When the user clicks anywhere outside of the modal, close it
		var modal1 = document.getElementById("myModal1");
		window.onclick = function(event) {
		  if (event.target == modal1) {
			modal1.style.display = "none";
			
		  }
		}
		
		function closeModal()
		{
			modal1.style.display = "none";
		}
		
		var span2 = document.getElementsByClassName("close1")[1];
		var modal2 = document.getElementById("myModal2");
		// When the user clicks on <span> (x), close the modal2
		span2.onclick = function() {
		  modal2.style.display = "none";
		}
		
		//Function declaration to close continue test modal
		function continueTest()
		{
			var modal2 = document.getElementById("myModal2");
			modal2.style.display = "none";
		}
	</script>
</body>
</html>
