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

    <title>Assign form teachers | DLHS</title>

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
	<script src="jQuery3.3.1.js"></script>
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Assign form teachers</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Form teachers</li>
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
								<form id="editFormTeacherForm">
									<div class="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Year group</label>
										<select class="form-control m-bot15" name="yearGroupId" id="yearGroupId" onchange="getClasses()">
											<option value="">. . . Select Year group . . .</option>
											<?php
											
											$yeargroups="select * from yeargroup";
											$result1 = $connection->query($yeargroups);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="form-group">
										<label>Class</label>
										<select class="form-control m-bot15" name="classId" id="classId">
											
										</select>
									</div>
									<div class="form-group">
										<label>Form Teacher</label>
										<select class="form-control m-bot15" name="staffId" id="staffId" required >
											<option value="">. . . Select Form Teacher . . .</option>
											<?php
											
											$yeargroups="select * from stafflogin";
											$result1 = $connection->query($yeargroups);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1].' '.$row1[2].' '.$row1[3]; ?></option>
											<?php } ?>
										</select>
									</div>
									<button type="button" class="btn btn-primary" onclick="assignFormTeacher()"><i class="fa fa-sign-in"></i> Assign</button>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-9">
						<section class="panel">
							<header class="panel-heading">
								View | Edit | Delete Form Teacher Assignment
							</header>
							<div class="panel-body">
							<div class="message4" style="color:green; font-size:17px;" align="center"></div><br>
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="5%"><center>S/NO</center></th>
												<th width="35%"><center>CLASS NAME</center></th>
												<th width="35%"><center>FORM TEACHER</center></th>
												<th width="25%"><center>ACTION</center></th>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Edit form teacher assignment</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" id="editClassForm">
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Current assignment:</span></label><br>
									<label><span style="color:#000000;"><b><font id="currentClass"></font><font id="currentFormTeacher"></font></b></span></label><br>
									
								</div>
							</div>
                            <div class="form-group">
										<label style="color:#000000;">Year group</label>
										<input type="hidden" name="formTeacherAssignmentId" id="formTeacherAssignmentId">
										<select class="form-control m-bot15" name="yearGroupId1" disabled id="yearGroupId1" onchange="getClasses1()">
											<option value="">. . . Select Year group . . .</option>
											<?php
											
											$yeargroups="select * from yeargroup";
											$result1 = $connection->query($yeargroups);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="form-group">
										<label style="color:#000000;">Class</label>
										<select class="form-control m-bot15" disabled name="classId1" id="classId1">
											
										</select>
									</div>
									<div class="form-group">
										<label style="color:#000000;">Form Teacher</label>
										<select class="form-control m-bot15" name="staffId1" id="staffId1" required >
											<option value="">. . . Select Form Teacher . . .</option>
											<?php
											
											$yeargroups="select * from stafflogin";
											$result1 = $connection->query($yeargroups);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1].' '.$row1[2].' '.$row1[3]; ?></option>
											<?php } ?>
										</select>
									</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="button" class="btn btn-primary" onclick="editFormTeacherAssignment()">Update</button>
								</div>
							</div>
							<div class="message3" style="color:red;" align="center"></div><br>
                        </form>
                    </div>
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
					url: 'getFormTeacherAssignment.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var formTeacherAssignmentId = response[i].formTeacherAssignmentId;
							var staffId = response[i].staffId;
							var classId = response[i].classId;
							var yearGroupId = response[i].yearGroupId;						
							var staffName = response[i].staffName;						
							var className = response[i].className;						
							var yearGroupName = response[i].yearGroupName;						
							
							var tr_str = "<tr>" +
								"<td>" + (i+1) + "</td>" +
								"<td>" + yearGroupName + ' '+ className + "</td>" +
								"<td>" + staffName + "</td>" +
								"<td align='center'><a onClick='openEditModal(\""+formTeacherAssignmentId+"\",\""+classId+"\",\""+staffId+"\",\""+yearGroupId+"\",\""+staffName+"\",\""+className+"\",\""+yearGroupName+"\")' title='Edit this form teacher/class assignment' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a>&nbsp; &nbsp; &nbsp; &nbsp;<a onClick='deleteFormTeacherAssignment(\""+formTeacherAssignmentId+"\",\""+staffName+"\",\""+yearGroupName+"\",\""+className+"\")' title='Delete this form teacher/class assignment' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
		
		//Delete year group
		function deleteFormTeacherAssignment(formTeacherAssignmentId, staffName, yearGroupName, className)
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
			
			var toConfirm=confirm("Are you sure you wish to delete the form teacher of " + yearGroupName + " " + className + "?");
			if (toConfirm==true)
			{
				$.ajax({
					url: "deleteFormTeacherAssignment.php",
					type: "POST",        
					data: {formTeacherAssignmentId:formTeacherAssignmentId},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message2').html("");
							 $('.message1').html('<i class="fa fa-check-circle"></i> ' + staffName + ' successfully deleted as form teacher of ' + yearGroupName + ' ' + className).fadeIn('slow');
							 $('#example').DataTable().clear().destroy();
							 callTable();
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							 $('.message1').html("");
							 $('.message2').html('<i class="fa fa-times"></i> Could not delete form teacher/class assignment. Please try again.').fadeIn('slow');
						}
					}
								
				});
			}	
		}
		
		//function call to open modal for editing a class
		function openEditModal(formTeacherAssignmentId, classId, staffId, yearGroupId, staffName, className, yearGroupName)
        {
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
            var modal1 = document.getElementById("myModal1");
			document.getElementById('formTeacherAssignmentId').value = formTeacherAssignmentId;
			$("#yearGroupId1 option[value="+yearGroupId+"]").attr('selected', 'selected');
			$.ajax({
				type: "POST",
				url: "getClassToAssignFormTeacher.php",
				data: { yearGroup : yearGroupId, yearGroupName : yearGroupName} 
			}).done(function(data){
				$("#classId1").html(data);
				$("#classId1 option[value="+classId+"]").attr('selected', 'selected');
			});
			
			$("#staffId1 option[value="+staffId+"]").attr('selected', 'selected'); 
			$("#currentClass").html(yearGroupName + " - " + className); 
			$("#currentFormTeacher").html(" (Current form teacher: " + staffName + ")" ); 
                
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
		
		//Updating a form teacher assignment
		function editFormTeacherAssignment()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
		
			var existingAssignmentId = document.getElementById('formTeacherAssignmentId').value;					
			var staffId = document.getElementById('staffId1').value;	
			var staffName = $('#staffId1 option:selected').text();		
									 
			if (staffId == "")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select teacher to update as form teacher')
			}
			else
			{
				$.ajax({
					url: "editFormTeacher.php",
					type: "POST",       
					data: {existingAssignmentId:existingAssignmentId, staffId:staffId},    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{       
							var className = $("#currentClass").html();;
							$('.message4').html('<i class="fa fa-check-circle"></i> ' + staffName + ' successfully assigned as form teacher of ' + className).fadeIn('slow');
							$("#editFormTeacherForm").trigger("reset");
							var modal1 = document.getElementById("myModal1");
							modal1.style.display = "none";
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html==2)
						{                              
							$('.message2').html('<i class="fa fa-times"></i> Could not assign ' + staffName + ' as form teacher. Please try again.').fadeIn('slow');
						}
					}
				});
																		 
				
			}
		}
		//End of function declaration to update form teacher assignment
		
		
		//Assigning a form teacher to class
		function assignFormTeacher()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
		
			var yearGroupId = document.getElementById('yearGroupId').value;				
			var classId = document.getElementById('classId').value;		
			var staffId = document.getElementById('staffId').value;
			var yearGroupName = $('#yearGroupId option:selected').text();	
			var className = $('#classId option:selected').text();	
			var staffName = $('#staffId option:selected').text();		
									 
			if (yearGroupId == "")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select year group')
			}
			else if (classId == "")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select class')
			}
			else if (staffId == "")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select teacher to assign as form teacher')
			}
			else
			{
				$.ajax({
					url: "assignFormTeacher.php",
					type: "POST",       
					data: {yearGroupId:yearGroupId, classId:classId, staffId:staffId},    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message2').html("");
							$('.message1').html('<i class="fa fa-check-circle"></i> ' + staffName + ' successfully assigned as form teacher of ' + yearGroupName + ' ' + className).fadeIn('slow');
							$("#assignFormTeacherForm").trigger("reset");
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html==2)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-times"></i> Could not assign ' + staffName + ' as form teacher. Please try again.').fadeIn('slow');
						}
						else if (html==3)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-info-circle"></i> The class <b>' + yearGroupName + " " + className + '</b> has already been assigned a form teacher.').fadeIn('slow');
						}
					}
				});
																		 
				
			}
		}
		//End of function declaration to add a class
		
		//Fetching class for year group change when adding a form teacher assignment.
		function getClasses()
		{
			var selectedYearGroup = $("#yearGroupId option:selected").val();
			var theYearGroup=$("#yearGroupId option:selected").text();
			$.ajax({
				type: "POST",
				url: "getClassToAssignFormTeacher.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
			}).done(function(data){
				$("#classId").html(data);
			});
		}
		
		//Fetching class for year group change.
		function getClasses1()
		{
			var selectedYearGroup = $("#yearGroupId1 option:selected").val();
			var theYearGroup=$("#yearGroupId1 option:selected").text();
			$.ajax({
				type: "POST",
				url: "getClassToAssignFormTeacher.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
			}).done(function(data){
				$("#classId1").html(data);
			});
		}
	</script>


  </body>
</html>
