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
		
		if(!empty($_GET['uploadStatus']))
		{
			switch($_GET['uploadStatus']){
				case 'succ_insert':
					$statusType = 'alert-success';
					$statusMsg = '<i class="fa fa-check-circle"></i> Questions have been imported successfully';
					break;
				case 'succ_update':
					$statusType = 'alert-success';
					$statusMsg = '<i class="fa fa-check-circle"></i> Questions have been updated successfully.';
					break;
				case 'err':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> Some problem occurred, please try again.';
					break;
				case 'invalid_file':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> Please upload a valid CSV file.';
					break;
				default:
					$statusType = '';
					$statusMsg = '';
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
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- date picker -->
    
    <!-- color picker -->
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowreorder/1.2.7/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>
	<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet" />
	 <!-- bootstrap-wysiwyg -->
    <script src="js/jquery.hotkeys.js"></script>
    <script src="js/bootstrap-wysiwyg.js"></script>
    <script src="js/bootstrap-wysiwyg-custom.js"></script>
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
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBarAddQuestions.php'; ?>

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
								Add Questions to selected Test
							</header>
							<div class="panel-body">
								<form class="form-horizontal" name="myForm" method="post" action="uploadQuestions.php" enctype="multipart/form-data">
									<!-- Display status message -->
									<?php if(!empty($statusMsg)){ ?>
									<div class="col-md-12">
										<div class="alert <?php echo $statusType; ?>"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg; ?></div>
									</div>
									<?php } ?><p><br>
									<div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label class="control-label col-sm-4"><strong>Select Test to Add Question:</strong></label>
										<div class="col-sm-8">
											<select class="form-control" name="testName" id="testName" required >
												<option value="">... Select Test ...</option>
												<?php
													$staffId=$_SESSION['staffId'];
													$test="select * from tests WHERE staffId='$staffId'";
													$result1 = $connection->query($test);
													while($row1 = $result1->fetch_array(MYSQLI_NUM)){
												?>
												<option value="<?php echo $row1[0]; ?>"><?php echo $row1[2]; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-4"><strong>Select <i>.csv</i> file</strong></label>
										<div class="col-sm-8">
											<input type="file" name="file" accept=".csv" required /><br>
											<button type="submit" class="btn btn-primary" id="submitUpload"><i class="fa fa-sign-in"></i> Submit selected CSV</button>
										</div>
										
									</div>
								</form>
								<form class="form-horizontal" name="myForm" >	
									<div class="form-group">
                                        <center><label><strong>Question here:</strong></label></center>
                                        <div class="col-sm-12">
											<textarea class="form-control ckeditor" name="question" id="question"  rows="6"></textarea>
                                        </div>
                                    </div>
									<div class="form-group">
                                        <div class="col-sm-3">
											<center><label><strong>Enter option A:</strong></label></center>
                                            <textarea class="form-control ckeditor" id="optionA" name="optionA" rows="6"></textarea>
                                        </div>
										<div class="col-sm-3">
											<center><label><strong>Enter option B:</strong></label></center>
                                            <textarea class="form-control ckeditor" id="optionB" name="optionB" rows="6"></textarea>
                                        </div>
                                        <div class="col-sm-3">
											<center><label><strong>Enter option C:</strong></label></center>
                                            <textarea class="form-control ckeditor" id="optionC" name="optionC" rows="6"></textarea>
                                        </div>
										<div class="col-sm-3">
											<center><label><strong>Enter option D:</strong></label></center>
                                            <textarea class="form-control ckeditor" id="optionD" name="optionD" rows="6"></textarea>
                                        </div>
                                    </div>
									<div class="form-group">
										<div class="col-sm-4">
											<center><label><strong>Enter option E:</strong></label></center>
                                            <textarea class="form-control ckeditor" id="optionE" name="optionE" rows="6"></textarea>
                                        </div>
										<div class="col-sm-4">
											<strong>Select correct option:</strong><br>
										
											<input type="radio" name="options" id="A" value="A"> Option A<br>
											<input type="radio" name="options" id="B" value="B"> Option B<br>
											<input type="radio" name="options" id="C" value="C"> Option C<br>
											<input type="radio" name="options" id="D" value="D"> Option D<br>
											<input type="radio" name="options" id="E" value="E"> Option E<br><br>
											Mark for question: <input type="text" name="mark" id="mark" class="form-control" placeholder="Please enter mark for this question" onkeypress="return isNumberKey(event,this)"><br>
										</div>
										
                                        <div class="col-sm-4">
											<center><label><strong>Question Solution</strong> (<em>optional</em>):</label></center>
											<textarea class="form-control ckeditor" name="questionSolution" id="questionSolution" rows="6" placeholder="Enter question solution here"></textarea>
                                        </div>
									</div>
									<div class="form-group">
                                        
                                    </div>
									<button type="button" class="btn btn-primary" onclick="submitQuestion()"><i class="fa fa-sign-in"></i> Submit</button>
									<div class="message2" id="message2" style="color:red;" align="center"></div>
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
    <script src="js/jquery.js"></script>
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
    
    <!-- colorpicker -->
   
   
    <!-- custom form component script for this page-->
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
	<script>
		CKEDITOR.replace('question', {
			height: 300,
			filebrowserUploadUrl: "upload.php"
		});
		CKEDITOR.replace('optionA', {
			height: 300,
			filebrowserUploadUrl: "uploadOptionA.php"
		});
		CKEDITOR.replace('optionB', {
			height: 300,
			filebrowserUploadUrl: "uploadOptionB.php"
		});
		CKEDITOR.replace('optionC', {
			height: 300,
			filebrowserUploadUrl: "uploadOptionC.php"
		});
		CKEDITOR.replace('optionD', {
			height: 300,
			filebrowserUploadUrl: "uploadOptionD.php"
		});
		CKEDITOR.replace('optionE', {
			height: 300,
			filebrowserUploadUrl: "uploadOptionE.php"
		});
		CKEDITOR.replace('questionSolution', {
			height: 300,
			filebrowserUploadUrl: "uploadSolution.php"
		});
		
		//if submit button is clicked for adding a question.
		function submitQuestion()
		{       
			$('.message1').html('');
			$('.message2').html('');
				  	  
			var testId = document.getElementById('testName').value;
			var question = CKEDITOR.instances.question.getData().replaceAll('&nbsp;', '');
			var optionA = CKEDITOR.instances.optionA.getData().replaceAll('&nbsp;', '');
			var optionB = CKEDITOR.instances.optionB.getData().replaceAll('&nbsp;', '');
			var optionC = CKEDITOR.instances.optionC.getData().replaceAll('&nbsp;', '');
			var optionD = CKEDITOR.instances.optionD.getData().replaceAll('&nbsp;', '');
			var optionE = CKEDITOR.instances.optionE.getData().replaceAll('&nbsp;', '');
			var checkRadio = document.querySelector('input[name="options"]:checked'); 
			var mark = $('input[name=mark]').val();
			var questionSolution = CKEDITOR.instances.questionSolution.getData();
			
			
			if (testId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select Test name')
			}	
			else if (question.length == 0)
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
			else if (optionE.length == 0)
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
			else if (questionSolution.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter solution for question')
			}
			else
			{	
				alert(question);
				//organize the data properly
				var form_data = 
					'testId='+testId+
					'&question='+question+
					'&optionA='+optionA+
					'&optionB='+optionB+
					'&optionC='+optionC+
					'&optionD='+optionD+
					'&optionE='+optionE+
					'&selectedValue='+checkRadio.value+
					'&mark='+mark+
					'&questionSolution='+questionSolution;
					
				$.ajax({
					url: "cksubmit.php",
					type: "POST",       
					data: {testId:testId, question:question, optionA:optionA, optionB:optionB, optionC:optionC, optionD:optionD, optionE:optionE, selectedValue:checkRadio.value, mark:mark, questionSolution:questionSolution},    
					success: function (html) {             								
						alert(html);
					}
				});
			}
		}
	</script>


  </body>
</html>
