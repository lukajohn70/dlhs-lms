<?php
session_start();
	require_once 'userExpiredSession.php';
	
		include "../../db_connection/dlhs_db_connection.php";
		
		$testId = mysqli_real_escape_string($connection, $_POST['testIdToEdit']);
		$questionId = mysqli_real_escape_string($connection, $_POST['questionIdToEdit']);
		$frmAddQuestOrViewQuestStatus = mysqli_real_escape_string($connection, $_POST['frmAddQuestOrViewQuestStatus']);
		$staffId = $_SESSION['staffId'];
			
		//Getting the test name and question table name of the question
		$query="SELECT * FROM tests WHERE staffId='$staffId' AND testId='$testId'";
		$result = $connection->query($query);
		$row = $result->fetch_array(MYSQLI_NUM);
		$testName = $row[2];
		$questionsTableName = $row[13];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">

    <title>Add Question | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
	<link href="css/font-awesome.min.css" rel="stylesheet" />
	<link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
	<link href="fontAwesome/css/brands.css" rel="stylesheet">
	<link href="fontAwesome/css/solid.css" rel="stylesheet">
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>

    <!-- ck editor -->
    <script type="text/javascript" src="assets/ckeditor/ckeditor.js"></script>
	<script src="jQuery3.3.1.js"></script>
	<script>
		function isNumberKey(evt, element) {
			var charCode = (evt.which) ? evt.which : event.keyCode
			if (charCode > 31 && (charCode < 48 || charCode > 57) && !(charCode == 46 || charCode == 8))
				return false;
			else {
				var len = $(element).val().length;
				var index = $(element).val().indexOf('.');
				if (index > 0 && charCode == 46) {
				  return false;
				}
				if (index > 0) {
					var CharAfterdot = (len + 1) - index;
					if (CharAfterdot > 3) {
						return false;
					}
				}
			}
			return true;
		}
	</script>
	<style>
		.split-helper-card {
			margin-top: 12px;
			padding: 12px 15px;
			border-radius: 8px;
			border: 1px solid #ffe08a;
			background: #fff8e1;
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 10px;
		}
		.split-helper-card strong {
			color: #8a6100;
		}
		.option-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
			gap: 14px;
		}
		.option-field textarea {
			min-height: 190px;
		}
		.option-count-toggle {
			display: flex;
			flex-wrap: wrap;
			gap: 14px;
			margin: 8px 0 4px;
		}
	</style>
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBarViewQuestions.php'; ?>

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add Question</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Add Question</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								Edit Question of selected Test 
							</header>
							<div class="panel-body">
									<div class="message1" style="color:green; font-size:17px;" align="center"></div>
									<div class="form-group">
										<div class="col-sm-12">
											<label style="font-size:18px">Test with question to be edited: <strong><font><i><?php echo $testName; ?></i></font></strong></label>
										</div>
									</div>
								
								<form class="form-horizontal" name="myForm" >	
									<div class="form-group">
                                        <center><label><strong>Question:</strong></label></center>
                                        <div class="col-sm-12">
											<input type="hidden" name="testIdToEdit" id="testIdToEdit" />
											<input type="hidden" name="questionIdToEdit" id="questionIdToEdit" />
											<textarea class="form-control ckeditor" name="question" id="question"  rows="6"></textarea>
                                        </div>
										<div class="col-sm-12">
											<div class="split-helper-card" id="splitQuestionHelper">
												<strong><i class="fa fa-magic"></i> Pasted the full question together with A-D?</strong>
												<button type="button" class="btn btn-danger btn-lg" onclick="splitPastedQuestionIntoOptions()"><i class="fa fa-columns"></i> Split pasted question into options</button>
												<span>This moves A-D into the proper option boxes automatically.</span>
											</div>
											<div class="split-helper-card" id="splitQuestionDetected" style="display:none; border-color:#b7eb8f; background:#f6ffed;">
												<strong style="color:#237804;"><i class="fa fa-check-circle"></i> Options detected in the question box.</strong>
												<button type="button" class="btn btn-success btn-lg" onclick="splitPastedQuestionIntoOptions()"><i class="fa fa-bolt"></i> Split now</button>
												<span>The system noticed A-D options inside the question editor.</span>
											</div>
										</div>
                                    </div>
									<div class="form-group">
										<div class="col-sm-12">
											<label><strong>How many options?</strong></label>
											<div class="option-count-toggle">
												<label class="radio-inline"><input type="radio" name="optionCount" id="optionCount4" value="4" checked onchange="setOptionCount(4)"> 4 options (A-D)</label>
												<label class="radio-inline"><input type="radio" name="optionCount" id="optionCount5" value="5" onchange="setOptionCount(5)"> 5 options (A-E)</label>
											</div>
										</div>
									</div>
									<div class="form-group">
                                        <div class="col-sm-12 option-grid">
											<div class="option-field">
												<center><label><strong>Enter option A:</strong></label></center>
												<textarea class="form-control ckeditor" id="optionA" name="optionA" rows="6"></textarea>
											</div>
											<div class="option-field">
												<center><label><strong>Enter option B:</strong></label></center>
												<textarea class="form-control ckeditor" id="optionB" name="optionB" rows="6"></textarea>
											</div>
											<div class="option-field">
												<center><label><strong>Enter option C:</strong></label></center>
												<textarea class="form-control ckeditor" id="optionC" name="optionC" rows="6"></textarea>
											</div>
											<div class="option-field">
												<center><label><strong>Enter option D:</strong></label></center>
												<textarea class="form-control ckeditor" id="optionD" name="optionD" rows="6"></textarea>
											</div>
											<div class="option-field" id="optionEField" style="display:none;">
												<center><label><strong>Enter option E:</strong></label></center>
												<textarea class="form-control ckeditor" id="optionE" name="optionE" rows="6"></textarea>
											</div>
										</div>
                                    </div>
									<div class="form-group">
										<div class="col-sm-6">
											<strong>Select correct option:</strong><br>
										
											<input type="radio" name="options" id="A" value="A"> Option A<br>
											<input type="radio" name="options" id="B" value="B"> Option B<br>
											<input type="radio" name="options" id="C" value="C"> Option C<br>
											<input type="radio" name="options" id="D" value="D"> Option D<br>
											<span id="optionESelector" style="display:none;"><input type="radio" name="options" id="E" value="E"> Option E<br></span><br>
											Mark for question: <input type="text" name="mark" id="mark" class="form-control" placeholder="Please enter mark for this question" onkeypress="return isNumberKey(event,this)"><br>
											<label><strong>Shared material for this question:</strong></label>
											<select class="form-control" id="sharedStimulusId" name="sharedStimulusId" style="margin-bottom:10px;">
												<option value="0">No shared material</option>
											</select>
											<div class="help-block">If you need to create or edit the shared material itself, return to the add-question page for this test.</div>
											<button type="button" class="btn btn-primary " onclick="updateQuestion()" style="margin-left:20px;"><i class="fa fa-upload"></i> <b>Update question</b></button><div class="message2" id="message2" style="color:red; font-size:18px;" align="center"></div>
										</div>
									</div>
								</form>
                            </div>
						</section>
					</div>
				</div>				
                </div>
              </div>
              <!-- page end-->
          </section>
      </section>
      <!--main content end-->
      <div class="text-right">
        <div class="credits">
            <?php include "footer.php"; ?>
        </div>
    </div>
  </section>
  <!-- container section end -->
    <!-- javascripts -->
    //<script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- nice scroll -->
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>

    <!-- jquery ui -->
    <script src="js/jquery-ui-1.9.2.custom.min.js"></script>

    <!--custom checkbox & radio-->
    <script type="text/javascript" src="js/ga.js"></script>
    <!--custom switch-->
    <script src="js/bootstrap-switch.js"></script>
    <!--custom tagsinput-->
    <script src="js/jquery.tagsinput.js"></script>
    <!-- custome script for all page -->
	<script src="js/scripts.js"></script>
	<script src="js/question-authoring.js"></script>
	<script>
		var selectedOptionCount = 4;

		function setOptionCount(count)
		{
			selectedOptionCount = (count === 5) ? 5 : 4;
			$('#optionCount4').prop('checked', selectedOptionCount === 4);
			$('#optionCount5').prop('checked', selectedOptionCount === 5);
			$('#optionEField').toggle(selectedOptionCount === 5);
			$('#optionESelector').toggle(selectedOptionCount === 5);
			if (selectedOptionCount === 4 && document.getElementById('E').checked) {
				document.getElementById('E').checked = false;
			}
		}

		DLHSQuestionAuthoring.createEditor('question', 'uploadEditorImage.php', { height: 300 });
		DLHSQuestionAuthoring.createEditor('optionA', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionB', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionC', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionD', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionE', 'uploadEditorImage.php', { height: 220 });

		function loadSharedStimuli(testId, selectedStimulusId)
		{
			$.ajax({
				url: 'getSharedQuestionStimuli.php',
				type: 'POST',
				dataType: 'json',
				data: {testId: testId},
				success: function(response) {
					var options = '<option value="0">No shared material</option>';
					if (response && response.success && response.stimuli) {
						response.stimuli.forEach(function(stimulus) {
							var selected = String(selectedStimulusId || 0) === String(stimulus.stimulusId) ? ' selected' : '';
							options += '<option value="' + stimulus.stimulusId + '"' + selected + '>' + DLHSQuestionAuthoring.escapeHtml(stimulus.stimulusLabel) + '</option>';
						});
					}
					$('#sharedStimulusId').html(options);
				}
			});
		}

		function splitPastedQuestionIntoOptions()
		{
			var parsed = DLHSQuestionAuthoring.parseQuestionBlock(CKEDITOR.instances.question.getData());
			if (!parsed) {
				$('.message2').html('<i class="fa fa-info-circle"></i> No A-E option pattern was found in the question box.');
				return;
			}

			CKEDITOR.instances.question.setData(parsed.questionHtml);
			CKEDITOR.instances.optionA.setData(parsed.optionA);
			CKEDITOR.instances.optionB.setData(parsed.optionB);
			CKEDITOR.instances.optionC.setData(parsed.optionC);
			CKEDITOR.instances.optionD.setData(parsed.optionD);
			if (parsed.optionE && parsed.optionE.trim() !== '') {
				setOptionCount(5);
				CKEDITOR.instances.optionE.setData(parsed.optionE);
			} else {
				setOptionCount(selectedOptionCount);
				CKEDITOR.instances.optionE.setData('');
			}
			$('.message2').html('');
			$('.message1').html('<i class="fa fa-check"></i> Options were moved into the proper fields.').fadeIn('slow');
			updateSplitAssistVisibility();
		}

		function updateSplitAssistVisibility()
		{
			var detected = DLHSQuestionAuthoring.parseQuestionBlock(CKEDITOR.instances.question.getData());
			$('#splitQuestionDetected').toggle(!!detected);
		}
		
		//if submit button is clicked for updating an objective question.
		function updateQuestion()
		{       
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
				  	  
			let testId = $('input[name=testIdToEdit]').val();
			let questionId = $('input[name=questionIdToEdit]').val();
			let question = CKEDITOR.instances.question.getData().replaceAll('&nbsp;', '');
			let optionA = CKEDITOR.instances.optionA.getData().replaceAll('&nbsp;', '');
			let optionB = CKEDITOR.instances.optionB.getData().replaceAll('&nbsp;', '');
			let optionC = CKEDITOR.instances.optionC.getData().replaceAll('&nbsp;', '');
			let optionD = CKEDITOR.instances.optionD.getData().replaceAll('&nbsp;', '');
			let optionE = CKEDITOR.instances.optionE.getData().replaceAll('&nbsp;', '');
			let checkRadio = document.querySelector('input[name="options"]:checked'); 
			let mark = $('input[name=mark]').val();
			let sharedStimulusId = $('#sharedStimulusId').val() || 0;			
			let optionCount = ($('input[name="optionCount"]:checked').val() === '5') ? 5 : 4;
			selectedOptionCount = optionCount;
			
			if (question.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter question')
			}
			else if (optionA.length == 0)
			{
				$('.message2').html(' <i class="fa fa-info-circle"></i> Please enter option A')
			}
			else if (optionB.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option B')
			}
			else if (optionC.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option C')
			}
			else if (optionD.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option D')
			}
			else if (optionCount === 5 && optionE.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option E')
			}
			else if (checkRadio == null)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select correct answer')
			}
			else if (mark=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter mark for question')
			}
			else if (DLHSQuestionAuthoring.looksLikePlaceholder(optionA) ||
				DLHSQuestionAuthoring.looksLikePlaceholder(optionB) ||
				DLHSQuestionAuthoring.looksLikePlaceholder(optionC) ||
				DLHSQuestionAuthoring.looksLikePlaceholder(optionD) ||
				(optionCount === 5 && DLHSQuestionAuthoring.looksLikePlaceholder(optionE)))
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Enter the real option text in ' + (optionCount === 5 ? 'A-E' : 'A-D') + '. Only letters are no longer accepted.')
			}
			else
			{		
				let confirmIt = confirm("Are you sure you wish to submit the edit on this question?");
				if(confirmIt == true)
				{
					$.ajax({
						url: "editQuestion.php",
						type: "POST",       
						data: {testId:testId, questionId:questionId, question:question, optionA:optionA, optionB:optionB, optionC:optionC, optionD:optionD, optionE:optionCount === 5 ? optionE : '', optionCount:optionCount, selectedValue:checkRadio.value, mark:mark, sharedStimulusId:sharedStimulusId},
						success: function (html) {             								
							if (html==0)	//If session is expired.
							{                              
								window.location.replace("logout.php");
							}
							else if (html==1)	//If question successfully edited
							{                              
								$('.message1').html('<i class="fa fa-check"></i> Question successfully edited.').fadeIn('slow');
								document.getElementById("A").checked = false;
								document.getElementById("B").checked = false;
								document.getElementById("C").checked = false;
								document.getElementById("D").checked = false;
								if (document.getElementById("E")) { document.getElementById("E").checked = false; }
								CKEDITOR.instances.question.setData( '', function() {this.updateElement();})
								CKEDITOR.instances.optionA.setData( '', function() {this.updateElement();})
								CKEDITOR.instances.optionB.setData( '', function() {this.updateElement();})
								CKEDITOR.instances.optionC.setData( '', function() {this.updateElement();})
								CKEDITOR.instances.optionD.setData( '', function() {this.updateElement();})
								CKEDITOR.instances.optionE.setData( '', function() {this.updateElement();})
								document. getElementById("mark").value = "";
								let frmAddQuestOrViewQuestStatus = <?php echo $frmAddQuestOrViewQuestStatus; ?>;
								if(frmAddQuestOrViewQuestStatus == 1)
								{
									window.location.replace("addQuestionForm.php?editStatus=success");
								}
								else if(frmAddQuestOrViewQuestStatus == 2)
								{
									window.location.replace("viewQuestionForm.php?editStatus=success");
								}
								
							}
							else if (html==2)	//If edit is unsuccessful	
							{                              
								$('.message2').html('<i class="fa fa-times"></i> Could not edit Question. Please try again.').fadeIn('slow');
							}
							else if (html==3)
							{                              
								$('.message2').html('<i class="fa fa-times"></i> Oops! Something went wrong. Please contact Admin').fadeIn('slow');
							}
							else if (html==5)
							{
								$('.message2').html('<i class="fa fa-times"></i> Enter the real option text in A-E. Only letters are no longer accepted.').fadeIn('slow');
							}
						}
					});
				}
			}
		}
		//End of function updating an objective question

		$(document).ready(function(){
			setOptionCount(4);
			$.ajax({
					url: 'getTestQuestionBasedOnCriteria.php',
					type: 'POST',
					data: {testId:<?php echo $testId; ?>, questionId:<?php echo $questionId; ?>},
					dataType: 'JSON',
					success: function(response)
					{
						//alert(response)
						var len = response.length;
						for(var i=0; i<len; i++){
							let questionId = response[i].questionId;
							let question = response[i].question;
							let optionA = response[i].optionA;
							let optionB = response[i].optionB;
							let optionC = response[i].optionC;
							let optionD = response[i].optionD;
							let optionE = response[i].optionE || '';
							let correctOption = response[i].correctOption;
							let markForQuestion = response[i].markForQuestion;
							let solvedSolution = response[i].solvedSolution;
							let stimulusId = response[i].stimulusId || 0;
							
							
							//var theQuestion1 = theQuestion.replaceAll('\t', '')
							setOptionCount(optionE && optionE.trim() !== '' ? 5 : 4);
							CKEDITOR.instances['question'].setData(question);
							CKEDITOR.instances['optionA'].setData(optionA);
							CKEDITOR.instances['optionB'].setData(optionB);
							CKEDITOR.instances['optionC'].setData(optionC);
							CKEDITOR.instances['optionD'].setData(optionD);
							CKEDITOR.instances['optionE'].setData(optionE);
							let radioToBeCheckedId  = document.getElementById(correctOption);
							radioToBeCheckedId.checked = true;
							document.getElementById("mark").value = markForQuestion;
							document.getElementById("testIdToEdit").value = <?php echo $testId; ?>;
							document.getElementById("questionIdToEdit").value = <?php echo $questionId; ?>;
							loadSharedStimuli(<?php echo $testId; ?>, stimulusId);
							updateSplitAssistVisibility();


						}
				}
			});	
		});
		setTimeout(function() {
			if (CKEDITOR.instances.question) {
				CKEDITOR.instances.question.on('change', updateSplitAssistVisibility);
				CKEDITOR.instances.question.on('afterPaste', function() {
					setTimeout(updateSplitAssistVisibility, 200);
				});
			}
		}, 600);
	</script>


  </body>
</html>
