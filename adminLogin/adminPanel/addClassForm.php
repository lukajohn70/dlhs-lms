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
    <link rel="shortcut icon" href="../images/dlhslogo2.jpg">

    <title>Add class | DLHS</title>

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
	
	<!-- Toast Notification Styles -->
	<style>
		.toast {
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			padding: 16px 20px;
			margin-bottom: 10px;
			min-width: 300px;
			max-width: 400px;
			position: relative;
			transform: translateX(100%);
			transition: transform 0.3s ease-in-out;
			border-left: 4px solid;
		}
		
		.toast.show {
			transform: translateX(0);
		}
		
		.toast.success {
			border-left-color: #28a745;
		}
		
		.toast.error {
			border-left-color: #dc3545;
		}
		
		.toast-header {
			display: flex;
			align-items: center;
			margin-bottom: 8px;
		}
		
		.toast-icon {
			margin-right: 8px;
			font-size: 18px;
		}
		
		.toast-title {
			font-weight: 600;
			font-size: 14px;
			margin: 0;
		}
		
		.toast-close {
			margin-left: auto;
			background: none;
			border: none;
			font-size: 18px;
			cursor: pointer;
			color: #6c757d;
		}
		
		.toast-body {
			font-size: 13px;
			color: #495057;
			line-height: 1.4;
		}
		
		.toast.success .toast-icon {
			color: #28a745;
		}
		
		.toast.error .toast-icon {
			color: #dc3545;
		}
	</style>
	
	<script src="jQuery3.3.1.js"></script>
	<style>
		.modal1, .modal2, .modal3 {
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add new Class</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Classes</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-3" id="createFormColumn" style="display: none;">
						<section class="panel">
							<header class="panel-heading">
								Please fill required fields in the form
							</header>
							<div class="panel-body">
								<form id="addClassForm">
									<div class="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Name of Class (e.g. <em>Zambezi</em>)</label>
										<input type="text" name="theClassname" class="form-control" placeholder="Enter Class name (e.g. Zambezi)" required >
									</div>
									<div class="form-group">
										<label>Year group</label>
										<select class="form-control m-bot15" name="yearGroupId" id="yearGroupId" required >
											<option value="">... Select Year group ...</option>
											<?php
											
											$yeargroups="select * from yeargroup";
											$result1 = $connection->query($yeargroups);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
									</div>
									<button type="button" class="btn btn-primary" onclick="addAClass()"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-12" id="tableColumn">
						<section class="panel">
							<header class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
								<span>View | Edit | Delete Class &nbsp; &nbsp; <font class="message6" style="color:green; font-size:17px;" align="center"></font></span>
								<button class="btn btn-success btn-sm" id="toggleFormBtn" onclick="dlhsToggleCreateForm()" style="font-weight: 700; border-radius: 6px; padding: 6px 12px;"><i class="fa fa-plus"></i> Add New Class</button>
							</header>
							<div class="panel-body">
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="5%"><center>S/NO</center></th>
												<th width="15%"><center>CLASS NAME</center></th>
												<th width="20%"><center>YEAR GROUP</center></th>
												<th width="35%"><center>STAFF/SUBJECT ASSIGNMENT</center></th>
												<th width="20%"><center>ACTION</center></th>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Edit class</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" id="editClassForm">
							
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Class name to edit: <b><font id="classNameToEdit"></font></b></span></label><br>
									<label><span style="color:#000000;">Change class details below: </span> <span class="required" style="color:red;">*</span></label><br>
									<input type="hidden" name="classId" required="required" id="classId">
									
									<input type="text" name="className1" required="required" id="className1" class="form-control">
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label style="color:#000000;">Year group:</label>
									<select class="form-control m-bot15" name="yearGroupId1" id="yearGroupId1">
										<option value="">... Select Year group ...</option>
										<?php
										
										$yeargroups2="select * from yeargroup";
										$result2 = $connection->query($yeargroups2);
										while($row2 = $result2->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row2[0]; ?>"><?php echo $row2[1]; ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button class="btn btn-primary" type="reset">Reset</button>
									<button type="button" class="btn btn-success" onclick="editClassDetails()">Update</button>
								</div>
							</div>
							<div class="message3" style="color:red;" align="center"></div><div class="message4" style="color:green; font-size:17px;" align="center"></div><br>
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
    
    <!-- Toast Notification Container -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
    
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
		function dlhsToggleCreateForm() {
			var formCol = document.getElementById('createFormColumn');
			var tableCol = document.getElementById('tableColumn');
			var btn = document.getElementById('toggleFormBtn');
			if (!formCol || !tableCol || !btn) return;
			
			if (formCol.style.display === 'none') {
				formCol.style.display = 'block';
				tableCol.className = 'col-lg-9';
				btn.innerHTML = '<i class="fa fa-minus"></i> Hide Form';
				btn.className = 'btn btn-danger btn-sm';
			} else {
				formCol.style.display = 'none';
				tableCol.className = 'col-lg-12';
				btn.innerHTML = '<i class="fa fa-plus"></i> Add New Class';
				btn.className = 'btn btn-success btn-sm';
			}
			
			if ($.fn.dataTable) {
				$('#example').DataTable().columns.adjust().responsive.recalc();
			}
		}

		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getClasses.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var classId = response[i].classId;
							var classYearGroupId = response[i].classYearGroupId;						
							var className = response[i].className;						
							var yearGroupName = response[i].yearGroupName;						
							
							var tr_str = "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td>" + className + "</td>" +
								"<td>" + yearGroupName + "</td>" +
								"<td><center><a href='subjectAssignment.php?classId="+classId+"&yearGroupId="+classYearGroupId+"' title='Manage teacher/subject assignments' class='btn btn-xs btn-info'><i class='fa fa-random'></i> Manage Assignments</a></center></td>" +
								"<td align='center'><a onClick='openEditModal(\""+classId+"\",\""+classYearGroupId+"\",\""+className+"\",\""+yearGroupName+"\")' title='Edit this class' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a>&nbsp; &nbsp; &nbsp; &nbsp;<a onClick='deleteClass(\""+classId+"\",\""+className+"\")' title='Delete this class' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
		function deleteClass(classId, className)
		{
			$('.message1').html("");
			$('.message2').html("");
			
			var toConfirm=confirm("Are you sure you wish to delete this class?");
			if (toConfirm==true)
			{
				alert("Please contact developer if you wish to delete selected class");
				/*var form_data = 
					  'classId='+classId;
					  
					$.ajax({
						url: "deleteClass.php",
						type: "POST",        
						data: form_data,
						success: function (html) {             
							if (html==0)	//If session is expired.
							{                              
								 window.location.replace("logout.php");
							}
							else if (html==1)	
							{                              
								 $('.message2').html("");
								 $('.message1').html('<i class="fa fa-check"></i> ' + className + ' class successfully deleted.').fadeIn('slow');
								 $('#example').DataTable().clear().destroy();
								 callTable();
							}
							else if (html==2)	//If deletion is unsuccessful	
							{                              
								 $('.message1').html("");
								 $('.message2').html('<i class="fa fa-times"></i> Could not delete class. Please try again.').fadeIn('slow');
							}
						}
									
					});
				*/
			}	
		}
		
		function editClassDetails()
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
					
			var classId = $('input[name=classId]').val();
			var className = $('input[name=className1]').val();
			var newYearGroupId = document.getElementById("yearGroupId1").value;
			var newYearGroupName = $('#yearGroupId1 option:selected').text();
						
			if (className=="")
			{
				$('.message3').html('Please enter class name')
			}
			else if (newYearGroupId=="")
			{
				$('.message3').html('Please select year group of class')
			}
			else
			{
				var form_data = 
				  'classId='+classId+
				  '&className='+className+
				  '&newYearGroupId='+newYearGroupId;
				  
				$.ajax({
					url: "editClass.php",
					type: "POST",       
					data: form_data,    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message3').html("");
							$('.message1').html('<i class="fa fa-check"></i> successfully edited to ' + newYearGroupName + ' ' + className).fadeIn('slow');
							$("#editYearGroupForm").trigger("reset");
							$('#example').DataTable().clear().destroy();
							callTable();
							var modal1 = document.getElementById("myModal1");
							$('.message3').html('');
							modal1.style.display = "none";
						}
						else if (html==2)
						{                              
							$('.message4').html("");
							$('.message3').html('<i class="fa fa-times"></i> Could not edit Year Group. Please try again.').fadeIn('slow');
						}
						else if (html==3)
						{                              
							$('.message4').html("");
							$('.message3').html('<i class="fa fa-times"></i> The Year group ' + newYearGroupName + ' already exists for the class ' + className + '. Please select another Year Group name or change the class name.').fadeIn('slow');
						}
					}
				});
			}
		}
		
		//function call to open modal for editing a class
		function openEditModal(classId, classYearGroupId, className, yearGroupName)
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
            var modal1 = document.getElementById("myModal1");
			document.getElementById("classId").value=classId;  
			document.getElementById("className1").value=className;  
			document.getElementById("yearGroupId1").value=classYearGroupId;  
                
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
		
		// Toast Notification Functions
		function showToast(type, title, message) {
			const container = document.getElementById('toast-container');
			const toast = document.createElement('div');
			toast.className = `toast ${type}`;
			
			const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
			
			toast.innerHTML = `
				<div class="toast-header">
					<i class="fa ${icon} toast-icon"></i>
					<h6 class="toast-title">${title}</h6>
					<button type="button" class="toast-close" onclick="removeToast(this)">&times;</button>
				</div>
				<div class="toast-body">${message}</div>
			`;
			
			container.appendChild(toast);
			
			// Trigger animation
			setTimeout(() => {
				toast.classList.add('show');
			}, 100);
			
			// Auto remove after 5 seconds
			setTimeout(() => {
				removeToast(toast.querySelector('.toast-close'));
			}, 5000);
		}
		
		function removeToast(closeButton) {
			const toast = closeButton.closest('.toast');
			toast.classList.remove('show');
			setTimeout(() => {
				if (toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
			}, 300);
		}
		
		//Adding a class
		function addAClass()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message6').html('');
			
			
			var theClassname = $('input[name=theClassname]').val();
			var yearGroupId = document.getElementById('yearGroupId').value;		
			var yearGroupName = $('#yearGroupId option:selected').text();		
									 
			if (theClassname=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter class name')
			}
			else if (yearGroupId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select year group')
			}
			else
			{
				var form_data = 
							  'classname='+theClassname+
							  '&yearGroupId='+yearGroupId;
				$.ajax({
					url: "addClass.php",
					type: "POST",       
					data: form_data,    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message2').html("");
							$('.message1').html('<i class="fa fa-check"></i> ' + yearGroupName + " " + theClassname + ' successfully added.').fadeIn('slow');
							$("#addClassForm").trigger("reset");
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html==2)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-times"></i> Could not add class. Please try again.').fadeIn('slow');
						}
						else if (html==3)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-times"></i> The class ' + yearGroupName + " " + theClassname + ' already exists. Please enter another class.').fadeIn('slow');
						}
					}
				});
			}
		}
	</script>
  </body>
</html>
