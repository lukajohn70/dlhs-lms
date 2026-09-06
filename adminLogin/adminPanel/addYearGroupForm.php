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
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="jQuery3.3.1.js"></script>
	<script src="addYearGroupAjax.js"></script>
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add new Year Group</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Year Groups</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-4" id="createFormColumn" style="display: none;">
						<section class="panel">
							<header class="panel-heading">
								Please fill required field in the form
							</header>
							<div class="panel-body">
								<form>
									<div class="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label style="color:#000000;">Section</label>
										<select class="form-control m-bot15" name="section" id="section" required >
											<option value="">. . . Select Section . . .</option>
											<?php
											
											$sections="select * from sections";
											$result1 = $connection->query($sections);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="form-group">
										<label>Name of Year Group (e.g. <em>JS 1</em>)</label>
										<input type="text" name="yearGroup" class="form-control" placeholder="Enter Year Group" required >
									</div>
									<button type="button" class="btn btn-primary" id="submit" onclick="addYearGroup()"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-12" id="tableColumn">
						<section class="panel">
							<header class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
								<span>View | Edit | Delete Year group</span>
								<button class="btn btn-success btn-sm" id="toggleFormBtn" onclick="dlhsToggleCreateForm()" style="font-weight: 700; border-radius: 6px; padding: 6px 12px;"><i class="fa fa-plus"></i> Add New Year Group</button>
							</header>
							<div class="panel-body">
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
												<thead>
													<tr>
														<th>S/NO</th>
														<th>Year group name</th>
														<th>Section</th>
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
					<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Edit Year group</center></div>
                    <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                        <form method="post" id="editYearGroupForm">
							<div class="message3" style="color:red;" align="center"></div><div class="message4" style="color:green; font-size:17px;" align="center"></div><br>
                            <div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Year group name to edit: <b><font id="yearGroupNameToEdit"></font></b></span></label><br>
									<label><span style="color:#000000;">Section: </span> <span class="required" style="color:red;">*</span></label><br>
									<input type="hidden" name="yearGroupId" required="required" id="yearGroupId">
									<select class="form-control m-bot15" name="section1" id="section1" required >
											<option value="">. . . Select Section . . .</option>
											<?php
											
											$sections2="select * from sections";
											$result2 = $connection->query($sections2);
											while($row2 = $result2->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row2[0]; ?>"><?php echo $row2[1]; ?></option>
											<?php } ?>
										</select>
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<label><span style="color:#000000;">Change year group name below: </span> <span class="required" style="color:red;">*</span></label><br>
									<input type="text" name="YearGroupName1" required="required" id="YearGroupName1" class="form-control">
								</div>
							</div>
							<div class="item form-group">
								<div class="col-md-12">
									<button type="button" class="btn btn-success" onclick="editYearGroupName()">Update</button>
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
		function dlhsToggleCreateForm() {
			var formCol = document.getElementById('createFormColumn');
			var tableCol = document.getElementById('tableColumn');
			var btn = document.getElementById('toggleFormBtn');
			if (!formCol || !tableCol || !btn) return;
			
			if (formCol.style.display === 'none') {
				formCol.style.display = 'block';
				tableCol.className = 'col-lg-8';
				btn.innerHTML = '<i class="fa fa-minus"></i> Hide Form';
				btn.className = 'btn btn-danger btn-sm';
			} else {
				formCol.style.display = 'none';
				tableCol.className = 'col-lg-12';
				btn.innerHTML = '<i class="fa fa-plus"></i> Add New Year Group';
				btn.className = 'btn btn-success btn-sm';
			}
			
			if ($.fn.dataTable) {
				$('#example').DataTable().columns.adjust().responsive.recalc();
			}
		}

		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getYearGroups.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var yearGroupId = response[i].yearGroupId;
							var yearGroupName = response[i].yearGroupName;						
							var sectionId = response[i].sectionId;						
							var sectionName = response[i].sectionName;						
							
							var tr_str = "<tr>" +
								"<td>" + (i+1) + "</td>" +
								"<td>" + yearGroupName + "</td>" +
								"<td>" + sectionName + "</td>" +
								"<td align='center'><a onClick='openEditModal(\""+yearGroupId+"\",\""+yearGroupName+"\",\""+sectionId+"\",\""+sectionName+"\")' title='Edit this year group' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a>&nbsp; &nbsp; &nbsp; &nbsp;<a onClick='deleteYearGroup(\""+yearGroupId+"\",\""+yearGroupName+"\")' title='Delete this year group' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
		function deleteYearGroup(yearGroupId, yearGroupName)
		{
			$('.message1').html("");
			$('.message2').html("");
			
			var toConfirm=confirm("Are you sure you wish to delete this year group? Please note that there may be classes created under this year group.");
			if (toConfirm==true)
			{
				var form_data = 
					  'yearGroupId='+yearGroupId;
					  
					$.ajax({
						url: "deleteYearGroup.php",
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
								 $('.message1').html('<i class="fa fa-check"></i> ' + yearGroupName + ' Year group successfully deleted.').fadeIn('slow');
								 $('#example').DataTable().clear().destroy();
								 callTable();
							}
							else if (html==2)	//If deletion is unsuccessful	
							{                              
								 $('.message1').html("");
								 $('.message2').html('<i class="fa fa-times"></i> Could not delete Year group. Please try again.').fadeIn('slow');
							}
						}
									
					});
			}	
		}
		
		function editYearGroupName()
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
					
			var sectionId = document.getElementById("section1").value;
			var sectionName = $('#section1 option:selected').text();
			var yearGroupId = $('input[name=yearGroupId]').val();
			var newYearGroupName = $('input[name=YearGroupName1]').val();
			
			if(sectionId == "")
			{
				$('.message3').html('Please select section');
			}
			else if (newYearGroupName == "")
			{
				$('.message3').html('Please enter name of Year group');
			}
			else
			{
				$.ajax({
					url: "editYearGroup.php",
					type: "POST",       
					data: {sectionId:sectionId, yearGroupId:yearGroupId, newYearGroupName:newYearGroupName},    
					success: function (html) {             								
						if (html==0) {                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message3').html("");
							$('.message1').html('<i class="fa fa-check"></i> successfully edited to ' + newYearGroupName + " (" + sectionName + ")").fadeIn('slow');
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
							$('.message3').html('<i class="fa fa-times"></i> The Year group ' + newYearGroupName + ' (' + sectionName + ') already exists. You can make changes to the section or Year Group name.').fadeIn('slow');
						}
					}
				});
			}
		}
		
		//function call to open modal for editing a category
		function openEditModal(yearGroupId, yearGroupName, sectionId, sectionName)
        {	
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
            var modal1 = document.getElementById("myModal1");
			$("#section1 option[value=" + sectionId +"]").prop("selected", true)
			document.getElementById("yearGroupId").value=yearGroupId;  
			document.getElementById("YearGroupName1").value=yearGroupName;  
            $("#yearGroupNameToEdit").html(yearGroupName + " (" + sectionName + ")");  
                
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
