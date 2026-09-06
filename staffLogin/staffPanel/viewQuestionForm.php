<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn']))
	{
		header('location:../index.php');
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
		
		if(!empty($_GET['editStatus']))
		{
			switch($_GET['editStatus']){
				case 'success':
					$statusType1 = 'alert-success';
					$statusMsg1 = '<i class="fa fa-check-circle"></i> Question successfully edited';
					break;
				default:
					$statusType1 = '';
					$statusMsg1 = '';
			}
		}
	}
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
<!-- rowReorder removed: <link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/> -->
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="../../libs/jquery.min.js"></script>
	 <!-- bootstrap-wysiwyg -->
<script src="js/jquery.hotkeys.js"></script>
    <script src="js/bootstrap-wysiwyg.js"></script>
    <script src="js/bootstrap-wysiwyg-custom.js"></script>
    <!-- ck editor -->
    <script type="text/javascript" src="assets/ckeditor/ckeditor.js"></script>
<!-- Duplicate jQuery removed: <script src="jQuery3.3.1.js"></script> -->
	<script>
		//function to accept only integer minutes.
		function isNumberKey(evt)
		{
			var charCode = (evt.which) ? evt.which : evt.keyCode;
			if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
				return false;
				return true;
		}
	</script>
	<style>
		.modal1 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
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
            width: 60%;
            color:black;
            }

            /* The Close Button */
            .close1{
            color: #ffffff;;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus {
            color: red;
            text-decoration: none;
            cursor: pointer;
            }
			
			body{
			overflow-x:hidden;
			overflow-y:auto;
			min-height:100vh;
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> View Test Questions</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>View Questions</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								view Questions to selected Test
							</header>
							<div class="panel-body">
								<form class="form-horizontal" action="viewQuestion.php?pageNumber=1" method="post">
									<!-- Display status message -->
									
									<?php if(!empty($statusMsg1)){ ?>
									<div class="form-group">
										<div class="col-sm-4">
											<div class="alert <?php echo $statusType1; ?>" style="font-size:16px;"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg1; ?></div>
										</div>
										<div class="col-sm-4">
										</div>
										<p><br>
									</div>
									<?php } ?>
										
									<div class="form-group">
										<label class="control-label col-sm-4"><strong>Select Test to View Questions:</strong></label>
										<div class="col-sm-8">
													<select class="form-control" name="testId" id="testId" required >
												<option value="">... Select Test ...</option>
												<?php
															if(isset($_SESSION['staffId'])){
																$staffId=$_SESSION['staffId'];
																$test="select * from tests WHERE staffId='$staffId'";
															}else{
																$test="select * from tests";
															}
													$result1 = $connection->query($test);
													while($row1 = $result1->fetch_array(MYSQLI_NUM)){
												?>
												<option value="<?php echo $row1[0]; ?>"><?php echo $row1[2]; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<br>
								</form>
                            </div>
						</section>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View questions of selected Test.&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <font id="toViewEssayQuestions" style="background-color:"></font>							
							</header>
							<div class="panel-body">
								<div class="message1" style="color:red; font-size:17px;" align="center"></div><div class="message2" style="color:green; font-size:17px;" align="center"></div><br>
								<div class="table-responsive">
									<div style="margin-bottom:10px;">
										<button type="button" class="btn btn-danger" id="btnDeleteSelected" onclick="deleteSelected()"><i class="fa fa-trash"></i> Delete selected</button>
										<button type="button" class="btn btn-danger" id="btnDeleteAll" style="margin-left:8px;" onclick="deleteAll()"><i class="fa fa-trash"></i> Delete all</button>
									</div>
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="3%"><center><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></center></th>
												<th width="3%"><center>S/NO</center></th>
												<th width="30%"><center>QUESTION</center></th>
												<th width="10%"><center>SHARED MATERIAL</center></th>
												<th width="10%"><center>OPTION A</center></th>
												<th width="10%"><center>OPTION B</center></th>
												<th width="10%"><center>OPTION C</center></th>
												<th width="10%"><center>OPTION D</center></th>
												<th width="10%"><center>OPTION E</center></th>
												<th width="7%"><center>ANSWER</center></th>
												<th width="6%"><center>MARK</center></th>
												<th width="12%"><center>ACTION</center></th>
											</tr>
										</thead>
										<tbody>
												
										</tbody>
									</table>
								</div>
								<!-- End of table-responsive -->
								
								<form method="post" action="editQuestionForm.php" id="submitToEditQuestionForm">
									<input type="hidden" name="testIdToEdit" id="testIdToEdit">
									<input type="hidden" name="questionIdToEdit" id="questionIdToEdit">
									<input type="hidden" name="frmAddQuestOrViewQuestStatus" id="frmAddQuestOrViewQuestStatus">
								</form>
                            </div>
						</section>
					</div>
				</div>
                </div>
              </div>
              <!-- page end-->
          </section>
		<div id="myModal1" class="modal1"> <!--Start of modal to preview essay question-->
			<div class="modal-content" style="max-width: 900px; border-top: 3px solid #e91e8c;">
				<span class="close1" onclick="closeEssayModal()" style="color: #fff;">&times;</span>
				
				<!-- Modern Essay Header -->
				<div style="padding: 2rem 1rem; background: linear-gradient(135deg, #0095d9 0%, #00a8e8 100%); color: #fff; margin: -20px -20px 20px -20px; border-radius: 7px 7px 0 0; box-shadow: 0 4px 24px rgba(0,168,232,0.3);">
					<center style="font-size:24px; font-weight: 700; letter-spacing: 1px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Essay Section Preview</center>
				</div>
				
				<!-- Modern Card Body -->
				<div style="padding: 1rem;">
					<h5 style="color: #0095d9; margin-bottom: 1rem;">
						<i class="fa fa-file-text"></i> Essay Question
					</h5>
					
					<div id="theQuestion" style="background: linear-gradient(to bottom, #f0f9ff 0%, #fafcff 100%); border: 2px solid #bae6fd; border-radius: 12px; padding: 2rem; min-height: 200px; font-size: 1.1rem; line-height: 1.8;"></div>
					
					<div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
						<button type="button" onclick="closeEssayModal()" style="background: linear-gradient(135deg, #e91e8c 0%, #f02d95 100%); color: #fff; border: none; border-radius: 8px; padding: 0.8rem 2rem; font-size: 1.1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(233,30,140,0.3); transition: all 0.3s;">
							<i class="fa fa-times"></i> Close Preview
						</button>
					</div>
				</div>
			</div>	<!-- End of modal content-->
		</div><!-- End of modal to preview essay question-->
		<!-- Delete confirmation modal for questions -->
		<div id="myModalDeleteQuestions" class="modal1" style="display:none; align-items:center; justify-content:center; padding-top:0; z-index:1000;">
			<div class="modal-content" style="width:100%; max-width:560px; box-shadow:0 10px 30px rgba(0,0,0,0.25);">
				<span class="close1" onclick="closeDeleteQuestionsModal()">&times;</span>
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#c9302c;"><center style="font-size:20px;">Confirm Delete Questions</center></div>
				<div style="background-color:#FDF2F2; padding:15px; margin:10px; border:1px solid #f5c6cb;">
					<div id="deleteQuestionsModalBody" style="color:#721c24;"></div>
					<input type="text" id="deleteQuestionsConfirmInput" class="form-control" placeholder="Type DELETE to confirm" onkeyup="onDeleteQuestionsInput()" style="margin:10px 0;" />
					<div id="deleteQuestionsError" style="color:#a94442; font-size:14px; margin-bottom:10px;"></div>
					<div id="deleteQuestionsMessage" style="color:#31708f; font-size:14px; margin-bottom:10px;"></div>
					<button type="button" id="confirmDeleteQuestionsBtn" class="btn btn-danger" onclick="confirmDeleteQuestions()" disabled>Confirm Delete</button>
					<button type="button" class="btn btn-default" onclick="closeDeleteQuestionsModal()" style="margin-left:8px;">Cancel</button>
				</div>
			</div>
		</div>
			<!--main content end-->
			<div class="text-right">
				<div class="credits">
					<?php include "footer.php"; ?>
				</div>
			</div>
		</section>
	</section>
	<!-- container section end -->
    <!-- javascripts -->
<!-- Duplicate jQuery removed: <script src="js/jquery.js"></script> -->
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
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
<script src="../../datatables/js/jquery.dataTables.min.js"></script>
<!-- rowReorder removed: <script src="../../datatables/js/dataTables.rowReorder.min.js"></script> -->
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script>
		//View questions based on change of test select
		$("#testId").on('change', function() {
			$('.message1').html('');
			$('.message2').html('');
		
			$('#example').DataTable().clear().destroy();
			
			var selectedTestId = $("#testId option:selected").val();
			if(selectedTestId == "")
			{
				$('#toViewEssayQuestions').html("");
			}
			
			$.ajax({
				url: 'getTestQuestion.php',
				type: 'POST',
				data: {testId : selectedTestId},
				dataType: 'JSON',
				success: function(response)
				{
					var len = response.length;
					for(var i=0; i<len; i++){
						var questionId = response[i].questionId;
						var question = response[i].question;
						var stimulusLabel = response[i].stimulusLabel || '';
						var optionA = response[i].optionA;
						var optionB = response[i].optionB;
						var optionC = response[i].optionC;						
						var optionD = response[i].optionD;												
						var optionE = response[i].optionE;												
						var correctOption = response[i].correctOption;										
						var markForQuestion = response[i].markForQuestion;										
						var solvedSolution = response[i].solvedSolution;										
						
							var tr_str = "<tr>" +
								"<td width='3%'><center><input type='checkbox' class='row-select' data-question-id='"+questionId+"'></center></td>" +
								"<td width='3%'><center>" + (i+1) + "</center></td>" +
							"<td width='30%'>" + question + "</td>" +
							"<td width='10%'><center>" + stimulusLabel + "</center></td>" +
							"<td width='10%'><center>" + optionA + "</center></td>" +
							"<td width='10%'><center>" + optionB + "</center></td>" +
							"<td width='10%'><center>" + optionC + "</center></td>" +
							"<td width='10%'><center>" + optionD + "</center></td>" +
							"<td width='10%'><center>" + (optionE || '') + "</center></td>" +
							"<td width='7%'><center>" + correctOption + "</center></td>" +
							"<td width='6%'><center>" + markForQuestion + "</center></td>" +
							"<td align='center' width='12%'><a onclick='beforeSubmitToEditTest(\""+selectedTestId+"\",\""+questionId+"\")' title='Edit this question' style='cursor:pointer;'><i class='fa fa-pencil-square-o' aria-hidden='true' style='color:blue;'></i></a> &nbsp; &nbsp;<a onClick='deleteQuestion(\""+selectedTestId+"\",\""+questionId+"\")' title='Delete this question' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
							
							"</tr>";
							$("#example tbody").append(tr_str);
					}
					$('#example').DataTable( {
						"paging":   true,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							{
								extend: 'copy',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'csv',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'excel',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'pdf',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'print',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							}
						],
						rowReorder: {
							selector: 'td:nth-child(2)'
						},
						"responsive": true
					});
					if(len > 0)
					{
						$('#toViewEssayQuestions').html("| &nbsp; &nbsp; &nbsp;<a onclick='previewObjectives(\""+selectedTestId+"\")' style='cursor:pointer; background-color:#0095d9; color:#ffffff; padding:5px 10px; border-radius:3px;'><i class='fa fa-eye'></i> Preview Objectives (Student View)</a>&nbsp; &nbsp; &nbsp;<a onclick='viewEssay(\""+selectedTestId+"\")' style='cursor:pointer; background-color:#e91e8c; color:#ffffff; padding:5px 10px; border-radius:3px;'><i class='fa fa-file-text'></i> View Essay</a>&nbsp; &nbsp; &nbsp;<a onclick='printSetQuestions(\""+selectedTestId+"\")' style='cursor:pointer; background-color:#28a745; color:#ffffff; padding:5px 10px; border-radius:3px;'><i class='fa fa-print'></i> Print/Export Set Questions</a>&nbsp; &nbsp;")
					}
					else
					{
						$('#toViewEssayQuestions').html("")
					}
				}
			});
		});
		//calling the data table function
		
		//Start of function to delete a Question
		function deleteQuestion(testId, questionId)
		{
			$('.message1').html('');
			$('.message2').html('');
			
			var confirmIt = confirm("Are you sure you wish to delete this question? This action cannot be undone.");
			if(confirmIt == true)
			{					  
				$.ajax({
					url: "deleteQuestion.php",
					type: "POST", 
					data: {testId:testId, questionId:questionId},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message2').html('<i class="fa fa-check"></i> Question successfully deleted.').fadeIn('slow');
							 refreshQuestionsTable(testId, questionId);
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							$('.message1').html('<i class="fa fa-times"></i> Could not delete question. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If deletion is unsuccessful	
						{                              
							alert("Cannot delete this question as one or more candidates have started or completed the test it belongs to.");
						}
					}
									
				});
			}
		}

		function toggleSelectAll(master){
			var checked = master.checked;
			$(".row-select").prop('checked', checked);
		}
		function collectSelectedIds(){
			var ids = [];
			$(".row-select:checked").each(function(){
				ids.push($(this).data('question-id'));
			});
			return ids;
		}
		function deleteSelected(){
			$('.message2').html('');
			var testId = document.getElementById('testId').value;
			if(testId == ""){ alert('Please select test first'); return; }
			var ids = collectSelectedIds();
			if(ids.length === 0){ alert('Please select at least one question'); return; }
			if(!confirm('Are you sure you want to delete the selected questions? This cannot be undone.')) return;
			$.ajax({
				url: 'deleteSelectedQuestions.php',
				type: 'POST',
				data: {testId: testId, questionIds: ids},
				success: function(html){
					if(html==0){ window.location.replace('logout.php'); }
					else if(html==1){ $('.message2').html('<i class="fa fa-check"></i> Selected questions deleted.').fadeIn('slow'); refreshQuestionsTable(testId, null); }
					else if(html==3){ alert('Cannot delete; some examinees have started or completed this test.'); }
					else { alert('Could not delete selected questions. Please try again.'); }
				}
			});
		}
		function deleteAll(){
			$('.message2').html('');
			var testId = document.getElementById('testId').value;
			if(testId == ""){ alert('Please select test first'); return; }
			QDEL_MODE = 'all';
			QDEL_IDS = [];
			openDeleteQuestionsModal(null);
		}
		var QDEL_MODE = null; var QDEL_IDS = []; var QDEL_CODE = null;
		function generateConfirmationCode(){ var c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', r=''; for(var i=0;i<8;i++){ r+=c.charAt(Math.floor(Math.random()*c.length)); } return r; }
		function openDeleteQuestionsModal(count){
			var msg = (QDEL_MODE === 'selected') ? ('You are about to permanently delete '+count+' selected question(s).') : 'You are about to permanently delete ALL questions for this test.';
			QDEL_CODE = generateConfirmationCode();
			var body = '<p style="margin:0 0 10px 0;">'+msg+'</p>'+
				'<div style="background-color:#fff3cd; border:1px solid #ffeaa7; border-radius:5px; padding:10px; margin-bottom:10px;">'+
				'<div style="color:#856404; font-weight:bold;">Security confirmation required</div>'+
				'<div>Type this code: <span style="color:#dc3545; font-family:monospace; padding:6px 10px; border:2px solid #dc3545; border-radius:4px; letter-spacing:2px;">'+QDEL_CODE+'</span></div>'+
				'</div>'+
				'<p style="color:#721c24; font-weight:bold; margin:0 0 10px 0;">This action cannot be undone.</p>';
			document.getElementById('deleteQuestionsModalBody').innerHTML = body;
			document.getElementById('deleteQuestionsConfirmInput').value = '';
			document.getElementById('deleteQuestionsError').innerHTML = '';
			document.getElementById('deleteQuestionsMessage').innerHTML = '';
			document.getElementById('confirmDeleteQuestionsBtn').disabled = true;
			document.getElementById('myModalDeleteQuestions').style.display = 'block';
		}
		function closeDeleteQuestionsModal(){ document.getElementById('myModalDeleteQuestions').style.display = 'none'; }
		function onDeleteQuestionsInput(){ var v=document.getElementById('deleteQuestionsConfirmInput').value.trim(); var ok=(QDEL_CODE && v===QDEL_CODE); document.getElementById('confirmDeleteQuestionsBtn').disabled=!ok; document.getElementById('deleteQuestionsError').innerHTML=''; }
		function confirmDeleteQuestions(){
			var testId = document.getElementById('testId').value;
			var v = document.getElementById('deleteQuestionsConfirmInput').value.trim();
			if(!(QDEL_CODE && v===QDEL_CODE)){ document.getElementById('deleteQuestionsError').innerHTML='Please type the confirmation code exactly as shown.'; return; }
			document.getElementById('deleteQuestionsMessage').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting, please wait...';
			document.getElementById('confirmDeleteQuestionsBtn').disabled = true;
			if(QDEL_MODE === 'selected'){
				$.ajax({ url: 'deleteSelectedQuestions.php', type: 'POST', data: {testId: testId, questionIds: QDEL_IDS}, success: function(html){
					if(html==0){ window.location.replace('logout.php'); }
					else if(html==1){ document.getElementById('deleteQuestionsMessage').innerHTML = '<i class="fa fa-check"></i> Selected questions deleted.'; setTimeout(function(){ closeDeleteQuestionsModal(); refreshQuestionsTable(testId, null); }, 1200); }
					else if(html==3){ document.getElementById('deleteQuestionsMessage').innerHTML = 'Cannot delete; some examinees have started or completed this test.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
					else { document.getElementById('deleteQuestionsMessage').innerHTML = 'Could not delete selected questions. Please try again.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
				}});
			}else{
				$.ajax({ url: 'deleteAllQuestions.php', type: 'POST', data: {testId: testId}, success: function(html){
					if(html==0){ window.location.replace('logout.php'); }
					else if(html==1){ document.getElementById('deleteQuestionsMessage').innerHTML = '<i class="fa fa-check"></i> All questions deleted.'; setTimeout(function(){ closeDeleteQuestionsModal(); refreshQuestionsTable(testId, null); }, 1200); }
					else if(html==3){ document.getElementById('deleteQuestionsMessage').innerHTML = 'Cannot delete; some examinees have started or completed this test.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
					else { document.getElementById('deleteQuestionsMessage').innerHTML = 'Could not delete all questions. Please try again.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
				}});
			}
		}
		
		//calling refresh table after a question delete is successfully
		function refreshQuestionsTable(testId, questionId)
		{
			$('#example').DataTable().clear().destroy();
			$.ajax({
				url: 'getTestQuestion.php',
				type: 'POST',
				data: {testId : testId},
				dataType: 'JSON',
				success: function(response)
				{
					var len = response.length;
					for(var i=0; i<len; i++){
						var questionId = response[i].questionId;
						var question = response[i].question;
						var stimulusLabel = response[i].stimulusLabel || '';
						var optionA = response[i].optionA;
						var optionB = response[i].optionB;
						var optionC = response[i].optionC;						
						var optionD = response[i].optionD;												
						var optionE = response[i].optionE;												
						var correctOption = response[i].correctOption;										
						var markForQuestion = response[i].markForQuestion;										
						var solvedSolution = response[i].solvedSolution;										
						
						var tr_str = "<tr>" +
							"<td width='3%'><center><input type='checkbox' class='row-select' data-question-id='"+questionId+"'></center></td>" +
							"<td width='3%'><center>" + (i+1) + "</center></td>" +
							"<td width='25%'>" + question + "</td>" +
							"<td width='10%'><center>" + stimulusLabel + "</center></td>" +
							"<td width='9%'><center>" + optionA + "</center></td>" +
							"<td width='8%'><center>" + optionB + "</center></td>" +
							"<td width='8%'><center>" + optionC + "</center></td>" +
							"<td width='8%'><center>" + optionD + "</center></td>" +
							"<td width='8%'><center>" + (optionE || '') + "</center></td>" +
							"<td width='7%'><center>" + correctOption + "</center></td>" +
							"<td width='5%'><center>" + markForQuestion + "</center></td>" +
							"<td align='center' width='12%'><a onclick='beforeSubmitToEditTest(\""+testId+"\",\""+questionId+"\")' title='Edit this question' style='cursor:pointer;'><i class='fa fa-pencil-square-o' aria-hidden='true' style='color:blue;'></i></a> &nbsp; &nbsp;<a onClick='deleteQuestion(\""+testId+"\",\""+questionId+"\")' title='Delete this question' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
							
							"</tr>";
							$("#example tbody").append(tr_str);
					}
					$('#example').DataTable( {
						"paging":   true,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							{
								extend: 'copy',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'csv',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'excel',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'pdf',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							},
							{
								extend: 'print',
								exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
							}
						],
						rowReorder: {
							selector: 'td:nth-child(2)'
						},
						"responsive": true
					});
					if(response.length > 0)
					{
						$('#toViewEssayQuestions').html("| &nbsp; &nbsp; &nbsp;<a onclick='previewObjectives(\""+testId+"\")' style='cursor:pointer; background-color:#0095d9; color:#ffffff; padding:5px 10px; border-radius:3px;'><i class='fa fa-eye'></i> Preview Objectives (Student View)</a>&nbsp; &nbsp; &nbsp;<a onclick='viewEssay(\""+testId+"\")' style='cursor:pointer; background-color:#e91e8c; color:#ffffff; padding:5px 10px; border-radius:3px;'><i class='fa fa-file-text'></i> View Essay</a>&nbsp; &nbsp; &nbsp;<a onclick='printSetQuestions(\""+testId+"\")' style='cursor:pointer; background-color:#28a745; color:#ffffff; padding:5px 10px; border-radius:3px;'><i class='fa fa-print'></i> Print/Export Set Questions</a>&nbsp; &nbsp;")
					}
					else
					{
						$('#toViewEssayQuestions').html("")
					}
				}
			});
		}
		
		function previewObjectives(testId)
		{
			// Open preview in new window with full screen dimensions
			var width = window.screen.width;
			var height = window.screen.height;
			window.open('previewObjectivesAsStudent.php?testId=' + testId, '_blank', 'width=' + width + ',height=' + height);
		}
		
		function viewEssay(testId)	//Declaration of the data table function
		{			
			$.ajax({
					url: 'getEssayQuestion.php',
					type: 'POST',
					data: {testId : testId},
					dataType: 'JSON',
					success: function(response)
					{	
						if(response.length > 0)
						{
							var question = response[0].question;
							var modal1 = document.getElementById("myModal1");
							$("#theQuestion").html(question);						
							modal1.style.display = "block";
						}
						else
						{
								alert("No essay uploaded for this test yet");
						}
					}
			});
		}
		
		function printSetQuestions(testId)
		{
			// Open print/export page in new window
			var width = window.screen.width;
			var height = window.screen.height;
			window.open('printSetQuestions.php?testId=' + testId, '_blank', 'width=' + width + ',height=' + height);
		}
		
		function closeEssayModal() {
			var modal1 = document.getElementById("myModal1");
			modal1.style.display = "none";
		}
		
		var span1 = document.getElementsByClassName("close1")[0];
            
        // When the user clicks on <span> (x), close the modal1
        span1.onclick = function() {
			closeEssayModal();
        }
            
        // When the user clicks anywhere outside of the modal, close it
        var modal1 = document.getElementById("myModal1");
        window.onclick = function(event) {
            if (event.target == modal1) {
                closeEssayModal();
            }
        }
		
		//Function to send data of test to be edited
		function beforeSubmitToEditTest(testId, questionId)
		{
			document.getElementById('testIdToEdit').value = testId;
			document.getElementById('questionIdToEdit').value = questionId;
			document.getElementById('frmAddQuestOrViewQuestStatus').value = 2;
			document.getElementById("submitToEditQuestionForm").submit();
			
		}
	</script>
	
  </body>
</html>
