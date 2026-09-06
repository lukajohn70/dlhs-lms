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
		
		$staffId = $_SESSION['staffId'];
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <link rel="icon" type="image/jpg" href="../images/dlhslogo3.jpg">

    <title>Form teacher business | DLHS</title>

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
            width: 90%;
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Form teacher settings</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Form teacher settings</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
            <div class="row">
				<div class="col-lg-3">
					<section class="panel">
						<header class="panel-heading">
							Current result settings
						</header>
						<div class="panel-body">
							<form>
								<div class="form-group">
									<?php
										$idToSelect = 1;
										//Getting the current result year
										$getCurrentResultYear = "SELECT * FROM set_result_year WHERE id='$idToSelect'";
										$result = $connection->query($getCurrentResultYear);
										$row = $result->fetch_array(MYSQLI_NUM);
										$currentResultYearId = $row[1];
										$currentResultYearName = $row[2];
										
										//Getting the current result term
										$getCurrentResultTerm = "SELECT * FROM result_set_current_term WHERE id='$idToSelect'";
										$result1 = $connection->query($getCurrentResultTerm);
										$row1 = $result1->fetch_array(MYSQLI_NUM);
										$currentResultTermId = $row1[1];
										$currentResultTerm = $row1[2];
									?>
									<br>
									<label style="font-size:20px;">Current Result Year: <font><b><?php echo $currentResultYearName; ?></b></font></label><br>
									<label style="font-size:20px;">Current Result Term: <font><b><?php echo $currentResultTerm; ?></font></b></label><br>
								</div>
							</form>
						</div>
					</section>
				</div>
				<div class="col-lg-9">
					<section class="panel">
						<header class="panel-heading">
							Classes you manage as a form teacher/master for current year and term
						</header>
						<div class="panel-body">
							<div class="message4" style="color:green; font-size:20px;" align="center"></div><br>
							<div id="displayForCreatedResultTable"></div>
							<div class="table-responsive" style="display:none" id="displayTableOrNot">
								<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%;">
									<thead>
										<tr>
											<th><center>S/NO</center></th>
											<th><center>CLASSES ASSIGNED TO YOU AS FORM TEACHER</center></th>
											<th><center>STUDENTS ADDED TO RESULT TABLE FOR CURRENT TERM</center></th>
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
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;"><?php echo $currentResultYearName." ".$currentResultTerm; ?> - <font id="addOrUpdateText"></font> result table of current year and term (Class: <b><font id="displayClassName"></font></b>)</center></div>
                <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;"> 
					<form>
						<div class="item form-group">
							<div class="table-responsive">
								<table id="example3" class="table table-striped table-bordered bulk_action" style="width:100%;">
									<thead>
										<tr>
											<th width="5%"><center>S/NO</center></th>
											<th width='8%'><input type='checkbox' id='checkUncheckAll' onClick='CheckUncheckAll()' /> &nbsp; Select all</th>
											<th width="10%"><center>PASSPORT</center></th>
											<th width="10%"><center>SURNAME</center></th>
											<th width="10%"><center>FIRST NAME</center></th>
											<th width="10%"><center>MIDDLE NAME</center></th>
											<th width="7%"><center>GENDER</center></th>
											<th width="10%"><center>ADMISSION NO.</center></th>
											<th width="15%"><center>HOUSE</center></th>
											<th width="15%"><center>SPORT ACTIVITY</center></th>
										</tr>
									</thead>
									<tbody>

									</tbody>
								</table>
								<br>
								<div id="buttonToAddSelected"><button type="button" class="btn btn-primary" onclick="addSelectedStudents()"><i class="fa fa-sign-in"></i> Add selected students</button></div>
								<div class="message3" style="color:red; font-size:20px;" align="center"></div>
							</div><!-- End of table-responsive -->
						</div>
					</form>
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
		function getMessageToAddOrUpdateStudents(flagIfStudentsExistInTable, yearGroupName, className, classId, classYearGroupId)	//function to check if students of the class have been added to that year and term's result table
		{
			if(flagIfStudentsExistInTable == 1)
			{
				return "Students added - <a onclick='getStudentsToUpdateOnResultTable(\""+classId+"\",\""+classYearGroupId+"\",\""+yearGroupName+"\",\""+className+"\")' style='cursor:pointer;'>Click to update students for this class " + yearGroupName + " - " + className + "</a>";
			}
			else if(flagIfStudentsExistInTable == 0)
			{
				return "No student added yet - <a onclick='getStudentsToAddToResultTable(\""+classId+"\",\""+classYearGroupId+"\",\""+yearGroupName+"\",\""+className+"\")' style='cursor:pointer;'>Click to add student(s) for " + yearGroupName + " - " + className + "</a>";
			}
		}
		
		function getClassesAssignedToFormTeacher()
		{
			$.ajax({
					url: 'getClassesAssigned.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var flagIfCreatedResultTableName = response[0].flagIfCreatedResultTableName; 
						var flagIfStudentsExistInCreatedResultTableName = response[0].flagIfStudentsExistInCreatedResultTableName; 
						
						if(flagIfCreatedResultTableName == 0)
						{
							$("#displayForCreatedResultTable").html("<center style='font-size:20px;'><font style='color:red;'><i class='fa fa-info-circle'></i></font> Admin is yet to create result table for current result year</center>");
						}
						else if(flagIfCreatedResultTableName == 1)
						{
							document.getElementById("displayTableOrNot").style.display = "block";
							var len = response.length;
							for(var i=0; i<len; i++)
							{
								var classId = response[i].classId;
								var className = response[i].className;
								var classYearGroupId = response[i].classYearGroupId;
								var yearGroupName = response[i].yearGroupName;
								var flagIfStudentsExistInTable = response[i].flagIfStudentsExistInCreatedResultTableName;
								
								var tr_str = "<tr>" +
									"<td><center>" + (i+1) + "</center></td>" +
									"<td>" + yearGroupName + ' - '+ className + "</td>" +
									"<td>" + getMessageToAddOrUpdateStudents(flagIfStudentsExistInTable, yearGroupName, className, classId, classYearGroupId) + "</td>" +									
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
					}
			});
		}
		//calling the data table function
		getClassesAssignedToFormTeacher()
		
		//function call to get students of a class and display to be added to result table.
		function getStudentsToAddToResultTable(classId, yearGroupId, yearGroupName, className)
        {
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
            var modal1 = document.getElementById("myModal1");
			$("#displayClassName").html(yearGroupName + " " + className);
			$("#addOrUpdateText").html("Add students to");
			$('#example3').DataTable().clear().destroy();
			$.ajax({
					url: 'getStudentsToAddToResultTable.php',
					type: 'POST',
					data: {classId:classId, yearGroupId:yearGroupId},
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var studentId = response[i].studentId;
							var surname = response[i].surname;
							var firstName = response[i].firstName;
							var middleName = response[i].middleName;						
							var gender = response[i].gender;						
							var admissionNumber = response[i].admissionNumber;						
							var studentPassport = response[i].studentPassport;						
							var houseName = response[i].houseName;						
							var sportName = response[i].sportName;	
							
							var displayPassport = "";
							if(studentPassport == "")
							{
								displayPassport = "No passport";
							}
							else
							{
								displayPassport = "<img src='../../adminLogin/adminPanel/studentPassports/"+studentPassport+"' style='width:50px; height:60px;'/>";
							}
	
							var tr_str = "<tr style='color:#000000;'>" +
								"<td width='5%'><center>" + (i+1) + "</center></td>" +
								"<td width='8%'><center><input type='checkbox' name='rowSelectCheckBox[]' id='rowSelectCheckBox' value='"+studentId+"' /><center></td>" +
								"<td width='10%'><center>" + displayPassport + "</center></td>" +
								"<td width='10%'>" + surname + "</td>" +
								"<td width='10%'>" + firstName + "</td>" +
								"<td width='10%'>" + middleName + "</td>" +
								"<td width='7%'>" + gender + "</td>" +
								"<td width='10%'>" + admissionNumber + "</td>" +
								"<td width='15%'><center>" + houseName + "</center></td>" +
								"<td width='15%'><center>" + sportName + "</center></td>" +
	
							"</tr>";

							$("#example3 tbody").append(tr_str);
						}
						$('#example3').DataTable( {
						"paging":   false,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							'copy', 'csv', 'excel', 'pdf', 'print'
						]
						});
						
						if(len > 0)
						{
							$("#buttonToAddSelected").show();
						}
						else
						{
							$("#buttonToAddSelected").hide();
						}
					}
			});
                
            modal1.style.display = "block";
        }
		
		//function call to get students of a class and display to be updated on result table.
		function getStudentsToUpdateOnResultTable(classId, yearGroupId, yearGroupName, className)
        {
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
            var modal1 = document.getElementById("myModal1");
			$("#displayClassName").html(yearGroupName + " " + className);
			$("#addOrUpdateText").html("Update students of");
			$('#example3').DataTable().clear().destroy();
			$.ajax({
					url: 'getStudentsToAddToResultTable.php',
					type: 'POST',
					data: {classId:classId, yearGroupId:yearGroupId},
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var studentId = response[i].studentId;
							var surname = response[i].surname;
							var firstName = response[i].firstName;
							var middleName = response[i].middleName;						
							var gender = response[i].gender;						
							var admissionNumber = response[i].admissionNumber;						
							var studentPassport = response[i].studentPassport;						
							var houseName = response[i].houseName;						
							var sportName = response[i].sportName;	
							var statusForCheck = response[i].statusForCheck;

							var applyCheckedStatus = "";
							if(statusForCheck == 0)
							{
								applyCheckedStatus = "";
							}
							else if(statusForCheck == 1)
							{
								applyCheckedStatus = "checked";
							}
							
							var displayPassport = "";
							if(studentPassport == "")
							{
								displayPassport = "No passport";
							}
							else
							{
								displayPassport = "<img src='../../adminLogin/adminPanel/studentPassports/"+studentPassport+"' style='width:50px; height:60px;'/>";
							}
	
							var tr_str = "<tr style='color:#000000;'>" +
								"<td width='5%'><center>" + (i+1) + "</center></td>" +
								"<td width='8%'><center><input type='checkbox' "+applyCheckedStatus+" name='rowSelectCheckBox[]' id='rowSelectCheckBox' value='"+studentId+"' /><center></td>" +
								"<td width='10%'><center>" + displayPassport + "</center></td>" +
								"<td width='10%'>" + surname + "</td>" +
								"<td width='10%'>" + firstName + "</td>" +
								"<td width='10%'>" + middleName + "</td>" +
								"<td width='7%'>" + gender + "</td>" +
								"<td width='10%'>" + admissionNumber + "</td>" +
								"<td width='15%'><center>" + houseName + "</center></td>" +
								"<td width='15%'><center>" + sportName + "</center></td>" +
	
							"</tr>";

							$("#example3 tbody").append(tr_str);
						}
						$('#example3').DataTable( {
						"paging":   false,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							'copy', 'csv', 'excel', 'pdf', 'print'
						]
						});
						
						if(len > 0)
						{
							$("#buttonToAddSelected").show();
						}
						else
						{
							$("#buttonToAddSelected").hide();
						}
					}
			});
                
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
		
		//Function to select and unselect students to be added to result table
		function CheckUncheckAll()
		{
		   var  selectAllCheckbox=document.getElementById("checkUncheckAll");
			if(selectAllCheckbox.checked==true)
			{
				var checkboxes =  document.getElementsByName("rowSelectCheckBox[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = true;
				}
			}
			else
			{
				var checkboxes =  document.getElementsByName("rowSelectCheckBox[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = false;
				}
			}
		}
		
		function addSelectedStudents()	//function to add all the selected students to be added to the result table for current year and term
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
			
			var favorite1 = [];
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox[]']:checked");

			for (var i = 0; i < checkboxes.length; i++) {
				favorite1.push(checkboxes[i].value)
			}
			
			favorite=favorite1.join(",")
			            		
			if (favorite=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please check at least one student before submitting')
			}
			else
			{
				var confirmIt = confirm("Are you sure you wish to add the selected student(s) to the current year and term's result table?");
				if(confirmIt == true)
				{
					var addStudentsToResultTable = 1;
					$.ajax({
						url: "addSelectedStudents.php",
						type: "POST",     
						data: {addStudentsToResultTable:addStudentsToResultTable, checkedStudents:favorite},    
						success: function (html) {             				
							if (html==0)	//If session is expired.
							{                              
								window.location.replace("logout.php");
							}
							else if (html==1)	//If examinees successfully added to write test
							{                              
								$('.message4').html("<i class='fa fa-check-circle'></i> The selected Student(s) has/have successfully been added to current year and term's result table.").fadeIn('slow');
								var modal1 = document.getElementById("myModal1");
								modal1.style.display = "none";
								$('#example').DataTable().clear().destroy();
								getClassesAssignedToFormTeacher();
							}
							else 	//If insertion is unsuccessful	
							{                              
								 $('.message4').html('');
								 $('.message3').html('Could not add selected Student(s). Please try again.').fadeIn('slow');
							}
						}								
					});
				}
			}
		}
	</script>


  </body>
</html>
