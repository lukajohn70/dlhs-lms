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

    <title>Set Result Year | DLHS</title>

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
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="jQuery3.3.1.js"></script>
	<script src="setAcademicYearAjax.js"></script>
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Result Settings</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Result settings</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
            <div class="row">
				<div class="col-lg-4">
					<section class="panel">
						<header class="panel-heading">
							Set Current Result year
						</header>
						<div class="panel-body">
							<form>
								<div class="form-group">
									<label>Set Result Year</label>
									<select class="form-control m-bot15" name="resultYear" id="resultYear" required >
									<option value="">. . . Select Result year . . .</option>
									<?php
										$academicYear="SELECT * FROM academic_year";
										$result4 = $connection->query($academicYear);
										while($row4 = $result4->fetch_array(MYSQLI_NUM)){
									?>
									<option value="<?php echo $row4[0]; ?>"><?php echo $row4[1]; ?></option>
									<?php } ?>
								</select>
								</div>
								<button type="button" class="btn btn-primary" id="submit" onclick="setResultYear()"><i class="fa fa-sign-in"></i> Submit</button><br><br>
								<div class="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
							</form>
						</div>
					</section>
				</div>
				<div class="col-lg-8">
					<section class="panel">
						<header class="panel-heading">
							View | Edit Result Year
						</header>
						<div class="panel-body">
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
									<thead>
										<tr>
											<th><center>S/NO</center></th>
											<th><center>CURRENT RESULT YEAR</center></th>
										</tr>
									</thead>
									<tbody>
										
									</tbody>
								</table>
							</div><!-- End of table-responsive -->
						</div>
					</section>
				</div>	
			</div><!-- End of row -->
			
			<hr style="border:2px solid grey">
			<div class="row"><!-- Start of row setting current term-->
				<div class="col-lg-4">
					<section class="panel">
						<header class="panel-heading">
							Set Current Result Term
						</header>
						<div class="panel-body">
							<form>
								<div class="form-group">
									<label>Set Result Term</label>
									<select class="form-control m-bot15" name="resultTerm" id="resultTerm" required >
									<option value="">. . . Select Term . . .</option>
									<?php
										$resultTerm="SELECT * FROM terms";
										$result5 = $connection->query($resultTerm);
										while($row5 = $result5->fetch_array(MYSQLI_NUM)){
									?>
									<option value="<?php echo $row5[0]; ?>"><?php echo $row5[1]; ?></option>
									<?php } ?>
								</select>
								</div>
								<button type="button" class="btn btn-primary" id="submit" onclick="setResultTerm()"><i class="fa fa-sign-in"></i> Submit</button><br><br>
								<div class="message222" style="color:red;" align="center"></div><div class="message111" style="color:green; font-size:17px;" align="center"></div><br>
							</form>
						</div>
					</section>
				</div>
				<div class="col-lg-8">
					<section class="panel">
						<header class="panel-heading">
							View | Edit Result Term
						</header>
						<div class="panel-body">
							<div class="table-responsive">
								<table id="example2" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
									<thead>
										<tr>
											<th><center>S/NO</center></th>
											<th><center>CURRENT RESULT TERM</center></th>
										</tr>
									</thead>
									<tbody>
										
									</tbody>
								</table>
							</div><!-- End of table-responsive -->
						</div>
					</section>
				</div>	
			</div><!-- End of row setting current term-->
			
			<hr style="border:2px solid grey">
			<div class="row"><!-- Start of row creating a new table to hold result for a year-->
				<div class="col-lg-4">
					<section class="panel">
						<header class="panel-heading">
							Create result table for a result year
						</header>
						<div class="panel-body">
							<form>
								<div class="form-group">
									<label>Create Result table for a year</label>
									<select class="form-control m-bot15" name="yearToCreateResultTable" id="yearToCreateResultTable" required >
										<option value="">. . . Select year . . .</option>
										<?php
											$academicYear5="SELECT * FROM academic_year";
											$result5 = $connection->query($academicYear5);
											while($row5 = $result5->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row5[0]; ?>"><?php echo $row5[1]; ?></option>
										<?php } ?>
									</select>
								</div>
								<button type="button" class="btn btn-primary" id="submit" onclick="createResultTable()"><i class="fa fa-sign-in"></i> Create</button><br><br>
								<div class="message22" style="color:red;" align="center"></div><div class="message11" style="color:green; font-size:17px;" align="center"></div><br>
							</form>
						</div>
					</section>
				</div>
				<div class="col-lg-8">
					<section class="panel">
						<header class="panel-heading">
							View | Delete Result Year tables
						</header>
						<div class="panel-body">
							<div class="table-responsive">
								<table id="example1" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
									<thead>
										<tr>
											<th><center>S/NO</center></th>
											<th><center>YEARS TABLES WERE CREATED FOR RESULT</center></th>
											<th><center>STATUS OF TERMS' RESULT LOCK</center></th>
											<th><center>ACTION</center></th>
										</tr>
									</thead>
									<tbody>
										
									</tbody>
								</table>
							</div><!-- End of table-responsive -->
						</div>
					</section>
				</div>	
			</div><!-- End of row creating a new table to hold result for a year -->			
        </section><!-- End of section wrapper -->	
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
		//function declaration to set result year
		function setResultYear()
		{
			$('.message1').html("");
			$('.message2').html("");
					
			
			var resultYearId = document.getElementById('resultYear').value;
			var resultYearName = $('#resultYear option:selected').text();
			
			if (resultYearId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select result year')
			}
			else
			{			
				$.ajax({
					url: "setResultYear.php",
					type: "POST",       
					data: {resultYearId:resultYearId, resultYearName:resultYearName},    
					success: function (html) {   
						if (html==0) 
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message2').html("");
							$('.message1').html('<i class="fa fa-check-circle"></i> Result year successfully set to ' + resultYearName).fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							getResultYear();
						}
						else if (html==2)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-times-circle"></i> Could not set Result year. Please try again.').fadeIn('slow');
						}
					}
				});
			}
		}//End of setting result year
		
		//function declaration to set result term
		function setResultTerm()
		{
			$('.message111').html("");
			$('.message222').html("");
					
			
			var resultTermId = document.getElementById('resultTerm').value;
			var resultTermName = $('#resultTerm option:selected').text();
			
			if (resultTermId=="")
			{
				$('.message222').html('<i class="fa fa-info-circle"></i> Please select result term')
			}
			else
			{			
				$.ajax({
					url: "setResultTerm.php",
					type: "POST",       
					data: {resultTermId:resultTermId, resultTermName:resultTermName},    
					success: function (html) {   
						if (html==0) 
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message111').html('<i class="fa fa-check-circle"></i> Result term successfully set to ' + resultTermName).fadeIn('slow');
							$('#example2').DataTable().clear().destroy();
							getResultTerm();
						}
						else if (html==2)
						{                              
							$('.message222').html('<i class="fa fa-times-circle"></i> Could not set Result term. Please try again.').fadeIn('slow');
						}
					}
				});
			}
		}//End of setting result term

		function getResultYear()
		{
			$.ajax({
					url: 'getResultYear.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var resultYearId = response[i].resultYearId;
							var resultYearName = response[i].resultYearName;						
							
							var tr_str = "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td><center>" + resultYearName + "</center></td>" +								
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
		getResultYear();
		
		function getResultTerm()	//Getting result term
		{
			$.ajax({
					url: 'getResultTerm.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var resultTermId = response[i].resultTermId;
							var resultTermName = response[i].resultTermName;						
							
							var tr_str = "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td><center>" + resultTermName + "</center></td>" +								
								"</tr>";

							$("#example2 tbody").append(tr_str);
						}
						$('#example2').DataTable( {
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
		getResultTerm();
		
		//function declaration to create result table for a particular year.
		function createResultTable()
		{
			$('.message11').html("");
			$('.message22').html("");
					
			
			var yearToCreateResultTable = document.getElementById('yearToCreateResultTable').value;
			var resultYearName = $('#yearToCreateResultTable option:selected').text();
			
			if (yearToCreateResultTable=="")
			{
				$('.message22').html('<i class="fa fa-info-circle"></i> Please select year to create result table for.')
			}
			else
			{			
				$.ajax({
					url: "createResultTable.php",
					type: "POST",       
					data: {yearToCreateResultTable:yearToCreateResultTable},    
					success: function (html) {   
						if (html==0) 
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message22').html("");
							$('.message11').html('<i class="fa fa-check-circle"></i> Result table successfully created for <b>' + resultYearName + '</b>').fadeIn('slow');
							$('#example1').DataTable().clear().destroy();
							getResultTableYear();
						}
						else if (html==2)
						{                              
							$('.message11').html("");
							$('.message22').html('<i class="fa fa-times-circle"></i> Could not create result year. Please try again.').fadeIn('slow');
						}
						else if (html==3)
						{                              
							$('.message11').html("");
							$('.message22').html('<i class="fa fa-times-circle"></i> Result year for <b>' + resultYearName + '</b> already exists').fadeIn('slow');
						}
					}
				});
			}
		}
		
		//function to return lock STATUS
		function getLockStatus(resultYearId, currentStatus, termNumToSendForCheck, resultYearName)
		{
			if(currentStatus == 0)
			{
				var newStatus = 1;
				return "<a onClick='lockUnlockTermResultEditing(\""+resultYearId+"\",\""+newStatus+"\",\""+termNumToSendForCheck+"\",\""+resultYearName+"\")' title='Status:Unlocked. Click to lock' style='cursor:pointer;'><i class='fa fa-unlock' aria-hidden='true' style='color:green;'></i></a>";
			}
			else if(currentStatus == 1)
			{
				var newStatus = 0;
				return "<a onClick='lockUnlockTermResultEditing(\""+resultYearId+"\",\""+newStatus+"\",\""+termNumToSendForCheck+"\",\""+resultYearName+"\")' title='Status:Locked. Click to unlock' style='cursor:pointer;'><i class='fa fa-lock' aria-hidden='true' style='color:red;'></i></a>";
			}
		}
		
		function getResultTableYear()
		{
			$.ajax({
					url: 'getResultTableYear.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var resultYearId = response[i].resultTableNameId;
							var resultYearName = response[i].resultYearName;						
							var term1LockStatus = response[i].term1LockStatus;						
							var term2LockStatus = response[i].term2LockStatus;						
							var term3LockStatus = response[i].term3LockStatus;	

							var term1NumToSendForCheck = 1;
							var term2NumToSendForCheck = 2;
							var term3NumToSendForCheck = 3;
							
							var tr_str = "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td><center>" + resultYearName + "</center></td>" +
								"<td align='center'>" + getLockStatus(resultYearId, term1LockStatus, term1NumToSendForCheck, resultYearName) + " &nbsp; &nbsp;|&nbsp; &nbsp; " + getLockStatus(resultYearId, term2LockStatus, term2NumToSendForCheck, resultYearName) + " &nbsp; &nbsp;|&nbsp; &nbsp; " + getLockStatus(resultYearId, term3LockStatus, term3NumToSendForCheck, resultYearName) + "</td>" +
								"<td align='center'><a onClick='deleteResultYearTable(\""+resultYearId+"\",\""+resultYearName+"\")' title='Delete result table for this year' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								"</tr>";

							$("#example1 tbody").append(tr_str);
						}
						$('#example1').DataTable( {
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
		getResultTableYear();
		
		function deleteResultYearTable(resultYearId, resultYearName)
		{
			$('.message11').html("");
			$('.message22').html("");
			
			var toConfirm=confirm("Are you sure you wish to delete the table holding the result year for " + resultYearName + "? Please note that this action cannot be undone, and it completely deletes the entire school's result for the selected year.");
			if (toConfirm==true)
			{
				$.ajax({
					url: "deleteResultYearTable.php",
					type: "POST",        
					data: {resultYearId:resultYearId},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							$('.message22').html("");
							$('.message11').html('<i class="fa fa-check-circle"></i> Table for <b>' + resultYearName + '</b> results successfully deleted.').fadeIn('slow');
							$('#example1').DataTable().clear().destroy();
							getResultTableYear();
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							 $('.message11').html("");
							 $('.message22').html('<i class="fa fa-times-circle"></i> Could not delete result table. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If deletion is unsuccessful	
						{                              
							 $('.message11').html("");
							 $('.message22').html('<i class="fa fa-times-circle"></i> Could not delete result table of selected year. Please try again.').fadeIn('slow');
						}
					}
				});
			}	
		}
		
		function lockUnlockTermResultEditing(resultYearId, termLockStatus, termNumToSendToChangeStatus, resultYearName)
		{
			$('.message11').html("");
			$('.message22').html("");
			
			var termLockStatusMessage = "";
			var termLockStatusMessageWithEd = "";
			if(termLockStatus == 0)
			{
				termLockStatusMessage = "unlock";
				termLockStatusMessageWithEd = "unlocked";
			}
			else if(termLockStatus == 1)
			{
				termLockStatusMessage = "lock";
				termLockStatusMessageWithEd = "locked";
			}
			
			termNumToSendToChangeStatusMessage = "";
			if(termNumToSendToChangeStatus == 1)
			{
				termNumToSendToChangeStatusMessage = "first term";
			}
			else if(termNumToSendToChangeStatus == 2)
			{
				termNumToSendToChangeStatusMessage = "second term";
			}
			else if(termNumToSendToChangeStatus == 3)
			{
				termNumToSendToChangeStatusMessage = "third term";
			}
			
			var toConfirm=confirm("Are you sure you wish to " + termLockStatusMessage + " the " + termNumToSendToChangeStatusMessage + " result of " + resultYearName + " ");
			if (toConfirm==true)
			{
				$.ajax({
					url: "lockUnlockTermResult.php",
					type: "POST",        
					data: {resultYearId:resultYearId, termLockStatus:termLockStatus, termNumToSendToChangeStatus:termNumToSendToChangeStatus},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							$('.message22').html("");
							$('.message11').html('<i class="fa fa-check-circle"></i> ' + termNumToSendToChangeStatusMessage + ' result for <b>' + resultYearName + '</b> results has been successfully '+termLockStatusMessageWithEd + ' for editing').fadeIn('slow');
							$('#example1').DataTable().clear().destroy();
							getResultTableYear();
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							 $('.message11').html("");
							 $('.message22').html('<i class="fa fa-times-circle"></i> Could not ' + termLockStatusMessage + ' result table. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If deletion is unsuccessful	
						{                              
							 $('.message11').html("");
							 $('.message22').html('<i class="fa fa-times-circle"></i> Could not ' + termLockStatusMessage + ' result table of selected year. Please try again.').fadeIn('slow');
						}
					}
				});
			}	
		}
	</script>


  </body>
</html>
