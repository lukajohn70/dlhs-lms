<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/dlhs_db_connection.php";
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

    <title>Manage Test Access | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <!-- date picker -->
    
    <!-- color picker -->
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	 <!-- bootstrap-wysiwyg -->
    <script src="js/jquery.hotkeys.js"></script>
    <script src="js/bootstrap-wysiwyg.js"></script>
    <script src="js/bootstrap-wysiwyg-custom.js"></script>
	<script>
		function getChecked()
		{
					
			var favorite1 = []
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox[]']:checked");

			for (var i = 0; i < checkboxes.length; i++) {
				favorite1.push(checkboxes[i].value)
			}
			
			favorite=favorite1.join(",")
			var testId = document.getElementById('test').value;
			var testName2 = $("#test option:selected").text();
			var yearGroup = document.getElementById('yearGroup').value;
			var yearGroupName2 = $("#yearGroup option:selected").text();
			var studentClass = document.getElementById('studentClass').value;
			var studentClassName = $("#studentClass option:selected").text();
            		
			$('.message1').html('')
			if (favorite=="")
			{
				$('.message2').html('Please check at least one student before submitting')
			}
			else if (testId=="")
			{
				$('.message2').html('Please select test')
			}
			else if (yearGroup=="")
			{
				$('.message2').html('Please select year group')
			}
			else if (studentClass=="")
			{
				$('.message2').html('Please select student\'s class')
			}
			else
			{
				$('.message2').html('');
								
				//organize the data properly
						var form_data = 
						  'testId='+testId+
						  '&checkedStudents='+favorite;
											  						 
						//start the ajax
						$.ajax({
							//this is the php file that processes the data and send mail
							url: "addStudentsForTest.php",
							 
							//POST method is used
							type: "POST",
				 
							//pass the data        
							data: form_data,    
							 
							
							//success
							success: function (html) {             
																
								if (html==0)	//If session is expired.
								{                              
									 window.location.replace("logout.php");
								}
								else if (html==1)	//If examinees successfully added to write test
								{                              
									 $('.message2').html('');
									 $('.message1').html('The selected Student(s) from '+yearGroupName2+' '+studentClassName+' have successfully been added to write '+testName2+' Test.').fadeIn('slow');
								}
								else 	//If insertion is unsuccessful	
								{                              
									 $('.message1').html('');
									 $('.message2').html('Could not add selected Student(s). Please try again.').fadeIn('slow');
								}
							}
										
						});
			}
		}
	</script>
    <!-- ck editor -->
    <script type="text/javascript" src="assets/ckeditor/ckeditor.js"></script>
	<script src="jQuery3.3.1.js"></script>
	<script src="manageStudentsAndTestAjax.js"></script>
	
	<style>
		body{
			overflow-x:hidden;
			overflow-y:auto;
			height:600px;
		}
	</style>
	
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBar.php'; ?>

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Manage Student(s) & Test</h3>
					<ol class="breadcrumb" style="font-size:12px;">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Manage Student(s) & Test</li>
						<a href="#" style="color:#0acca2; padding-left:4px;"> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								Manage Student(s) & Test
							</header>
							<div class="panel-body">
								<form class="form-horizontal" action="manageStudentsAndTestForm2.php" method="post">
									<div class="form-group">
										<label class="control-label col-sm-4"><strong>Select Test to Manage Student(s) assigned to write it:</strong></label>
										<div class="col-sm-8">
											<select class="form-control" name="test" id="test" required >
												<option value="">... Select Test ...</option>
												<?php
													$staffId=$_SESSION['staffId'];
													$test="select * from tests WHERE staffId='$staffId'";
													$result = $connection->query($test);
													while($row = $result->fetch_array(MYSQLI_NUM)){
												?>
												<option value="<?php echo $row[0]; ?>"><?php echo $row[2]; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i> Submit</button>
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
            &copy; <?php echo " ". date("Y")." ";?> Copyright: <a href="https://zamanicollege.com/" style="padding-right:10px;">Zamani College</a>
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
  </body>
</html>
