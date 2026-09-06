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
		if(!empty($_GET['msgStatus']))	//for adding new student with a picture of student to be uploaded alongside other data.
		{
			switch($_GET['msgStatus']){
				case '1':
					$statusType = 'alert-success';
					$statusMsg = '<i class="fa fa-check"></i> Student successfully added.';
					break;
				case '2':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> Could not add student. Please try again.';
					break;
				case '3':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-info-circle"></i> Email is already in use. Please use another email.';
					break;
				default:
					$statusType = '';
					$statusMsg = '';
			}
		}
		if(!empty($_GET['msgStatus1']))	//for uploading picture of student
		{
			switch($_GET['msgStatus1']){
				case '1':
					$statusType1 = 'alert-success';
					$statusMsg1 = 'Picture of student successfully uploaded.';
					break;
				case '2':
					$statusType1 = 'alert-danger';
					$statusMsg1 = 'Could not upload picture of student. Please try again.';
					break;
				default:
					$statusType1 = '';
					$statusMsg1 = '';
			}
		}
		if(!empty($_GET['msgStatus2']))	//for updating picture of student
		{
			switch($_GET['msgStatus2']){
				case '1':
					$statusType2 = 'alert-success';
					$statusMsg2 = 'Picture of student successfully updated.';
					break;
				case '2':
					$statusType2 = 'alert-danger';
					$statusMsg2 = 'Could not upload picture of student. Please try again.';
					break;
				default:
					$statusType2 = '';
					$statusMsg2 = '';
			}
		}
		if(!empty($_GET['msgStatus3']))	//for uloading students through csv
		{
			switch($_GET['msgStatus3']){
				case '1':
					$statusType3 = 'alert-success';
					$statusMsg3 = 'Student(s) successfully uploaded through CSV.';
					break;
				case '2':
					$statusType3 = 'alert-danger';
					$statusMsg3 = 'One or more columns of a record in the CSV file is/are required. Please fill and try again';
					break;
				case '3':
					$statusType3 = 'alert-danger';
					$statusMsg3 = 'Invalid file type uploaded';
					break;
				case '11':
					$statusType3 = 'alert-success';
					$statusMsg3 = 'Students records of selected year group and class successfully updated through CSV';
					break;
				default:
					$statusType3 = '';
					$statusMsg3 = '';
			}
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="shortcut icon" href="../images/dlhslogo2.jpg">

    <title>Add Year group | DLHS</title>

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
				btn.innerHTML = '<i class="fa fa-plus"></i> Add New Student';
				btn.className = 'btn btn-success btn-sm';
			}
			
			if ($.fn.dataTable) {
				$('#example').DataTable().columns.adjust().responsive.recalc();
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
		
		function validatePicture()	//validating the size of picture at first upload of a student
		{
			const fi = document.getElementById('file');
			if (fi.files.length > 0) { 
				for (const i = 0; i <= fi.files.length - 1; i++) 
				{ 
					const fsize = fi.files.item(i).size; 
					const file = Math.round((fsize / 1024)); 
					// The size of the file. 
					if (file > 60) { 
						return "Picture should be less than or equal to 60KB\n"; 
					} 
					else
					{ 
						return "";
					} 
				} 
			} 	
			return "";
		}
		function validatePicture1()	//validating the size of picture at update of a student to include his/her passport
		{
			const fi = document.getElementById('file1');
			if (fi.files.length > 0) { 
				for (const i = 0; i <= fi.files.length - 1; i++) 
				{ 
					const fsize = fi.files.item(i).size; 
					const file = Math.round((fsize / 1024)); 
					// The size of the file. 
					if (file >= 100) { 
						return "Picture should be greater than 100KB\n"; 
					} 
					else
					{ 
						return "";
					} 
				} 
			} 	
		}
		function validatePicture3()	//validating the size of picture at update of a student to update his/her passport
		{
			const fi = document.getElementById('file3');
			if (fi.files.length > 0) { 
				for (const i = 0; i <= fi.files.length - 1; i++) 
				{ 
					const fsize = fi.files.item(i).size; 
					const file = Math.round((fsize / 1024)); 
					// The size of the file. 
					if (file >= 100) { 
						return "Picture should be greater than 100KB\n"; 
					} 
					else
					{ 
						return "";
					} 
				} 
			} 	
		}
		function validate(form) 
		{
			fail =validateEmail(form.studentEmail.value)
			fail +=validatePassport(form.file.value)
			
			
			if (fail == ""){
				return true;
			}
			else { $('.message2').html(fail); return false }
		}
		
			function validateEmail(field)
			{
				var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
				if(field.match(mailformat)) return ""
						return "<i class='fa fa-info-circle'></i> Please enter valid email of student.\n"
			}
			function validatePassport(field){
				if (validatePicture() !== "") return "<i class='fa fa-info-circle'></i> Passport size should be less than or equal to 60KB.\n"
					return ""
			}
			
		//for validating picture upload form if a picture was not initially uploadedfunction validate(form) 
		function validate1(form) 
		{
			fail =validatePassport1(form.file1.value)
			
			
			
			if (fail == ""){
				return true;
			}
			else { $('.message5').html(fail); return false }
		}
			function validatePassport1(field){
				if (validatePicture1() !== "") return "<i class='fa fa-info-circle'></i> Passport size should be less than or equal to 60KB.\n"
					return ""
			}
			
		//for validating picture update of picureupload form if a picture
		function validate3(form) 
		{
			fail =validatePassport3(form.file3.value)
			
			
			
			if (fail == ""){
				return true;
			}
			else { $('.message6').html(fail); return false }
		}
			function validatePassport3(field){
				if (validatePicture3() !== "") return "<i class='fa fa-info-circle'></i> Passport size should be less than or equal to 60KB.\n"
					return ""
			}
	</script>
	<style>
		.modal1, .modal4, .modal5 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 5000; /* Ensure modal overlays all content */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }
			
		.modal2{
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 5001; /* Above other overlays */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }
		.modal3{
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 5001; /* Above other overlays */
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
            position: relative;
            z-index: 5100; /* Ensure content is above overlay */
            }
			.modal-content5 {
            border-radius:7px;
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            color:black;
            }

			/* Criteria modals sit on a pale body that previously forced table text white. */
			#myModal5 .dataTables_wrapper,
			#myModal6 .dataTables_wrapper,
			#myModal5 .table-responsive,
			#myModal6 .table-responsive,
			#myModal5 table,
			#myModal6 table,
			#myModal5 table th,
			#myModal6 table th,
			#myModal5 table td,
			#myModal6 table td {
				color: #1f2933;
			}

			#myModal5 table.dataTable thead th,
			#myModal6 table.dataTable thead th {
				background: #f3f6f8;
				color: #111827;
			}

			#myModal5 .dataTables_filter label,
			#myModal6 .dataTables_filter label,
			#myModal5 .dataTables_info,
			#myModal6 .dataTables_info,
			#myModal5 .dataTables_paginate,
			#myModal6 .dataTables_paginate {
				color: #1f2933 !important;
			}

			#deleteStudentsPanel {
				clear: both;
				color: #1f2933;
				margin-top: 18px;
			}

			#deleteStudentsPanel .table-responsive {
				background: #ffffff;
				border: 1px solid #d9e2dd;
				border-radius: 4px;
				max-height: 430px;
				overflow: auto;
			}

			#deleteStudentsTable {
				background: #ffffff;
				color: #1f2933;
				margin-bottom: 0;
			}

			#deleteStudentsTable th {
				background: #f3f6f8;
				color: #111827;
				position: sticky;
				top: 0;
				z-index: 1;
			}

			#deleteStudentsTable td {
				color: #1f2933;
				vertical-align: middle;
			}

			#deleteStudentsTable .empty-row td {
				color: #6b7280;
				padding: 24px;
				text-align: center;
			}

			#deleteStudentsActions {
				clear: both;
				margin-top: 12px;
			}

			#viewStudentsPanel {
				clear: both;
				color: #1f2933;
				margin-top: 18px;
			}

			#viewStudentsPanel .table-responsive {
				background: #ffffff;
				border: 1px solid #d9e2dd;
				border-radius: 4px;
				max-height: 430px;
				overflow: auto;
			}

			#viewStudentsTable {
				background: #ffffff;
				color: #1f2933;
				margin-bottom: 0;
			}

			#viewStudentsTable th {
				background: #f3f6f8;
				color: #111827;
				position: sticky;
				top: 0;
				z-index: 1;
			}

			#viewStudentsTable td {
				color: #1f2933;
				vertical-align: middle;
			}

			#viewStudentsTable .empty-row td {
				color: #6b7280;
				padding: 24px;
				text-align: center;
			}

            /* The Close Button */
            .close1{
            color: #ffffff;
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add new Student</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Students</li>
						<a href="javascript:void(0)" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
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
								<form method="post" action="addStudent.php" onSubmit="return validate(this)" enctype="multipart/form-data">
									<!-- Display status message -->
									<?php if(!empty($statusMsg)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType; ?>"><a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg; ?></div>
										</div>
									<?php } ?>
									<?php if(!empty($statusMsg1)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType1; ?>"><a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg1; ?></div>
										</div>
									<?php } ?>
									<?php if(!empty($statusMsg2)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType2; ?>"><a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg2; ?></div>
										</div>
									<?php } ?>
									<?php if(!empty($statusMsg3)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType3; ?>"><a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg3; ?></div>
										</div>
									<?php } ?>
									<label>Note: Fields marked <span style="color:red; font-size:18px;"><b>*</b></span> are required</label>
									<div class="form-group">
										<label>Surname of Student (e.g. <em>Maiyaki</em>) <span style="color:red; font-size:18px;">*</span></label>
										<input type="text" name="surname" class="form-control" placeholder="Enter Surname (e.g. Maiyaki)" required >
									</div>
									<div class="form-group">
										<label>First name <span style="color:red; font-size:18px;">*</span></label>
										<input type="text" name="firstName" id="firstName" class="form-control" placeholder="Enter first name of student">
									</div>
									<div class="form-group">
										<label>Middle name (<i>optional</i>)</label>
										<input type="text" name="middleName" id="middleName"  class="form-control" placeholder="Enter middle name of student">
									</div>
									<div class="form-group">
										<label>Gender <span style="color:red; font-size:18px;">*</span></label>
										<select class="form-control m-bot15" name="gender" id="gender" required >
											<option value="">... Select gender ...</option>
											<option value="Male">Male</option>
											<option value="Female">Female</option>
										</select>
									</div>
									<div class="form-group">
										<label>Admission No. (e.g. <em>dlhs2017001</em>) <span style="color:red; font-size:18px;">*</span></label>
										<input type="text" name="admissionNumber" class="form-control" placeholder="Enter Admission no. of student" required >
									</div>
									<div class="form-group">
										<label>Email <span style="color:red; font-size:18px;">*</span></label>
										<input type="email" name="studentEmail" class="form-control" placeholder="Enter student email" required >
									</div>
									
									<div class="form-group">
										<label>Password <span style="color:red; font-size:18px;">*</span></label>
										<input type="password" name="password" class="form-control" placeholder="Enter password of student" required >
									</div>
									<div class="form-group">
										<label>Year group <span style="color:red; font-size:18px;">*</span></label>
										<select class="form-control m-bot15" name="yeargroup" id="yeargroup" onchange="getClasses()" required >
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
									<div class="form-group">
										<label>Class <span style="color:red; font-size:18px;">*</span></label>
										<select class="form-control" name="className" id="className" required >
												
										</select>
									</div>
									<div class="form-group">
										<label>Image of Student <i>(optional)</i></label>
										<input type="file" name="file" id="file" accept="image/png, image/jpeg" class="form-control">
									</div>
									<button type="submit" class="btn btn-primary" id="submit"><i class="fa fa-sign-in"></i> Submit</button><br>
									<div class="message1" style="color:green; font-size:17px;" align="center"></div><div class="message2" id="message2" style="color:red;" align="center"></div><br>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-12" id="tableColumn">
						<section class="panel">
							<header class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
								<span>View | Edit | <a href="javascript:void(0)" onclick="openDeleteStudentsModal()" style="cursor:pointer;">Delete student based on criteria</a> | <a href="javascript:void(0)" onclick="openUploadCsvModal()" style="cursor:pointer;">Click to upload CSV of student's records</a> | <a href="javascript:void(0)" onclick="downloadCsvTemplate()" style="cursor:pointer; color:#28a745;"><i class="fa fa-download"></i> Download CSV Template</a> | <a href="javascript:void(0)" onclick="openViewStudentsBasedOnCriteria()" style="cursor:pointer;">Click to view students based on criteria</a></span>
								<button class="btn btn-success btn-sm" id="toggleFormBtn" onclick="dlhsToggleCreateForm()" style="font-weight: 700; border-radius: 6px; padding: 6px 12px;"><i class="fa fa-plus"></i> Add New Student</button>
								<div class="message8" style="color:green; font-size:17px;" align="center"></div>
							</header>
							<div class="panel-body">
								<div class="message3" style="color:green; font-size:17px;" align="center"></div><div class="message4" style="color:red;" align="center"></div>
								<div class="table-responsive">
								
											<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
												<thead>
													<tr>
														<th width='2%'>S/NO</th>
														<th>SURNAME</th>
														<th>FIRST NAME</th>
														<th>MIDDLE NAME</th>
														<th>GENDER</th>
														<th>ADMISSION NO</th>
														<th>CLASS</th>
														<th>EMAIL</th>
														<th>PASSPORT</th>
														<th>ACTION</th>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Update Student's record</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" id="updateStudentForm">
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student Surname</span> <span style="color:red; font-size:18px;">*</span>></label><br>
									<input type="hidden" name="studentId" required="required" id="studentId">
									<input type="text" name="surname1" required="required" id="surname1" class="form-control">
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student First name: </span> <span style="color:red; font-size:18px;">*</span></label><br>
									<input type="text" name="firstName1" required="required" id="firstName1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student Middle name: </span></label><br>
									<input type="text" name="middleName1" id="middleName1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student Gender: </span> <span style="color:red; font-size:18px;">*</span></label><br>
									<select class="form-control m-bot15" name="gender1" id="gender1" required >
										<option value="">... Select gender ...</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student Admission number: </span> <span style="color:red; font-size:18px;">*</span></label><br>
									<input type="text" name="studentAdmissionNo1" required="required" id="studentAdmissionNo1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student Email: </span> <span style="color:red; font-size:18px;">*</span></label><br>
									<input type="text" name="studentEmail1" required="required" id="studentEmail1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Student Password: </span> <span style="color:red; font-size:18px;">*</span></label><br>
									<input type="text" name="password1" required="required" id="password1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label style="color:#000000;">Year group <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control m-bot15" name="yeargroup1" id="yeargroup1" onchange="getClasses1()" required >
										<option value="">... Select Year group ...</option>
										<?php
											$yeargroups3="select * from yeargroup";
											$result3 = $connection->query($yeargroups3);
											while($row3 = $result3->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row3[0]; ?>"><?php echo $row3[1]; ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label style="color:#000000;">Class <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control" name="className1" id="className1" required >
										<option value="">... Select Class ...</option>
										<?php
											$studentClass="SELECT * FROM classes";
											$result2 = $connection->query($studentClass);
											while($row2 = $result2->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row2[0]; ?>"><?php echo $row2[2]; ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="button" class="btn btn-primary" onclick="updateStudentData()">Update</button>
								</div>
							</div>
                        </form>
						<div class="message7" id="message7" style="color:red;" align="center"></div>
                    </div>
						<br>
						<br>
						<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of edit modal-->
			
			<div id="myModal2" class="modal2"> <!--Start of Edit modal-->
				<div class="modal-content">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Upload student picture</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" action="addStudentPicture.php" onSubmit="return validate1(this)" enctype="multipart/form-data">
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Upload picture of <font id='toHoldStudentName'></font></span> <span class="required">*</span></label><br>
									<input type="hidden" name="setStudentId" required="required" id="setStudentId">
									<input type="file" name="file1" id="file1" accept="image/png, image/jpeg" class="form-control" required>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="submit" class="btn btn-primary" name="updateStudentPicture"><i class="fa fa-sign-in"></i> Upload</button><br><br>
								</div>
							</div>
                        </form>
						<div class="message5" id="message5" style="color:red;" align="center"></div>
                    </div>
						<br>
						<br>
						<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of edit modal-->
			
			<div id="myModal3" class="modal3"> <!--Start of Edit modal-->
				<div class="modal-content">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Update student picture</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" action="updateStudentPicture.php" onSubmit="return validate3(this)" enctype="multipart/form-data">
                            <div class="item form-group">
								<div class="col-md-12" id='toPreviewStudentPicture'>
									
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Upload picture of <font id='toHoldStudentName3'></font></span> <span class="required">*</span></label><br>
									<input type="hidden" name="setStudentId3" required="required" id="setStudentId3">
									<input type="file" name="file3" id="file3" accept="image/png, image/jpeg" class="form-control" required>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="submit" class="btn btn-primary" name="updateStudentPicture3"><i class="fa fa-sign-in"></i> Upload</button><br><br>
								</div>
							</div>
                        </form>
						<div class="message6" id="message6" style="color:red;" align="center"></div>
                    </div>
						<br>
						<br>
						<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of edit modal-->
			
			<div id="myModal4" class="modal4"> <!--Start of Edit modal-->
				<div class="modal-content">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Upload CSV file of students' records</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" action="uploadCsvStudentsRecords.php" enctype="multipart/form-data">
                            <div class="item form-group">
								<div class="col-md-12">
									<label for="yearGroup4" style="color:#000000;">Year group <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control m-bot15" name="yearGroup4" id="yearGroup4" onchange="getClasses4()" required >
										<option value="">... Select Year group ...</option>
										<?php
											$yeargroups4="SELECT * FROM yeargroup";
											$result4 = $connection->query($yeargroups4);
											while($row4 = $result4->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row4[0]; ?>"><?php echo $row4[1]; ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label for="className4" style="color:#000000;">Class <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control" name="className4" id="className4" required >
										
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label for="file4"><span style="color:#000000;">Select CSV file of students' records<font id='toHoldStudentName3'></font></span> <span style="color:red; font-size:18px;">*</span></label><br>
									
									<input type="file" name="file4" id="file4" accept=".xlsx, .xls, .csv" class="form-control" required>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i> Upload</button><br><br>
								</div>
							</div>
                        </form>
                    </div>
						<br>
						<br>
						<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of edit modal-->
			
			<div id="myModal5" class="modal5"> <!--Start of modal to view students based on selected criteria-->
				<div class="modal-content5">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">View students based on selected criteria</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form>
                            <div class="item form-group">
								<div class="col-md-6">
									<label for="yearGroup5" style="color:#000000;">Year group <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control m-bot15" name="yearGroup5" id="yearGroup5" onchange="getClasses5()" required >
										<option value="">... Select Year group ...</option>
										<?php
											$yeargroups5="SELECT * FROM yeargroup";
											$result5 = $connection->query($yeargroups5);
											while($row5 = $result5->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row5[0]; ?>"><?php echo $row5[1]; ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="col-md-6">
									<label for="className5" style="color:#000000;">Arm <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control" name="className5" id="className5" onchange="viewStudentsBasedOnCriteria()" required >
										
									</select>
								</div>
								<div class="col-md-12" style="margin-top:8px;">
									<div class="message9" style="color:red; font-size:14px;"></div>
								</div>
							</div>
							<div class="item form-group" id="viewStudentsPanel">
								<div class="table-responsive">
									<table id="viewStudentsTable" class="table table-striped table-bordered" style="width:100%;">
										<thead>
											<tr>
												<th width='3%' style='width:40px;max-width:50px;'>S/NO</th>
												<th width='9%'>SURNAME</th>
												<th width='11%'>FIRST NAME</th>
												<th width='11%'>MIDDLE NAME</th>
												<th width='7%'>GENDER</th>
												<th width='12%'>ADMISSION NO</th>
												<th width='10%'>ARM</th>
												<th width='15%'>EMAIL</th>
												<th width='9%'>PASSPORT</th>
												<th width='11%'><input type='checkbox' id='checkUncheckAll' onClick='CheckUncheckAll()' /> &nbsp; Select all</th>
											</tr>
										</thead>
										<tbody>
											<tr class="empty-row">
												<td colspan="10">Select a year group and arm to list students.</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="col-md-6" id="theClass">
									<label style="color:#000000;">Select class to promote/demote selected student(s) to: <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control" name="classes6" id="classes6" required >
										<option value="">... Select class ...</option>
										<?php
											$classes6="SELECT * FROM classes";
											$result6 = $connection->query($classes6);
											if($result6->num_rows > 0)
											{
												while($row6 = $result6->fetch_array(MYSQLI_NUM))
												{
													$theClassName=$row6[2];
													$theClassYearGroupId=$row6[1];
													
													//Getting the year group name
													$classes61="SELECT * FROM yeargroup WHERE yearGroupId='$theClassYearGroupId'";
													$result61 = $connection->query($classes61);
													$row61 = $result61->fetch_array(MYSQLI_NUM);
										?>
										<option value="<?php echo $row6[0]; ?>"><?php echo $row61[1].' '.$theClassName; ?></option>
										<?php } }?>
									</select><br>
									<button type='button' onClick='getChecked()' class='btn btn-primary'><i class='fa fa-sign-in'></i> Add Students to selected class</button><br>
									<div class="message10" style="color:red; font-size:17px;"></div>
								</div>
							</div>
							<!-- End of table-responsive -->
							<div class="item form-group" >
								
							</div><br><br>
						</form>
                    </div>
					<br>
					<br>
					<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of modal to view students based on selected criteria-->
			
			<div id="myModal7" class="modal1"> <!--Start of modal to delete individual student-->
				<div class="modal-content">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#dc3545;"><center style="font-size:22px;">Delete Student</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <div class="item form-group">
							<div class="col-md-12">
								<div style="text-align: center; margin-bottom: 20px;">
									<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
									<h4 style="color: #000000; margin-bottom: 15px;">Confirm Student Deletion</h4>
									<p style="color: #000000; font-size: 16px; margin-bottom: 20px;">
										Are you sure you want to delete <strong id="studentToDeleteName"></strong>?
									</p>
									<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
										<h5 style="color: #856404; margin-bottom: 10px;"><i class="fa fa-warning"></i> Warning</h5>
										<p style="color: #856404; margin: 0; font-size: 14px;">
											This action cannot be undone! This will permanently delete:
										</p>
										<ul style="color: #856404; margin: 10px 0 0 20px; font-size: 14px;">
											<li>Student record from the database</li>
											<li>Associated passport photo</li>
											<li>All related academic data</li>
										</ul>
									</div>
									<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
										<h5 style="color: #721c24; margin-bottom: 10px;"><i class="fa fa-shield"></i> Security Confirmation</h5>
										<p style="color: #721c24; margin: 0 0 10px 0; font-size: 14px;">
											To confirm deletion, please type the following code:
										</p>
										<div style="background-color: #ffffff; border: 2px solid #721c24; border-radius: 3px; padding: 10px; margin-bottom: 10px; text-align: center;">
											<strong id="confirmationCode" style="font-size: 18px; color: #721c24; letter-spacing: 2px;"></strong>
										</div>
										<input type="text" id="userConfirmationCode" class="form-control" placeholder="Type the confirmation code here" style="margin-bottom: 10px;" autocomplete="off">
										<small style="color: #721c24; font-size: 12px;">
											<i class="fa fa-info-circle"></i> The code is case-sensitive and expires in 5 minutes
										</small>
									</div>
									<input type="hidden" id="studentToDeleteId" value="">
									<input type="hidden" id="confirmationCodeExpiry" value="">
									<button type="button" class="btn btn-danger" onclick="confirmDeleteStudent()" id="deleteConfirmBtn" disabled style="margin-right: 10px;">
										<i class="fa fa-trash"></i> Yes, Delete Student
									</button>
									<button type="button" class="btn btn-secondary" onclick="closeDeleteStudentModal()">
										<i class="fa fa-times"></i> Cancel
									</button>
								</div>
							</div>
						</div>
						<div class="message12" id="message12" style="color:red; font-size:17px; text-align: center;"></div>
                    </div>
					<br>
					<br>
					<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of modal to delete individual student-->
			
			<div id="myModal8" class="modal1"> <!--Start of modal to confirm bulk delete-->
				<div class="modal-content">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#dc3545;"><center style="font-size:22px;">Confirm Bulk Student Deletion</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <div class="item form-group">
							<div class="col-md-12">
								<div style="text-align: center; margin-bottom: 20px;">
									<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
									<h4 style="color: #000000; margin-bottom: 15px;">Confirm Bulk Student Deletion</h4>
									<p style="color: #000000; font-size: 16px; margin-bottom: 20px;">
										Are you sure you want to delete <strong id="bulkStudentCount"></strong> selected student(s)?
									</p>
                                    <div id="selectedStudentsList" style="display:none;"></div>
									<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
										<h5 style="color: #856404; margin-bottom: 10px;"><i class="fa fa-warning"></i> Warning</h5>
										<p style="color: #856404; margin: 0; font-size: 14px;">
											This action cannot be undone! This will permanently delete:
										</p>
										<ul style="color: #856404; margin: 10px 0 0 20px; font-size: 14px;">
											<li>All selected student records from the database</li>
											<li>Associated passport photos</li>
											<li>All related academic data</li>
										</ul>
									</div>
									<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
										<h5 style="color: #721c24; margin-bottom: 10px;"><i class="fa fa-shield"></i> Security Confirmation</h5>
										<p style="color: #721c24; margin: 0 0 10px 0; font-size: 14px;">
											To confirm bulk deletion, please type the following code:
										</p>
										<div style="background-color: #ffffff; border: 2px solid #721c24; border-radius: 3px; padding: 10px; margin-bottom: 10px; text-align: center;">
											<strong id="bulkConfirmationCode" style="font-size: 18px; color: #721c24; letter-spacing: 2px;"></strong>
										</div>
										<input type="text" id="bulkUserConfirmationCode" class="form-control" placeholder="Type the confirmation code here" style="margin-bottom: 10px;" autocomplete="off">
										<small style="color: #721c24; font-size: 12px;">
											<i class="fa fa-info-circle"></i> The code is case-sensitive and expires in 5 minutes
										</small>
									</div>
									<input type="hidden" id="bulkStudentIds" value="">
									<input type="hidden" id="bulkConfirmationCodeExpiry" value="">
									<button type="button" class="btn btn-danger" onclick="confirmBulkDeleteStudents()" id="bulkDeleteConfirmBtn" disabled style="margin-right: 10px;">
										<i class="fa fa-trash"></i> Yes, Delete All Selected Students
									</button>
									<button type="button" class="btn btn-secondary" onclick="closeBulkDeleteModal()">
										<i class="fa fa-times"></i> Cancel
									</button>
								</div>
							</div>
						</div>
						<div class="message13" id="message13" style="color:red; font-size:17px; text-align: center;"></div>
                    </div>
					<br>
					<br>
					<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of modal to confirm bulk delete-->
			
			<div id="myModal6" class="modal5"> <!--Start of modal to delete students based on selected criteria-->
				<div class="modal-content5">
					<span class="close1">&times;</span>
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#dc3545;"><center style="font-size:22px;">Delete students based on selected criteria</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form>
                            <div class="item form-group">
								<div class="col-md-6">
									<label style="color:#000000;">Year group <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control m-bot15" name="yearGroup6" id="yearGroup6" onchange="getClasses6()" required >
										<option value="">... Select Year group ...</option>
										<?php
											$yeargroups6="SELECT * FROM yeargroup";
											$result6 = $connection->query($yeargroups6);
											while($row6 = $result6->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row6[0]; ?>"><?php echo $row6[1]; ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="col-md-6">
									<label style="color:#000000;">Arm <span style="color:red; font-size:18px;">*</span></label>
									<select class="form-control" name="className6" id="className6" onchange="viewStudentsForDeletion()" required >
										
									</select>
								</div>
							</div>
							<div class="item form-group" id="deleteStudentsPanel">
								<div class="table-responsive">
									<table id="deleteStudentsTable" class="table table-striped table-bordered" style="width:100%;">
										<thead>
											<tr>
												<th width='5%'>S/NO</th>
												<th width='15%'>SURNAME</th>
												<th width='15%'>FIRST NAME</th>
												<th width='15%'>MIDDLE NAME</th>
												<th width='8%'>GENDER</th>
												<th width='14%'>ADMISSION NO</th>
												<th width='15%'>ARM</th>
												<th width='13%'><input type='checkbox' id='checkUncheckAll2' onClick='CheckUncheckAll2()' /> &nbsp; Select all</th>
											</tr>
										</thead>
										<tbody>
											<tr class="empty-row">
												<td colspan="8">Select a year group and arm to list students for deletion.</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="col-md-12" id="deleteStudentsActions">
									<button type='button' onClick='deleteSelectedStudents()' class='btn btn-danger' id='deleteSelectedBtn' disabled><i class='fa fa-trash'></i> Delete selected students (<span id='selectedCount'>0</span>)</button><br>
									<div class="message11" style="color:red; font-size:17px;"></div>
								</div>
							</div>
							<!-- End of table-responsive -->
							<div class="item form-group" >
								
							</div><br><br>
						</form>
                    </div>
					<br>
					<br>
					<hr>
				</div>	<!-- End of modal content-->
			</div><!-- End of modal to delete students based on selected criteria-->
			
			<!-- Toast Notification Container -->
			<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
			
			<div class="text-right">
				<div class="credits">
					<?php include "footer.php"; ?>
				</div>
			</div>
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
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script>
		//function to check if a picture of the student was uploaded before or not.
		function callPictureCheck(studentId, studentPassport, firstName, surname)
		{
			if(studentPassport == "")
			{
				return "<a href='javascript:void(0)' onclick='openToUploadPic(\""+studentId+"\",\""+firstName+"\",\""+surname+"\",\""+studentPassport+"\")' style='cursor: pointer;' title='Click to upload passport of this student'>Upload Picture</a>";
			}
			else
			{
				return "<a href='javascript:void(0)' onclick='openToUpdatePicture(\""+studentId+"\",\""+firstName+"\",\""+surname+"\",\""+studentPassport+"\")' style='cursor:pointer;' title='Click to update picture'><img src='studentPassports/"+studentPassport+"' style='width:50px; height:60px;'/></a>";
			}
		}
		
		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getStudentsRecords.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						//alert(response)
						var len = response.length;
						
						for(var i=0; i<len; i++){
							var studentId = response[i].studentId;
							var surname = response[i].surname;
							var firstName = response[i].firstName;						
							var middleName = response[i].middleName;	
							var gender = response[i].gender;	
							var admissionNumber = response[i].admissionNumber;	
							var studentClassName = response[i].studentClassName;	
							var studentEmail = response[i].studentEmail;	
							var studentPassword = response[i].studentPassword;	
							var studentYearGroupId = response[i].studentYearGroupId;	
							var studentClassId = response[i].studentClassId;	
							var studentPassport = response[i].studentPassport;	
							var studentFullName = surname+" "+firstName+" "+middleName;
							
							var tr_str = "<tr>" +
								"<td>" + (i+1) + "</td>" +
								"<td>" + surname + "</td>" +
								"<td>" + firstName + "</td>" +
								"<td>" + middleName + "</td>" +
								"<td>" + gender + "</td>" +
								"<td>" + admissionNumber + "</td>" +
								"<td>" + studentClassName + "</td>" +
								"<td>" + studentEmail + "</td>" +
								"<td><center>" + callPictureCheck(studentId, studentPassport, firstName, surname) + "</center></td>" +
								"<td align='center'><a href='javascript:void(0)' onClick='openEditModal(\""+studentId+"\",\""+surname+"\",\""+firstName+"\",\""+middleName+"\",\""+gender+"\",\""+admissionNumber+"\",\""+studentEmail+"\",\""+studentPassword+"\",\""+studentYearGroupId+"\",\""+studentClassId+"\")' title='Edit this student record' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a>&nbsp; &nbsp; &nbsp; &nbsp;<a href='javascript:void(0)' onClick='deleteStudent(\""+studentId+"\",\""+studentFullName+"\")' title='Delete this student' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
						"responsive": true
						});
					}
			});
		}
		//calling the data table function
		callTable();
		
		//Delete individual student
		function deleteStudent(studentId, studentName)
		{
			$('.message8').html("");
			$('.message2').html("");
			$('.message12').html("");
			
			// Open the delete confirmation modal
			openDeleteStudentModal(studentId, studentName);
		}
		
		//Open modal to delete individual student
		function openDeleteStudentModal(studentId, studentName)
		{
			var modal7 = document.getElementById("myModal7");
			document.getElementById("studentToDeleteId").value = studentId;
			document.getElementById("studentToDeleteName").textContent = studentName;
			
			// Generate dynamic confirmation code
			generateConfirmationCode();
			
			modal7.style.display = "block";
		}
		
		//Generate dynamic confirmation code
		function generateConfirmationCode()
		{
			// Generate a 6-character alphanumeric code
			var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			var code = '';
			for (var i = 0; i < 6; i++) {
				code += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			
			// Set the confirmation code
			document.getElementById("confirmationCode").textContent = code;
			
			// Set expiry time (5 minutes from now)
			var expiryTime = new Date().getTime() + (5 * 60 * 1000); // 5 minutes
			document.getElementById("confirmationCodeExpiry").value = expiryTime;
			
			// Clear user input and disable button
			document.getElementById("userConfirmationCode").value = '';
			document.getElementById("deleteConfirmBtn").disabled = true;
			
			// Add event listener for real-time validation
			document.getElementById("userConfirmationCode").addEventListener('input', validateConfirmationCode);
			
			// Start countdown timer
			startCountdownTimer();
		}
		
		//Validate confirmation code in real-time
		function validateConfirmationCode()
		{
			var userCode = document.getElementById("userConfirmationCode").value.toUpperCase();
			var expectedCode = document.getElementById("confirmationCode").textContent;
			var expiryTime = parseInt(document.getElementById("confirmationCodeExpiry").value);
			var currentTime = new Date().getTime();
			var deleteBtn = document.getElementById("deleteConfirmBtn");
			
			// Check if code has expired
			if (currentTime > expiryTime) {
				deleteBtn.disabled = true;
				showCodeExpiredMessage();
				return;
			}
			
			// Check if codes match
			if (userCode === expectedCode) {
				deleteBtn.disabled = false;
				document.getElementById("userConfirmationCode").style.borderColor = "#28a745";
				document.getElementById("userConfirmationCode").style.backgroundColor = "#d4edda";
			} else {
				deleteBtn.disabled = true;
				document.getElementById("userConfirmationCode").style.borderColor = "#dc3545";
				document.getElementById("userConfirmationCode").style.backgroundColor = "#f8d7da";
			}
		}
		
		//Show code expired message
		function showCodeExpiredMessage()
		{
			document.getElementById("userConfirmationCode").style.borderColor = "#dc3545";
			document.getElementById("userConfirmationCode").style.backgroundColor = "#f8d7da";
			document.getElementById("userConfirmationCode").placeholder = "Code expired! Please refresh the modal.";
			document.getElementById("userConfirmationCode").disabled = true;
		}
		
		//Start countdown timer
		function startCountdownTimer()
		{
			var expiryTime = parseInt(document.getElementById("confirmationCodeExpiry").value);
			var timerInterval = setInterval(function() {
				var currentTime = new Date().getTime();
				var timeLeft = expiryTime - currentTime;
				
				if (timeLeft <= 0) {
					clearInterval(timerInterval);
					showCodeExpiredMessage();
					return;
				}
				
				var minutes = Math.floor(timeLeft / (1000 * 60));
				var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
				
				// Update timer display (you can add a timer element if needed)
				// For now, we'll just check expiry in validateConfirmationCode
			}, 1000);
		}
		
		//Close modal to delete individual student
		function closeDeleteStudentModal()
		{
			var modal7 = document.getElementById("myModal7");
			modal7.style.display = "none";
			$('.message12').html("");
			
			// Reset form elements
			document.getElementById("userConfirmationCode").value = '';
			document.getElementById("userConfirmationCode").disabled = false;
			document.getElementById("userConfirmationCode").placeholder = "Type the confirmation code here";
			document.getElementById("userConfirmationCode").style.borderColor = "";
			document.getElementById("userConfirmationCode").style.backgroundColor = "";
			document.getElementById("deleteConfirmBtn").disabled = true;
			
			// Clear confirmation code
			document.getElementById("confirmationCode").textContent = '';
			document.getElementById("confirmationCodeExpiry").value = '';
		}
		
		//Confirm deletion of individual student
		function confirmDeleteStudent()
		{
			$('.message12').html("");
			
			var studentId = document.getElementById("studentToDeleteId").value;
			var studentName = document.getElementById("studentToDeleteName").textContent;
			var userCode = document.getElementById("userConfirmationCode").value.toUpperCase();
			var expectedCode = document.getElementById("confirmationCode").textContent;
			var expiryTime = parseInt(document.getElementById("confirmationCodeExpiry").value);
			var currentTime = new Date().getTime();
			
			// Validate confirmation code
			if (!studentId) {
				$('.message12').html('<i class="fa fa-exclamation-triangle"></i> Error: Student ID not found.');
				return;
			}
			
			if (!userCode) {
				$('.message12').html('<i class="fa fa-exclamation-triangle"></i> Please enter the confirmation code.');
				return;
			}
			
			if (currentTime > expiryTime) {
				$('.message12').html('<i class="fa fa-exclamation-triangle"></i> Confirmation code has expired. Please refresh the modal.');
				return;
			}
			
			if (userCode !== expectedCode) {
				$('.message12').html('<i class="fa fa-exclamation-triangle"></i> Invalid confirmation code. Please check and try again.');
				return;
			}
			
			// Show loading message
			$('.message12').html('<i class="fa fa-spinner fa-spin"></i> Deleting student, please wait...');
			
			//organize the data properly
			var form_data = 'studentId=' + encodeURIComponent(studentId);

			$.ajax({
				url: "deleteStudent.php",
				type: "POST",     
				data: form_data,
				timeout: 15000, // 15 second timeout
				success: function (html) {             
					if (html == 0)	//If session is expired.
					{                              
						window.location.replace("logout.php");
					}
					else if (html == 1)	//If student successfully deleted
					{                              
						showToast('success', 'Deletion Successful', studentName + ' has been successfully deleted from the system.');
						$('#example').DataTable().clear().destroy();
						callTable();
						// Close the modal after successful deletion
						setTimeout(function() {
							closeDeleteStudentModal();
						}, 2000);
					}
					else if (html == 2)	//If deletion is unsuccessful	
					{                              
						showToast('error', 'Deletion Failed', 'Could not delete student. Please try again.');
					}
					else if (html == 3)	//If passport file deletion failed
					{                              
						showToast('error', 'Partial Success', 'Student deleted but passport file could not be removed. Please check file permissions.');
					}
					else 	//If unexpected error	
					{                              
						showToast('error', 'Error', 'An unexpected error occurred. Please try again.');
					}
				},
				error: function(xhr, status, error) {
					if (status === 'timeout') {
						showToast('error', 'Timeout Error', 'The deletion request timed out. Please try again.');
					} else {
						showToast('error', 'Network Error', 'An error occurred while deleting the student. Please check your connection and try again.');
					}
					$('.message12').html('');
				}
			});
		}
		
		// HTML order of .close1 elements:
		// [0]=myModal1, [1]=myModal2, [2]=myModal3, [3]=myModal4,
		// [4]=myModal5(view criteria), [5]=myModal7(del individual),
		// [6]=myModal8(bulk delete confirm), [7]=myModal6(del by criteria)
		
		// Handle modal close events for individual delete modal (index 5)
		var span7 = document.getElementsByClassName("close1")[5];
		if (span7) {
			span7.onclick = function() {
				closeDeleteStudentModal();
			};
		}
		
		// Handle modal close events for bulk delete modal (index 6)
		var span8 = document.getElementsByClassName("close1")[6];
		if (span8) {
			span8.onclick = function() {
				closeBulkDeleteModal();
			};
		}
		
		function editYearGroupName()
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
					
			var yearGroupId = $('input[name=yearGroupId]').val();
			var newYearGroupName = $('input[name=YearGroupName1]').val();
			
			if (newYearGroupName=="")
			{
				$('.message3').html('Please enter name of Year group')
			}
			else
			{
				var form_data = 
				  'yearGroupId='+yearGroupId+
				  '&newYearGroupName='+newYearGroupName;
				  
				$.ajax({
					url: "editYearGroup.php",
					type: "POST",       
					data: form_data,    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message3').html("");
							$('.message1').html('<i class="fa fa-check"></i> successfully edited to ' + newYearGroupName).fadeIn('slow');
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
							$('.message3').html('<i class="fa fa-times"></i> The Year group ' + newYearGroupName + ' already exists. Please enter another Year Group name.').fadeIn('slow');
						}
					}
				});
			}
		}
		
		//launching a modal to edit a student
		function openEditModal(studentId, surname, firstName, middleName, gender, admissionNumber, studentEmail, password, studentYearGroupId, studentClassId)
        {
			$('.message7').html("");
			$('.message8').html("");
            var modal1 = document.getElementById("myModal1");
			document.getElementById("studentId").value=studentId;
			document.getElementById("surname1").value=surname;
            document.getElementById("firstName1").value=firstName;       
            document.getElementById("middleName1").value=middleName;       
            $("#gender1").val(gender);       
            document.getElementById("studentAdmissionNo1").value=admissionNumber;       
            document.getElementById("studentEmail1").value=studentEmail;       
            document.getElementById("password1").value=password;
			$("#yeargroup1").val(studentYearGroupId);
			
			var theYearGroup = $("#yeargroup1 option:selected").text();
			$.ajax({
				type: "POST",
				url: "getClassToAddStudent.php",
				data: { yearGroup : studentYearGroupId, yearGroupName : theYearGroup } 
			}).done(function(data){
				$("#className1").html(data);
				$("#className1").val(studentClassId);
			});
                
            modal1.style.display = "block";
        }
		var closeSpans = document.getElementsByClassName("close1");
		for (var i = 0; i < closeSpans.length; i++) {
			closeSpans[i].onclick = function() {
				var modals = ['myModal1', 'myModal2', 'myModal3', 'myModal4', 'myModal5', 'myModal6', 'myModal7', 'myModal8', 'myModalBulkDelete', 'myModalToUpdatePic', 'myModalToUploadPic'];
				modals.forEach(function(mId) {
					var m = document.getElementById(mId);
					if (m) m.style.display = "none";
				});
			}
		}
        // Close modals handled by closeSpans loop above
            
        // Consolidated window click listener for all modals
        window.onclick = function(event) {
            var modals = document.querySelectorAll('.modal1, .modal2, .modal3, .modal4, .modal5, .modal6, .modal8');
            modals.forEach(function(modal) {
                if (event.target == modal) {
                    modal.style.display = "none";
                    // Restore body overflow if closing specific modals
                    if (modal.id === 'myModal8') {
                        document.documentElement.style.overflow = 'auto';
                        document.body.style.overflow = 'auto';
                    }
                }
            });
        }
        
        var modal1 = document.getElementById("myModal1");
		
		//function to open modal to upload picture
		function openToUploadPic(staffId, firstName, surname)
		{
			$('.message5').html("");
			var modal2 = document.getElementById("myModal2");
			document.getElementById("setStudentId").value=staffId;
			$('#toHoldStudentName').html(firstName + ' ' + surname);

			modal2.style.display = "block";
		}
		var span2 = document.getElementsByClassName("close1")[1];
            
        // When the user clicks on <span> (x), close the modal1
        span2.onclick = function() {
			modal2.style.display = "none";
        }
            
        var modal2 = document.getElementById("myModal2");
		
		//function to open modal to update picture
		function openToUpdatePicture(studentId, firstName, surname, studentPassport)
		{
			$('.message6').html("");
			var modal3 = document.getElementById("myModal3");
			document.getElementById("setStudentId3").value=studentId;
			$('#toHoldStudentName3').html(firstName + ' ' + surname);
			$('#toPreviewStudentPicture').html("<center><img src='studentPassports/"+studentPassport+"' style='width:160px; border:3px solid #202A44; border-radius:4px; padding:5px;'></center>");

			modal3.style.display = "block";
		}
		var span3 = document.getElementsByClassName("close1")[2];
            
        // When the user clicks on <span> (x), close the modal1
        span3.onclick = function() {
			modal3.style.display = "none";
        }
            
        var modal3 = document.getElementById("myModal3");
		
		//function declaration to update a student's data
		function updateStudentData()
		{
			$('.message8').html('');
			$('.message7').html('');
			$('.alert alert-success').html('');
					
			var studentIdToUpdate = $('input[name=studentId]').val();
			var surname = $('input[name=surname1]').val();
			var firstName = $('input[name=firstName1]').val();
			var middleName = $('input[name=middleName1]').val();
			var gender = document.getElementById('gender1').value;
			var studentAdmissionNo = $('input[name=studentAdmissionNo1]').val();
			var email = $('input[name=studentEmail1]').val();
			var thePassword = $('input[name=password1]').val();									
			var studentYearGroupId = document.getElementById('yeargroup1').value;			
			var studentClassId = document.getElementById('className1').value;		
										
			var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
						 		 
			if (surname=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter surname')
			}
			else if (firstName=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter first name')
			}
			else if (gender=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please select gender')
			}
			else if (studentAdmissionNo=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter Student Admission number')
			}
			else if (email.length < 1)
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter email')
			}
			else if(email.length > 1 && !email.match(mailformat))
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + " Please enter staff's valid email")
			}
			else if (thePassword=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter password')
			}
			else if (studentYearGroupId=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please select student year group')
			}
			else if (studentClassId=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please select student class')
			}
			else
			{									
				var form_data = 
				  'studentIdToUpdate='+studentIdToUpdate+
				  '&surname='+surname+
				  '&firstName='+firstName+
				  '&middleName='+middleName+
				  '&gender='+gender+
				  '&studentAdmissionNo='+studentAdmissionNo+
				  '&studentEmail='+email+
				  '&thePassword='+thePassword+
				  '&studentYearGroupId='+studentYearGroupId+
				  '&studentClassId='+studentClassId;
				
				$.ajax({
					url: "updateStudentData.php",
					type: "POST",    
					data: form_data,
											
					success: function (html) {   
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	//If student successfully added
						{                              
							$('.message7').html("");
							$('.message8').html('<i class="fa fa-check-circle"></i> Student data successfully updated.').fadeIn('slow');
							$("#updateStudentForm").trigger("reset");
							$('#example').DataTable().clear().destroy();
							callTable();
							var modal1 = document.getElementById("myModal1");
							modal1.style.display = "none";
						}
						else if (html==2)	//If insertion is unsuccessful	
						{                              
							$('.message8').html("");
							$('.message7').html('Could not update Student. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If email is already in use
						{                              
							$('.message8').html("");
							$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' The email address you entered is already in use. Please use another email.').fadeIn('slow');
						}
					}		
				});
			}
		}
		
		//Fetching class for year group change when adding a student's record.
		function getClasses()
		{
			var selectedYearGroup = $("#yeargroup option:selected").val();
			var theYearGroup=$("#yeargroup option:selected").text();
			$.ajax({
				type: "POST",
				url: "getClassToAddStudent.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
				}).done(function(data){
				$("#className").html(data);
			});
		}
		
		//Fetching class for year group change when updating a student's record.
		function getClasses1()
		{
			var selectedYearGroup = $("#yeargroup1 option:selected").val();
			var theYearGroup=$("#yeargroup1 option:selected").text();
			$.ajax({
				type: "POST",
				url: "getClassToAddStudent.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
				}).done(function(data){
				$("#className1").html(data);
			});
		}
		//Fetching class for year group change when uploading students' record through CSV
		function getClasses4()
		{
			var selectedYearGroup = $("#yearGroup4 option:selected").val();
			var theYearGroup=$("#yearGroup4 option:selected").text();
			$.ajax({
				type: "POST",
				url: "getClassToAddStudent.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
				}).done(function(data){
				$("#className4").html(data);
			});
		}
		
		//Fetching class for year group change when viewing students based on selected criteria
		function getClasses5()
		{
			if (xhr_viewCriteria) {
				xhr_viewCriteria.abort();
				xhr_viewCriteria = null;
			}
			clearViewStudentsList('Select an arm to list students.');
			$('#theClass').hide();
			$('.message9').html('');
			$('.message10').html('');

			var selectedYearGroup = $("#yearGroup5 option:selected").val();
			var theYearGroup=$("#yearGroup5 option:selected").text();
			$("#className5").html("<option value=''>Loading arms...</option>");

			if (!selectedYearGroup) {
				$("#className5").html("<option value=''>... Select Arm ...</option>");
				return;
			}

			$.ajax({
				type: "POST",
				url: "getClassToAddStudent.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
				}).done(function(data){
				$("#className5").html(data ? data.replace(/Class/g, 'Arm') : "<option value=''>No arm found</option>");
			}).fail(function(){
				$("#className5").html("<option value=''>Could not load arms</option>");
				$('.message9').html('<i class="fa fa-exclamation-triangle"></i> Could not load arms. Please try again.');
			});
		}
		
		//Fetching class for year group change when deleting students based on selected criteria
		function getClasses6()
		{
			var selectedYearGroup = $("#yearGroup6 option:selected").val();
			var theYearGroup=$("#yearGroup6 option:selected").text();
			clearDeleteStudentsList('Select an arm to list students for deletion.');
			$('.message11').html('');
			$("#className6").html("<option value=''>Loading arms...</option>");

			if (!selectedYearGroup) {
				$("#className6").html("<option value=''>... Select Arm ...</option>");
				return;
			}

			$.ajax({
				type: "POST",
				url: "getClassToAddStudent.php",
				data: { yearGroup : selectedYearGroup, yearGroupName : theYearGroup} 
				}).done(function(data){
				$("#className6").html(data ? data.replace(/Class/g, 'Arm') : "<option value=''>No arm found</option>");
			}).fail(function(){
				$("#className6").html("<option value=''>Could not load arms</option>");
				$('.message11').html('<i class="fa fa-exclamation-triangle"></i> Could not load arms. Please try again.');
			});
		}
		
		// Modal Global Variables
		var modal4_csv = document.getElementById("myModal4");
		var modal5_view = document.getElementById("myModal5");
		var modal6_delete = document.getElementById("myModal6");

		//open modal to upload CSV file of students' records
		function openUploadCsvModal()
		{
			if (modal4_csv) modal4_csv.style.display = "block";
		}
		
		// Close button for modal4
		var span4 = document.getElementsByClassName("close1")[3];
		if (span4) {
			span4.onclick = function() {
				if (modal4_csv) modal4_csv.style.display = "none";
			}
		}
		
		//open modal to delete students based on criteria
		function openDeleteStudentsModal()
		{
			if (modal6_delete) {
				clearDeleteStudentsList('Select a year group and arm to list students for deletion.');
				$('.message11').html('');
				modal6_delete.style.display = "block";
			}
		}
		
		// Close button for modal6
		var span6 = document.getElementsByClassName("close1")[7];
		if (span6) {
			span6.onclick = function() {
				if (modal6_delete) modal6_delete.style.display = "none";
			};
		}
		
		//open modal to view students based on criteria
		function openViewStudentsBasedOnCriteria()
		{
			if (modal5_view) {
				if (xhr_viewCriteria) {
					xhr_viewCriteria.abort();
					xhr_viewCriteria = null;
				}
				clearViewStudentsList('Select a year group and arm to list students.');
				$('#theClass').hide();
				$('.message9').html('');
				$('.message10').html('');
				modal5_view.style.display = "block";
			}
		}
		
		// Close button for modal5
		var span5 = document.getElementsByClassName("close1")[4];
		if (span5) {
			span5.onclick = function() {
				if (modal5_view) modal5_view.style.display = "none";
			}
		}
		
		var xhr_viewCriteria = null;

		function clearViewStudentsList(message)
		{
			var tableBody = document.querySelector('#viewStudentsTable tbody');
			var selectAllCheckbox = document.getElementById('checkUncheckAll');

			if (tableBody) {
				tableBody.innerHTML = '<tr class="empty-row"><td colspan="10">' + escapeHtml(message || 'No students loaded.') + '</td></tr>';
			}
			if (selectAllCheckbox) {
				selectAllCheckbox.checked = false;
				selectAllCheckbox.indeterminate = false;
			}
		}

		function getViewStudentCheckboxes(checkedOnly)
		{
			var selector = "#viewStudentsTable input[name='rowSelectCheckBox[]']";
			if (checkedOnly) selector += ':checked';
			return document.querySelectorAll(selector);
		}

		function updateViewSelectAllState()
		{
			var selectAllCheckbox = document.getElementById('checkUncheckAll');
			var allCheckboxes = getViewStudentCheckboxes(false);
			var checkedCheckboxes = getViewStudentCheckboxes(true);

			if (selectAllCheckbox) {
				selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCheckboxes.length === allCheckboxes.length;
				selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
			}
		}

		function addViewCheckboxListeners()
		{
			var checkboxes = getViewStudentCheckboxes(false);
			for (var i = 0, n = checkboxes.length; i < n; i++) {
				checkboxes[i].addEventListener('change', updateViewSelectAllState);
			}
		}

		function viewStudentsBasedOnCriteria()
		{
			if (xhr_viewCriteria) {
				xhr_viewCriteria.abort();
				xhr_viewCriteria = null;
			}
			
			$('.message9').html('');
			$('.message10').html('');
			
			var selectedYearGroupId = $("#yearGroup5 option:selected").val();
			var selectedClassId = $("#className5 option:selected").val();
			
			if (!selectedYearGroupId || selectedYearGroupId === "") {
				clearViewStudentsList('Select a year group and arm to list students.');
				$('.message9').html('<i class="fa fa-info-circle"></i> Please select the year group.');
				return;
			}
			if (!selectedClassId || selectedClassId === "") {
				clearViewStudentsList('Select an arm to list students.');
				$('.message9').html('<i class="fa fa-info-circle"></i> Please select the arm.');
				return;
			}
			
			clearViewStudentsList('Loading students...');
			$('#theClass').hide();
			$('.message9').html('<i class="fa fa-spinner fa-spin"></i> Loading students...');
			
			xhr_viewCriteria = $.ajax({
				type: 'POST',
				url: 'getStudentsRecordsBasedOnCriteria.php',
				data: {selectedYearGroupId: selectedYearGroupId, selectedClassId: selectedClassId},
				dataType: 'JSON',
				timeout: 15000,
				success: function(response) {
					$('.message9').html('');
					var students = Array.isArray(response) ? response : [];
					var len = students.length;

					if (len === 0) {
						clearViewStudentsList('No students found for the selected arm.');
						$('#theClass').hide();
						$('.message9').html('<i class="fa fa-info-circle"></i> No students found.');
						return;
					}

					var rowsHtml = '';
					for (var i = 0; i < len; i++) {
						var s = students[i];
						var fullName = [s.surname, s.firstName, s.middleName].filter(Boolean).join(' ');
						rowsHtml += '<tr>' +
							'<td>' + (i+1) + '</td>' +
							'<td>' + escapeHtml(s.surname) + '</td>' +
							'<td>' + escapeHtml(s.firstName) + '</td>' +
							'<td>' + escapeHtml(s.middleName) + '</td>' +
							'<td>' + escapeHtml(s.gender) + '</td>' +
							'<td>' + escapeHtml(s.admissionNumber) + '</td>' +
							'<td>' + escapeHtml(s.studentClassName) + '</td>' +
							'<td>' + escapeHtml(s.studentEmail) + '</td>' +
							'<td><center>' + callPictureCheck(s.studentId, s.studentPassport, s.firstName, s.surname) + '</center></td>' +
							"<td align='center'><input type='checkbox' name='rowSelectCheckBox[]' value='" + escapeHtml(s.studentId) + "' data-name='" + escapeHtml(fullName) + "' /></td>" +
							'</tr>';
					}

					$('#viewStudentsTable tbody').html(rowsHtml);
					addViewCheckboxListeners();
					updateViewSelectAllState();
					$("#theClass").show();
					$('.message9').html('<i class="fa fa-check-circle" style="color:green;"></i> Found ' + len + ' student(s).');
				},
				error: function(xhr, status, error) {
					if (status === 'abort') return; // Ignore intentional aborts
					clearViewStudentsList('Could not load students for this arm.');
					$('#theClass').hide();
					$('.message9').html('<i class="fa fa-exclamation-triangle"></i> Error loading students.');
				},
				complete: function() {
					xhr_viewCriteria = null;
				}
			});
		}

		function escapeHtml(value)
		{
			return String(value == null ? '' : value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		}

		function clearDeleteStudentsList(message)
		{
			var tableBody = document.querySelector('#deleteStudentsTable tbody');
			var selectAllCheckbox = document.getElementById('checkUncheckAll2');

			if (tableBody) {
				tableBody.innerHTML = '<tr class="empty-row"><td colspan="8">' + escapeHtml(message || 'No students loaded.') + '</td></tr>';
			}
			if (selectAllCheckbox) {
				selectAllCheckbox.checked = false;
				selectAllCheckbox.indeterminate = false;
			}
			updateDeleteButton();
		}

		function getDeleteStudentCheckboxes(checkedOnly)
		{
			var selector = "#deleteStudentsTable input[name='deleteStudentCheckBox[]']";
			if (checkedOnly) selector += ':checked';
			return document.querySelectorAll(selector);
		}

		function viewStudentsForDeletion()
		{
			$('.message11').html('');
			
			var selectedYearGroupId = $("#yearGroup6 option:selected").val();
			var selectedClassId = $("#className6 option:selected").val();
			
			if (!selectedYearGroupId) {
				clearDeleteStudentsList('Select a year group and arm to list students for deletion.');
				$('.message11').html('<i class="fa fa-info-circle"></i> Please select the year group.').fadeIn('slow');
				return;
			}
			if (!selectedClassId) {
				clearDeleteStudentsList('Select an arm to list students for deletion.');
				$('.message11').html('<i class="fa fa-info-circle"></i> Please select the arm.').fadeIn('slow');
				return;
			}
			
			clearDeleteStudentsList('Loading students...');
			$('.message11').html('<i class="fa fa-spinner fa-spin"></i> Loading students, please wait...');
			
			$.ajax({
				type: 'POST',
				url: 'getStudentsRecordsBasedOnCriteria.php',
				data: {selectedYearGroupId: selectedYearGroupId, selectedClassId: selectedClassId},
				dataType: 'JSON',
				timeout: 15000,
				success: function(response) {
					$('.message11').html('');
					var students = Array.isArray(response) ? response : [];
					var len = students.length;

					if (len === 0) {
						clearDeleteStudentsList('No students found for the selected arm.');
						$('.message11').html('<i class="fa fa-info-circle"></i> No students found for the selected arm.').fadeIn('slow');
						return;
					}

					var rowsHtml = '';
					for (var i = 0; i < len; i++) {
						var s = students[i];
						var fullName = [s.surname, s.firstName, s.middleName].filter(Boolean).join(' ');
						rowsHtml += '<tr>' +
							'<td>' + (i+1) + '</td>' +
							'<td>' + escapeHtml(s.surname) + '</td>' +
							'<td>' + escapeHtml(s.firstName) + '</td>' +
							'<td>' + escapeHtml(s.middleName) + '</td>' +
							'<td>' + escapeHtml(s.gender) + '</td>' +
							'<td>' + escapeHtml(s.admissionNumber) + '</td>' +
							'<td>' + escapeHtml(s.studentClassName) + '</td>' +
							"<td align='center'><input type='checkbox' name='deleteStudentCheckBox[]' value='" + escapeHtml(s.studentId) + "' data-name='" + escapeHtml(fullName) + "' /></td>" +
							'</tr>';
					}
					$('#deleteStudentsTable tbody').html(rowsHtml);
					addCheckboxListeners();
					updateDeleteButton();

					$('.message11').html('<i class="fa fa-info-circle"></i> Found ' + len +
						' student(s) in this arm. Select checkboxes then click Delete.').fadeIn('slow');
				},
				error: function(xhr, status, error) {
					clearDeleteStudentsList('Could not load students for this arm.');
					$('.message11').html('<i class="fa fa-exclamation-triangle"></i> ' +
						(status === 'timeout' ? 'Request timed out.' : 'Error loading students.') +
						' Please try again.').fadeIn('slow');
				}
			});
		}
		
			
		//Function to select and unselect students to be promoted or  demoted
		function CheckUncheckAll()
		{
		   var selectAllCheckbox=document.getElementById("checkUncheckAll");
			var checkboxes = getViewStudentCheckboxes(false);
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].checked = !!(selectAllCheckbox && selectAllCheckbox.checked);
			}
			updateViewSelectAllState();
		}
		
		//Function to select and unselect students to be deleted
		function CheckUncheckAll2()
		{
		   var selectAllCheckbox=document.getElementById("checkUncheckAll2");
			var checkboxes = getDeleteStudentCheckboxes(false);
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].checked = !!(selectAllCheckbox && selectAllCheckbox.checked);
			}
			updateDeleteButton();
		}
		
		// Function to update the delete button based on selected students
		function updateDeleteButton()
		{
			var allDeleteCheckboxes = getDeleteStudentCheckboxes(false);
			var selectedCheckboxes = getDeleteStudentCheckboxes(true);
			var count = selectedCheckboxes.length;
			var deleteBtn = document.getElementById('deleteSelectedBtn');
			var selectedCountSpan = document.getElementById('selectedCount');
			var selectAllCheckbox = document.getElementById('checkUncheckAll2');
			
			if (selectedCountSpan) {
				selectedCountSpan.textContent = count;
			}

			if (selectAllCheckbox) {
				selectAllCheckbox.checked = allDeleteCheckboxes.length > 0 && count === allDeleteCheckboxes.length;
				selectAllCheckbox.indeterminate = count > 0 && count < allDeleteCheckboxes.length;
			}
			
			if (deleteBtn) {
				if (count > 0) {
					deleteBtn.disabled = false;
					deleteBtn.classList.remove('btn-secondary');
					deleteBtn.classList.add('btn-danger');
				} else {
					deleteBtn.disabled = true;
					deleteBtn.classList.remove('btn-danger');
					deleteBtn.classList.add('btn-secondary');
				}
			}
		}
		
		// Add event listeners to individual checkboxes when the table is populated
		function addCheckboxListeners()
		{
			var checkboxes = getDeleteStudentCheckboxes(false);
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].addEventListener('change', updateDeleteButton);
			}
		}
		
		//Submit selected students and selected action
		function getChecked()
		{
			$('.message8').html('');
			$('.message10').html('');
			
			var favorite1 = [];
			var checkboxes = getViewStudentCheckboxes(true);

			for (var i = 0; i < checkboxes.length; i++) {
				favorite1.push(checkboxes[i].value)
			}
			
			var favorite=favorite1.join(",")
			var classPromotingDemotingFrom = document.getElementById('className5').value;
			var selectedClassId = document.getElementById('classes6').value;
			var selectedClassName = $("#classes6 option:selected").text();
			            		
			if (favorite=="")
			{
				$('.message10').html('<i class="fa fa-info-circle"></i> Please check at least one student before submitting')
			}
			else if (selectedClassId == "")
			{
				$('.message10').html('<i class="fa fa-info-circle"></i> Please select a class')
			}
			else
			{	
				var confirmIt = confirm("Are you sure you wish to promote/demote the selected student(s) to " + selectedClassName);
				if(confirmIt == true)
				{
					//organize the data properly
					var form_data = 
					  'classId='+selectedClassId+
					  '&checkedStudents='+favorite;

					$.ajax({
						url: "promoteOrDemoteStudent.php",
						type: "POST",     
						data: form_data,    
						success: function (html) {             
															
							if (html==0)	//If session is expired.
							{                              
								window.location.replace("logout.php");
							}
							else if (html==1)	//If examinees successfully added to write test
							{                              
								$('.message10').html('');
								$('.message8').html('<i class="fa fa-check-circle"></i> The selected Student(s) has/have successfully been promoted/demoted to ' + selectedClassName + '.').fadeIn('slow');
								var modal5 = document.getElementById("myModal5");
								 modal5.style.display = "none";
								$('#example').DataTable().clear().destroy();
								callTable();
							}
							else 	//If insertion is unsuccessful	
							{                              
								 $('.message8').html('');
								 $('.message10').html('Could not promoted/demoted selected Student(s). Please try again.').fadeIn('slow');
							}
						}								
					});
				}
			}
		}
		
		//Delete selected students
		function deleteSelectedStudents()
		{
			$('.message11').html('');
			
			var favorite1 = [];
			var checkboxes = getDeleteStudentCheckboxes(true);
			var selectedStudentNames = [];

			for (var i = 0; i < checkboxes.length; i++) {
				favorite1.push(checkboxes[i].value);
				var studentName = checkboxes[i].getAttribute('data-name');
				if (studentName) {
					selectedStudentNames.push(studentName);
				} else {
					var row = checkboxes[i].closest('tr');
					if (row && row.cells && row.cells.length > 3) {
						var surname = row.cells[1] ? row.cells[1].textContent : '';
						var firstName = row.cells[2] ? row.cells[2].textContent : '';
						var middleName = row.cells[3] ? row.cells[3].textContent : '';
						selectedStudentNames.push(surname + ' ' + firstName + ' ' + middleName);
					} else {
						selectedStudentNames.push('Unknown Student');
					}
				}
			}
			
			var favorite = favorite1.join(",");
			
			if (favorite == "")
			{
				$('.message11').html('<i class="fa fa-info-circle"></i> Please check at least one student before deleting');
				return;
			}
			
			openBulkDeleteModal(favorite, selectedStudentNames);
		}
		
		//Open modal to confirm bulk delete
		function openBulkDeleteModal(studentIds, studentNames)
		{
			console.log('openBulkDeleteModal called with:', studentIds, studentNames);
			var modal8 = document.getElementById("myModal8");
			// Ensure modal is at top of DOM to avoid stacking-context issues
			if (modal8 && modal8.parentNode !== document.body) {
				document.body.appendChild(modal8);
			}
			// Force visibility and top-layer stacking
			modal8.style.position = 'fixed';
			modal8.style.display = 'block';
			modal8.style.zIndex = '2147483646';
			console.log('Modal element:', modal8);
			
			if (!modal8) {
				console.error('Modal8 not found!');
				return;
			}
			
			document.getElementById("bulkStudentIds").value = studentIds;
			document.getElementById("bulkStudentCount").textContent = studentNames.length;
			
			// Populate the students list (optional UI). Guard in case it is hidden/removed.
			var studentsList = document.getElementById("studentsList");
			if (studentsList) {
				studentsList.innerHTML = '';
				studentNames.forEach(function(name, index) {
					var li = document.createElement('li');
					li.textContent = (index + 1) + '. ' + name.trim();
					studentsList.appendChild(li);
				});
			}
			
			// Generate dynamic confirmation code
			generateBulkConfirmationCode();
			
			console.log('Showing modal');
			modal8.style.display = "block";
			// Prevent background scroll
			document.documentElement.style.overflow = 'hidden';
			document.body.style.overflow = 'hidden';
		}
		
		//Generate dynamic confirmation code for bulk delete
		function generateBulkConfirmationCode()
		{
			// Generate a 6-character alphanumeric code
			var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			var code = '';
			for (var i = 0; i < 6; i++) {
				code += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			
			// Set the confirmation code
			document.getElementById("bulkConfirmationCode").textContent = code;
			
			// Set expiry time (5 minutes from now)
			var expiryTime = new Date().getTime() + (5 * 60 * 1000); // 5 minutes
			document.getElementById("bulkConfirmationCodeExpiry").value = expiryTime;
			
			// Clear user input and disable button
			document.getElementById("bulkUserConfirmationCode").value = '';
			document.getElementById("bulkDeleteConfirmBtn").disabled = true;
			
			// Add event listener for real-time validation
			document.getElementById("bulkUserConfirmationCode").addEventListener('input', validateBulkConfirmationCode);
			
			// Start countdown timer
			startBulkCountdownTimer();
		}
		
		//Validate bulk confirmation code in real-time
		function validateBulkConfirmationCode()
		{
			var userCode = document.getElementById("bulkUserConfirmationCode").value.toUpperCase();
			var expectedCode = document.getElementById("bulkConfirmationCode").textContent;
			var expiryTime = parseInt(document.getElementById("bulkConfirmationCodeExpiry").value);
			var currentTime = new Date().getTime();
			var deleteBtn = document.getElementById("bulkDeleteConfirmBtn");
			
			// Check if code has expired
			if (currentTime > expiryTime) {
				deleteBtn.disabled = true;
				showBulkCodeExpiredMessage();
				return;
			}
			
			// Check if codes match
			if (userCode === expectedCode) {
				deleteBtn.disabled = false;
				document.getElementById("bulkUserConfirmationCode").style.borderColor = "#28a745";
				document.getElementById("bulkUserConfirmationCode").style.backgroundColor = "#d4edda";
			} else {
				deleteBtn.disabled = true;
				document.getElementById("bulkUserConfirmationCode").style.borderColor = "#dc3545";
				document.getElementById("bulkUserConfirmationCode").style.backgroundColor = "#f8d7da";
			}
		}
		
		//Show bulk code expired message
		function showBulkCodeExpiredMessage()
		{
			document.getElementById("bulkUserConfirmationCode").style.borderColor = "#dc3545";
			document.getElementById("bulkUserConfirmationCode").style.backgroundColor = "#f8d7da";
			document.getElementById("bulkUserConfirmationCode").placeholder = "Code expired! Please refresh the modal.";
			document.getElementById("bulkUserConfirmationCode").disabled = true;
		}
		
		//Start countdown timer for bulk delete
		function startBulkCountdownTimer()
		{
			var expiryTime = parseInt(document.getElementById("bulkConfirmationCodeExpiry").value);
			var timerInterval = setInterval(function() {
				var currentTime = new Date().getTime();
				var timeLeft = expiryTime - currentTime;
				
				if (timeLeft <= 0) {
					clearInterval(timerInterval);
					showBulkCodeExpiredMessage();
					return;
				}
			}, 1000);
		}
		
		//Close bulk delete modal
		function closeBulkDeleteModal()
		{
			var modal8 = document.getElementById("myModal8");
			if (modal8) {
				// Force close the modal with multiple methods
				modal8.style.display = "none";
				modal8.style.visibility = "hidden";
				modal8.style.opacity = "0";
				modal8.classList.remove("show");
				
				// Remove any inline styles that might be keeping it visible
				modal8.removeAttribute("style");
				modal8.style.display = "none";
			}
			
			$('.message13').html("");
			
			// Restore background scroll
			document.documentElement.style.overflow = '';
			document.body.style.overflow = '';
			
			// Reset form elements
			var userCodeInput = document.getElementById("bulkUserConfirmationCode");
			var confirmBtn = document.getElementById("bulkDeleteConfirmBtn");
			
			if (userCodeInput) {
				userCodeInput.value = '';
				userCodeInput.disabled = false;
				userCodeInput.placeholder = "Type the confirmation code here";
				userCodeInput.style.borderColor = "";
				userCodeInput.style.backgroundColor = "";
			}
			
			if (confirmBtn) {
				confirmBtn.disabled = true;
			}
			
			// Clear confirmation code
			var confirmationCodeEl = document.getElementById("bulkConfirmationCode");
			var expiryEl = document.getElementById("bulkConfirmationCodeExpiry");
			
			if (confirmationCodeEl) {
				confirmationCodeEl.textContent = '';
			}
			if (expiryEl) {
				expiryEl.value = '';
			}
		}
		
		//Confirm bulk deletion of students
		function confirmBulkDeleteStudents()
		{
			$('.message13').html("");
			
			var studentIds = document.getElementById("bulkStudentIds").value;
			var studentCount = document.getElementById("bulkStudentCount").textContent;
			var userCode = document.getElementById("bulkUserConfirmationCode").value.toUpperCase();
			var expectedCode = document.getElementById("bulkConfirmationCode").textContent;
			var expiryTime = parseInt(document.getElementById("bulkConfirmationCodeExpiry").value);
			var currentTime = new Date().getTime();
			
			// Validate confirmation code
			if (!studentIds) {
				$('.message13').html('<i class="fa fa-exclamation-triangle"></i> Error: Student IDs not found.');
				return;
			}
			
			if (!userCode) {
				$('.message13').html('<i class="fa fa-exclamation-triangle"></i> Please enter the confirmation code.');
				return;
			}
			
			if (currentTime > expiryTime) {
				$('.message13').html('<i class="fa fa-exclamation-triangle"></i> Confirmation code has expired. Please refresh the modal.');
				return;
			}
			
			if (userCode !== expectedCode) {
				$('.message13').html('<i class="fa fa-exclamation-triangle"></i> Invalid confirmation code. Please check and try again.');
				return;
			}
			
					// Show loading message
					$('.message13').html('<i class="fa fa-spinner fa-spin"></i> Deleting students, please wait...');
					var deleteBtnEl = document.getElementById('bulkDeleteConfirmBtn');
					if (deleteBtnEl) { deleteBtnEl.disabled = true; }
			
			//organize the data properly
			var form_data = 'checkedStudents=' + encodeURIComponent(studentIds);

			$.ajax({
				url: "deleteSelectedStudents.php",
				type: "POST",     
				data: form_data,
				timeout: 30000, // 30 second timeout
					success: function (html) {             
					if (html == 0)	//If session is expired.
					{                              
						window.location.replace("logout.php");
					}
					else if (html == 4)	// No students selected or empty input
					{
						$('.message13').html('<i class="fa fa-info-circle"></i> Please select at least one student.').fadeIn('slow');
							if (deleteBtnEl) { deleteBtnEl.disabled = false; }
					}
					else if (html == 1)	//If students successfully deleted
					{                              
						// Close modal immediately so toast appears on top
						closeBulkDeleteModal();
						
						// Small delay to ensure modal is fully closed before showing toast
						setTimeout(function() {
							// Show success toast
							showToast('success', 'Bulk Deletion Successful', studentCount + ' student(s) have been successfully deleted from the system.');
						}, 100);
						
						// Refresh the main table
						$('#example').DataTable().clear().destroy();
						callTable();
						
						// Refresh the delete modal table
						viewStudentsForDeletion();
					}
					else if (html == 2)	//If some students deleted, some failed
					{                              
						// Close modal immediately so toast appears on top
						closeBulkDeleteModal();
						
						// Small delay to ensure modal is fully closed before showing toast
						setTimeout(function() {
							// Show warning toast
							showToast('warning', 'Partial Deletion', 'Some students were deleted successfully, but some failed. Please check the logs for details.');
						}, 100);
						
						// Refresh the main table
						$('#example').DataTable().clear().destroy();
						callTable();
						
						// Refresh the delete modal table
						viewStudentsForDeletion();
					}
					else if (html == 3)	//If no students were deleted
					{                              
						showToast('error', 'Deletion Failed', 'No students were deleted. Please verify the student IDs and try again.');
						// Refresh the delete modal table
						viewStudentsForDeletion();
							if (deleteBtnEl) { deleteBtnEl.disabled = false; }
					}
					else 	//If deletion is unsuccessful	
					{                              
						showToast('error', 'Error', 'Could not delete selected student(s). Please try again.');
						// Refresh the delete modal table
						viewStudentsForDeletion();
							if (deleteBtnEl) { deleteBtnEl.disabled = false; }
					}
				},
				error: function(xhr, status, error) {
					if (status === 'timeout') {
						showToast('error', 'Timeout Error', 'The deletion request timed out. Please try again.');
					} else {
						showToast('error', 'Network Error', 'An error occurred while deleting students. Please check your connection and try again.');
					}
					$('.message13').html('');
						if (deleteBtnEl) { deleteBtnEl.disabled = false; }
				}
			});
		}
		
		//Download CSV template for bulk upload
		function downloadCsvTemplate()
		{
			// Create CSV content with exact field names matching the form
			var csvContent = "surname,firstName,middleName,gender,admissionNumber,studentEmail,password\n";
			csvContent += "Maiyaki,John,Michael,Male,dlhs2024001,john.maiyaki@dlhs.edu,password123\n";
			csvContent += "Johnson,Sarah,,Female,dlhs2024002,sarah.johnson@dlhs.edu,password456\n";
			csvContent += "Williams,David,James,Male,dlhs2024003,david.williams@dlhs.edu,password789\n";
			
			// Create blob and download
			var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
			var link = document.createElement("a");
			var url = URL.createObjectURL(blob);
			link.setAttribute("href", url);
			link.setAttribute("download", "student_upload_template.csv");
			link.style.visibility = 'hidden';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			
			// Show success message
			showToast('success', 'Download Complete', 'CSV template has been downloaded successfully. Please fill in the required fields and upload.');
		}
	</script>
  </body>
</html>
