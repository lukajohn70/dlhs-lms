<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{
		header('location:../index.php');
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="shortcut icon" href="../images/dlhslogo2.jpg">

    <title>Manage Psychomotor Skills | DLHS</title>

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
	<script src="addSubjectAjax.js"></script>
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
            color: #000000;;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Manage Psychomotor Skills</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Psychomotor Skills</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-3">
						<section class="panel">
							<header class="panel-heading">
								Please fill required fields in the form
							</header>
							<div class="panel-body">
								<form id="subjectForm">
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Pyschomotor Skill(e.g. <em>Handling Tools</em>)</label>
										<input type="text" name="pyschomotorSkill" class="form-control" placeholder="Enter pyschomotor skill(e.g. Handling Tools)" required >
									</div>
									<button type="button" class="btn btn-primary" onclick="addPyschomotorSkill()"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-8">
						<section class="panel">
							<header class="panel-heading">
								View | Edit | Delete Psychomotor skills
							</header>
							<div class="panel-body">
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th><center>S/NO</center></th>
												<th><center>PSYCHOMOTOR SKILL</center></th>
												<th><center>ACTION</center></th>
											</tr>
										</thead>
										<tbody>
												
										</tbody>
									</table>
								</div>
								<!-- End of table-responsive -->
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
	  
	  <div id="myModal1" class="modal1"> <!--Start of Edit modal-->
				<div class="modal-content">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Edit Psychomotor Skill</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" id="editPsychomotorForm">
							<div class="message3" style="color:red;" align="center"></div><div class="message4" style="color:green; font-size:17px;" align="center"></div><br>
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Psychomotor skill to edit: <b><font id="psychomotorNameToEdit"></font></b></span></label><br>
									<label><span style="color:#000000;">Change psychomotor skill name below: </span> <span class="required" style="color:red;">*</span></label><br>
									<input type="hidden" name="pyschomotorSkillId1" id="pyschomotorSkillId1">
									<input type="text" name="pyschomotorSkill1" required="required" id="pyschomotorSkill1" class="form-control">
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="button" class="btn btn-primary" onclick="editPsychomotorSkill()">Update</button>
								</div>
							</div>
                        </form>
                    </div>
						<br>
						<br>
						<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of edit modal-->
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
   
    <!-- bootstrap-wysiwyg -->
    <script src="js/jquery.hotkeys.js"></script>
    <script src="js/bootstrap-wysiwyg.js"></script>
    <script src="js/bootstrap-wysiwyg-custom.js"></script>
    <!-- ck editor -->
    <script type="text/javascript" src="assets/ckeditor/ckeditor.js"></script>
    <!-- custom form component script for this page-->
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
	<script src="../../datatables/js/jquery.dataTables.min.js"></script>
	<script src="../../datatables/js/dataTables.rowReorder.min.js"></script>
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script>
		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getPsychomotorSkills.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var psychomotorSkillId = response[i].psychomotorSkillId;
							var psychomotorSkillName = response[i].psychomotorSkillName;						
							
							var tr_str = "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td><center>" + psychomotorSkillName + "</center></td>" +
								"<td align='center'><a onClick='openEditModal(\""+psychomotorSkillId+"\",\""+psychomotorSkillName+"\")' title='Edit this psychomotor skill' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a>&nbsp; &nbsp; &nbsp; &nbsp;<a onClick='deletePsychomotor(\""+psychomotorSkillId+"\",\""+psychomotorSkillName+"\")' title='Delete this psychomotor skill' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
							'copy', 'csv', 'excel', 'pdf', 'print'
						],
						rowReorder: {
								selector: 'td:nth-child(2)'
							},
							"responsive": true
						});
					}
			});
		}
		//calling the data table function
		callTable();
		
		function addPyschomotorSkill()	//Function to add character
		{
			$('.message1').html("");
			$('.message2').html("");
					
			var pyschomotorSkill = $('input[name=pyschomotorSkill]').val();
			
			if (pyschomotorSkill=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter psychomotor skill')
			}
			else
			{
				var addPsychomotor = 1;
				$.ajax({
					url: "managePsychomotor.php",
					type: "POST",       
					data: {addPsychomotor:addPsychomotor, pyschomotorSkill:pyschomotorSkill},    
					success: function (html) {				
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message2').html("");
							$('.message1').html('<i class="fa fa-check-circle"></i> ' + pyschomotorSkill + ' successfully added.').fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html==2)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-times-circle"></i> Could not add psychomotor skill. Please try again.').fadeIn('slow');
						}
						else if (html==3)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-info-circle"></i> The psychomotor skill <b>' + pyschomotorSkill + '</b> already exists.').fadeIn('slow');
						}
					}
				});
			}
		}	//End of function to add a character
		
		//Delete class
		function deletePsychomotor(psychomotorSkillId, psychomotorSkillName)
		{
			$('.message1').html("");
			$('.message2').html("");
			
			var toConfirm=confirm("Are you sure you wish to delete this psychomotor skill?");
			if (toConfirm==true)
			{
				var deletePsychomotor = 1;
				$.ajax({
					url: "managePsychomotor.php",
					type: "POST",        
					data: {deletePsychomotor:deletePsychomotor, psychomotorSkillId:psychomotorSkillId},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message2').html("");
							 $('.message1').html('<i class="fa fa-check-circle"></i> The psychomotor skill <b>'  + psychomotorSkillName + '</b> has been successfully deleted.').fadeIn('slow');
							 $('#example').DataTable().clear().destroy();
							 callTable();
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							 $('.message1').html("");
							 $('.message2').html('<i class="fa fa-times-circle"></i> Could not delete psychomotor skill. Please try again.').fadeIn('slow');
						}
					}
				});
			}	
		}
		
		function editPsychomotorSkill()
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
					
			var pyschomotorSkillId = $('input[name=pyschomotorSkillId1]').val();
			var pyschomotorSkill = $('input[name=pyschomotorSkill1]').val();
			
			if (pyschomotorSkill=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please enter name of character')
			}
			else
			{
				var editPsychomotor = 1;
				$.ajax({
					url: "managePsychomotor.php",
					type: "POST",       
					data: {editPsychomotor:editPsychomotor, pyschomotorSkillId:pyschomotorSkillId, pyschomotorSkill:pyschomotorSkill},    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message3').html("");
							$('.message1').html('<i class="fa fa-check-circle"></i> successfully edited to <b>' + pyschomotorSkill + '</b>').fadeIn('slow');
							$("#editPsychomotorForm").trigger("reset");
							$('#example').DataTable().clear().destroy();
							callTable();
							var modal1 = document.getElementById("myModal1");
							$('.message3').html('');
							modal1.style.display = "none";
						}
						else if (html==2)
						{                              
							$('.message4').html("");
							$('.message3').html('<i class="fa fa-times-circle"></i> Could not edit psychomotor skill. Please try again.').fadeIn('slow');
						}
						else if (html==3)
						{                              
							$('.message4').html("");
							$('.message3').html('<i class="fa fa-times-circle"></i> The psychomotor skill <b>' + pyschomotorSkill + '</b> already exists.').fadeIn('slow');
						}
					}
				});
			}
		}
		
		//function call to open modal for editing a category
		function openEditModal(pyschomotorSkillId, psychomotorSkillName)
        {
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
            var modal1 = document.getElementById("myModal1");
			document.getElementById("pyschomotorSkillId1").value = pyschomotorSkillId;  
			document.getElementById("pyschomotorSkill1").value = psychomotorSkillName;  
            $("#psychomotorNameToEdit").html(psychomotorSkillName);  
                
            modal1.style.display = "block";
        }
		var span1 = document.getElementsByClassName("close1")[0];
            
        // When the user clicks on <span> (x), close the modal1
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
	</script>
  </body>
</html>
