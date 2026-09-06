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
		if(!empty($_GET['msgStatus']))
		{
			switch($_GET['msgStatus']){
				case '1':
					$statusType = 'alert-success';
					$statusMsg = 'Staff successfully added.';
					break;
				case '2':
					$statusType = 'alert-danger';
					$statusMsg = 'Could not add staff. Please try again.';
					break;
				case '3':
					$statusType = 'alert-danger';
					$statusMsg = 'Email is already in use. Please use another email.';
					break;
				default:
					$statusType = '';
					$statusMsg = '';
			}
		}
		if(!empty($_GET['msgStatus1']))	//for uploading picture of staff
		{
			switch($_GET['msgStatus1']){
				case '1':
					$statusType = 'alert-success';
					$statusMsg = 'Picture of staff successfully uploaded.';
					break;
				case '2':
					$statusType = 'alert-danger';
					$statusMsg = 'Could not upload picture of staff. Please try again.';
					break;
				default:
					$statusType = '';
					$statusMsg = '';
			}
		}
		if(!empty($_GET['msgStatus2']))	//for updating picture of student
		{
			switch($_GET['msgStatus2']){
				case '1':
					$statusType2 = 'alert-success';
					$statusMsg2 = 'Picture of staff successfully updated.';
					break;
				case '2':
					$statusType2 = 'alert-danger';
					$statusMsg2 = 'Could not upload picture of staff. Please try again.';
					break;
				default:
					$statusType2 = '';
					$statusMsg2 = '';
			}
		}
		if(!empty($_GET['msgStatus3']))	//for uloading staff through csv
		{
			switch($_GET['msgStatus3']){
				case '1':
					$statusType3 = 'alert-success';
					$statusMsg3 = 'Staff successfully uploaded through CSV.';
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
					$statusMsg3 = 'Staff records successfully updated through CSV';
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
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="shortcut icon" href="../images/dlhslogo2.jpg">

    <title>Add Staff | DLHS</title>
	
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
	
    <!-- date picker -->
    
    <!-- color picker -->
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	
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
				btn.innerHTML = '<i class="fa fa-plus"></i> Add New Staff';
				btn.className = 'btn btn-success btn-sm';
			}
			
			if ($.fn.dataTable) {
				$('#example').DataTable().columns.adjust().responsive.recalc();
			}
		}

		function validatePicture()	//validating the size of picture
		{
			const fi = document.getElementById('file');
			if (fi.files.length > 0) { 
				for (const i = 0; i <= fi.files.length - 1; i++) 
				{ 
					const fsize = fi.files.item(i).size; 
					const file = Math.round((fsize / 1024)); 
					// The size of the file. 
					if (file >= 60) { 
						return "Picture should be greater than 60KB\n"; 
					} 
					else
					{ 
						return "";
					} 
				} 
			} 	
		}
		function validatePicture1()	//validating the size of picture at update of a staff to include his/her passport
		{
			const fi = document.getElementById('file1');
			if (fi.files.length > 0) { 
				for (const i = 0; i <= fi.files.length - 1; i++) 
				{ 
					const fsize = fi.files.item(i).size; 
					const file = Math.round((fsize / 1024)); 
					// The size of the file. 
					if (file >= 60) { 
						return "Picture should be greater than 60KB\n"; 
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
					if (file >= 60) { 
						return "Picture should be greater than 60KB\n"; 
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
			fail =validateSurname(form.surname.value)
			fail +=validateFirstName(form.firstName.value)
			fail +=validateGender(form.gender.value)
			fail +=validateEmail(form.staffEmail.value)
			fail +=validatePassword(form.thePassword.value)
			fail +=validatePassport(form.file.value)
			
			
			if (fail == ""){
				return true;
			}
			else { $('.message2').html(fail); return false }
		}
		
			function validateSurname(field){
				if (field == "") return "<i class='fa fa-info-circle'></i> Please enter surname of staff.\n"
					return ""
			}
			function validateFirstName(field){
				if (field == "") return "<i class='fa fa-info-circle'></i> Please enter first name of staff.\n"
					return ""
			}
			function validateGender(field){
				if (field == "") return "<i class='fa fa-info-circle'></i> Please select gender of staff.\n"
					return ""
			}
			function validateEmail(field)
			{
				var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
				if(field.match(mailformat)) return ""
						return "<i class='fa fa-info-circle'></i> Please enter valid email of staff.\n"
			}
			function validatePassword(field){
				if (field == "") return "<i class='fa fa-info-circle'></i> Please enter password to be used by staff.\n"
					return ""
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
		 .modal1, .modal2, .modal3, .modal4 {
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add new Staff</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Staffs</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-3" id="createFormColumn" style="display: none;">
						<section class="panel">
                          <header class="panel-heading">
                              Add New Staff
                          </header>
                          <div class="panel-body">
                              <form method="post" action="addStaff.php" onSubmit="return validate(this)" enctype="multipart/form-data">	
									
										<!-- Display status message -->
										<?php if(!empty($statusMsg)){ ?>
											<div class="form-group">
												<div class="alert <?php echo $statusType; ?>"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg; ?></div>
											</div>
										<?php } ?>
										<?php if(!empty($statusMsg1)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType1; ?>"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg1; ?></div>
										</div>
									<?php } ?>
									<?php if(!empty($statusMsg2)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType2; ?>"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg2; ?></div>
										</div>
									<?php } ?>
									<?php if(!empty($statusMsg3)){ ?>
										<div class="form-group">
											<div style="font-size:18px;" class="alert <?php echo $statusType3; ?>"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg3; ?></div>
										</div>
									<?php } ?>
									<div class="form-group">
										<div class="message4" style="color:red;" align="center"></div>
										<label>Surname of Staff (e.g. <em>Musa</em>)</label>
										<input type="text" name="surname" class="form-control" placeholder="Enter surname (e.g. Maiyaki)" required >
									</div>
									<div class="form-group">
										<label>First name</label>
										<input type="text" name="firstName" class="form-control" placeholder="Enter first name of student" required >
									</div>
									<div class="form-group">
										<label>Middle name</label>
										<input type="text" name="middleName" class="form-control" placeholder="Enter middle name of student">
									</div>
									<div class="form-group">
										<label>Gender</label>
										<select class="form-control m-bot15" name="gender" id="gender" required >
											<option value="">... Select gender ...</option>
											<option value="Male">Male</option>
											<option value="Female">Female</option>
										</select>
									</div>
									<div class="form-group">
										<label>Email</label>
										<input type="text" name="staffEmail" class="form-control" placeholder="Enter student's email" required >
									</div>
									<div class="form-group">
										<label>Password</label>
										<input type="password" name="thePassword" class="form-control" placeholder="Enter password of student" required >
									</div>
									<div class="form-group">
										<label>Image of Staff (<i>Size should not be more than 60KB</i>)</label>
										<input type="file" name="file" id="file" accept="image/png, image/jpeg" class="form-control" required>
									</div>
									<button type="submit" class="btn btn-primary" name="submitStaff"><i class="fa fa-sign-in"></i> Submit</button><br><br>
									<div class="message1" style="color:green; font-size:17px;" align="center"></div><div class="message2" style="color:red;" align="center"></div><br>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-12" id="tableColumn">
						<section class="panel">
							<header class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
								<span>View | Edit | Delete Staff &nbsp; &nbsp; &nbsp;<a onclick="openUploadCsvModal()" style="cursor:pointer;">Click to upload CSV of staff's records</a></span>
								<button class="btn btn-success btn-sm" id="toggleFormBtn" onclick="dlhsToggleCreateForm()" style="font-weight: 700; border-radius: 6px; padding: 6px 12px;"><i class="fa fa-plus"></i> Add New Staff</button>
								<div class="message8" style="color:green; font-size:17px;" align="center"></div>
							</header>
							<div class="panel-body">
								<div class="message3" style="color:green; font-size:17px;" align="center"></div><div class="message4" style="color:red;" align="center"></div>
								<div class="table-responsive">
								
											<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
												<thead>
													<tr>
														<th width='5%'>S/NO</th>
														<th>SURNAME</th>
														<th>FIRST NAME</th>
														<th>MIDDLE NAME</th>
														<th>GENDER</th>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Update Teacher's details</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" id="updateStaffForm">
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Staff Surname</span> <span class="required">*</span></label><br>
									<input type="hidden" name="staffId" required="required" id="staffId">
									<input type="text" name="surname1" required="required" id="surname1" class="form-control">
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Staff First name: </span> <span class="required">*</span></label><br>
									<input type="text" name="firstName1" required="required" id="firstName1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Staff Middle name: </span></label><br>
									<input type="text" name="middleName1" id="middleName1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Staff Gender: </span> <span class="required">*</span></label><br>
									<select class="form-control m-bot15" name="gender1" id="gender1" required >
										<option value="">... Select gender ...</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Staff Email: </span> <span class="required">*</span></label><br>
									<input type="text" name="staffEmail1" required="required" id="staffEmail1" class="form-control"  >
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Staff Password: </span> <span class="required">*</span></label><br>
									<input type="text" name="staffPassword1" required="required" id="staffPassword1" class="form-control"  >
								</div>
							</div>

							<div class="item form-group">
								<div class="col-md-12">
									<button class="btn btn-primary" type="reset">Reset</button>
									<button type="button" class="btn btn-success" id="updateStaff" onclick="updateStaffData()">Update</button>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Upload staff picture</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" action="addStaffPicture.php" onSubmit="return validate1(this)" enctype="multipart/form-data">
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Upload picture of <font id='toHoldStaffName'></font></span> <span class="required">*</span></label><br>
									<input type="hidden" name="setStaffId" required="required" id="setStaffId">
									<input type="file" name="file1" id="file1" accept="image/png, image/jpeg" class="form-control" required>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="submit" class="btn btn-primary" name="updateStaffPicture"><i class="fa fa-sign-in"></i> Upload</button><br><br>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Update staff picture</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" action="updateStaffPicture.php" onSubmit="return validate3(this)" enctype="multipart/form-data">
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Upload picture of <font id='toHoldStaffName3'></font></span> <span class="required">*</span></label><br>
									<input type="hidden" name="setStaffId3" required="required" id="setStaffId3">
									<input type="file" name="file3" id="file3" accept="image/png, image/jpeg" class="form-control" required>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="submit" class="btn btn-primary" name="updateStaffPicture3"><i class="fa fa-sign-in"></i> Upload</button><br><br>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Upload CSV file of staff' records</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" action="uploadCsvStaffRecords.php" enctype="multipart/form-data">
                            
							
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Select CSV file of staff records</span> <span style="color:red; font-size:18px;">*</span></label><br>
									
									<input type="file" name="file4" id="file4" accept=".xlsx, .xls, .csv" class="form-control" required>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="submit" class="btn btn-primary" name="uploadCsvOfStaff"><i class="fa fa-sign-in"></i> Upload</button><br><br>
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
		//function to check if a picture of the staff was uploaded before or not.
		function callPictureCheck(staffId, staffPassport, firstName, surname)
		{
			if(staffPassport == "")
			{
				return "<a onclick='openToUploadPic(\""+staffId+"\",\""+firstName+"\",\""+surname+"\")' style='cursor: pointer;' title='Click to upload passport of this staff'>Upload Picture</a>";
			}
			else
			{
				return "<a onclick='openToUpdatePicture(\""+staffId+"\",\""+firstName+"\",\""+surname+"\")' style='cursor:pointer;' title='Click to update picture'><img src='staffPassports/"+staffPassport+"' style='width:50px; height:60px;'/></a>";
			}
		}
		
		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getStaffRecords.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var staffId = response[i].staffId;
							var surname = response[i].surname;
							var firstName = response[i].firstName;
							var middleName = response[i].middleName;
							var gender = response[i].gender;
							var staffEmail = response[i].staffEmail;							
							var staffPassword = response[i].staffPassword;							
							var staffPassport = response[i].staffPassport;							
							
							var tr_str = "<tr>" +
								"<td>" + (i+1) + "</td>" +
								"<td>" + surname + "</td>" +
								"<td>" + firstName + "</td>" +
								"<td>" + middleName + "</td>" +
								"<td>" + gender + "</td>" +
								"<td>" + staffEmail + "</td>" +
								"<td><center>" + callPictureCheck(staffId, staffPassport, firstName, surname) + "</center></td>" +
								"<td align='center'><a onClick='openEditModal(\""+staffId+"\",\""+surname+"\",\""+firstName+"\",\""+middleName+"\",\""+gender+"\",\""+staffEmail+"\",\""+staffPassword+"\")' title='Edit this staff' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a>&nbsp; &nbsp; &nbsp; &nbsp;<a onClick='deleteStaff("+staffId+")' title='Delete this staff' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
				
		//Delete Staff
		function deleteStaff(staffId)
		{
			$('.message1').html("");
			$('.message2').html("");
			
			var toConfirm=confirm("Are you sure you wish to delete this staff?");
			if (toConfirm==true)
			{
				var form_data = 
					  'staffId='+staffId;
					  
					$.ajax({
						url: "deleteStaff.php",
						type: "POST",        
						data: form_data,
						success: function (html) {             
							if (html==0)	//If session is expired.
							{                              
								 window.location.replace("logout.php");
							}
							else if (html==1)	
							{                              
								 $('.message4').html("");
								 $('.message3').html('Staff successfully deleted.').fadeIn('slow');
								 $('#example').DataTable().clear().destroy();
								 callTable();
							}
							else if (html==2)	//If deletion is unsuccessful	
							{                              
								 $('.message3').html("");
								 $('.message4').html('Could not delete Staff. Please try again.').fadeIn('slow');
							}
						}
									
					});
			}	
		}
		
		
		//launching a modal to edit a staff
		//function call to open modal for editing a staff data
		function openEditModal(staffId, surname, firstName, middleName, gender, staffEmail, staffPassword)
        {
			$('.message7').html("");
            var modal1 = document.getElementById("myModal1");
			document.getElementById("staffId").value=staffId;
			document.getElementById("surname1").value=surname;
            document.getElementById("firstName1").value=firstName;       
            document.getElementById("middleName1").value=middleName;       
            $("#gender1 option[value="+gender+"]").attr('selected', 'selected');       
            document.getElementById("staffEmail1").value=staffEmail;       
            document.getElementById("staffPassword1").value=staffPassword;       
                
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
		
		//function to open modal to upload picture
		function openToUploadPic(staffId, firstName, surname)
		{
			var modal2 = document.getElementById("myModal2");
			document.getElementById("setStaffId").value=staffId;
			$('#toHoldStaffName').html(firstName + ' ' + surname);

			modal2.style.display = "block";
		}
		var span2 = document.getElementsByClassName("close1")[1];
            
        // When the user clicks on <span> (x), close the modal1
        span2.onclick = function() {
			modal2.style.display = "none";
        }
            
        // When the user clicks anywhere outside of the modal, close it
        var modal2 = document.getElementById("myModal2");
        window.onclick = function(event) {
            if (event.target == modal2) {
                modal2.style.display = "none";
            }
        }
		
		//function to open modal to update picture
		function openToUpdatePicture(staffId, firstName, surname)
		{
			$('.message6').html("");
			var modal3 = document.getElementById("myModal3");
			document.getElementById("setStaffId3").value=staffId;
			$('#toHoldStaffName3').html(firstName + ' ' + surname);

			modal3.style.display = "block";
		}
		var span3 = document.getElementsByClassName("close1")[2];
            
        // When the user clicks on <span> (x), close the modal1
        span3.onclick = function() {
			modal3.style.display = "none";
        }
            
        // When the user clicks anywhere outside of the modal, close it
        var modal3 = document.getElementById("myModal3");
        window.onclick = function(event) {
            if (event.target == modal3) {
                modal3.style.display = "none";
            }
        }
		
		//function declaration to update a staff's data
		function updateStaffData()
		{
			$('.message1').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message7').html('');
			$('.alert alert-success').html('');
					
			var staffIdToUpdate = $('input[name=staffId]').val();
			var surname = $('input[name=surname1]').val();
			var firstName = $('input[name=firstName1]').val();
			var middleName = $('input[name=middleName1]').val();
			var gender = document.getElementById('gender1').value;
			var email = $('input[name=staffEmail1]').val();
			var thePassword = $('input[name=staffPassword1]').val();					
						
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
			else if (email.length < 1)
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter email')
			}
			else if(email.length > 0 && !email.match(mailformat))
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + " Please enter staff's valid email")
			}
			else if (thePassword=="")
			{
				$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' Please enter password')
			}
			else
			{									
				var form_data = 
				  'staffIdToUpdate='+staffIdToUpdate+
				  '&surname='+surname+
				  '&firstName='+firstName+
				  '&middleName='+middleName+
				  '&gender='+gender+
				  '&staffEmail='+email+
				  '&thePassword='+thePassword;
				
				$.ajax({
					url: "updateStaffData.php",
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
							$('.message3').html('Staff data successfully updated.').fadeIn('slow');
							$("#updateStaffForm").trigger("reset");
							$('#example').DataTable().clear().destroy();
							callTable();
							var modal1 = document.getElementById("myModal1");
							modal1.style.display = "none";
						}
						else if (html==2)	//If insertion is unsuccessful	
						{                              
							$('.message1').html("");
							$('.message7').html('Could not update Staff. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If email is already in use
						{                              
							$('.message1').html("");
							$('.message7').html('<i class="fa fa-info-circle"></i> ' + ' The email address you entered is already in use. Please use another email.').fadeIn('slow');
						}
					}		
				});
			}
		}
		
		//open modal to upload CSV file of staff' records
		function openUploadCsvModal()
        {
            var modal4 = document.getElementById("myModal4");
                
            modal4.style.display = "block";
        }
		var span4 = document.getElementsByClassName("close1")[3];
            
        // When the user clicks on <span> (x), close the modal1
        span4.onclick = function() {
			modal4.style.display = "none";
        }
            
        // When the user clicks anywhere outside of the modal, close it
        var modal4 = document.getElementById("myModal4");
        window.onclick = function(event) {
            if (event.target == modal4) {
                modal4.style.display = "none";
            }
        }
	</script>
  </body>
</html>