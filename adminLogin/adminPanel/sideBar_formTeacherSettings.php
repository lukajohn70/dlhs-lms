<!--sidebar start-->
<script>
	function setNext()
	{
		<?php
			$setTo=1;
			$_SESSION['setNext']= $setTo;
		?>
	}
</script>
<aside>
    <div id="sidebar"  class="nav-collapse ">
        <!-- sidebar menu start-->
        <ul class="sidebar-menu">                
            <li>
                <a class="" href="index.php">
					<i class="icon_house_alt"></i>
					<span>Dashboard</span>
                </a>
            </li>
			<li>
                <a class="" href="addTestForm.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Manage Test/Exam</span>
                </a>
            </li>
			<li class="sub-menu">
                <a href="javascript:;" class="">
                    <i class="icon_document_alt"></i>
                    <span>Manage Quest.</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
                    <li><a class="" href="addQuestionForm.php" onclick="setNext()">Add Test Questions</a></li> 
					<li><a class="" href="viewQuestionForm.php" onclick="setNext()">View Test Questions</a></li>    					
                </ul>
            </li>
			<li class="sub-menu">
                <a href="javascript:;" class="">
                    <i class="icon_document_alt"></i>
                    <span>Students & Test</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
                    <li><a class="" href="addStudentsToTestForm.php" onclick="setNext()" style="font-size:13px;">Manage Students & Test</a></li>					
                </ul>
            </li>
			<li class="sub-menu active">
                <a href="javascript:;" class="">
                    <i class="icon_document_alt"></i>
                    <span style="font-size:14px;">Examinees' Status</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
                    <li><a class="" href="examineesStatus.php" style="background-color:red; color:white;" onclick="setNext()">Examinees' Status</a></li>                          
                </ul>
            </li>

			<li class="sub-menu">
                <a href="javascript:;" class="" style="font-size:15px;">
                    <i class="icon_documents_alt"></i>
                    <span>Result processing</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
					<li><a class="" href="formTeacherBusinessForm.php">Form teacher business</a></li>
                    <li><a class="" href="resultSettingsForm.php">Result settings</a></li>
					<li><a class="" href="manageGradingForm.php">Manage Grading</a></li>
					<li><a class="" href="manageCharacterForm.php">Manage Character</a></li>
					<li><a class="" href="managePsychomotorForm.php">Manage Psychomotor</a></li>
					<li><a class="" href="manageHouseForm.php">Manage House</a></li>
					<li><a class="" href="manageSportForm.php">Manage Sport Activities</a></li>
					<li><a class="" href="generateResultForm.php">Generate result</a></li>
                </ul>
            </li>			
			<li>                     
                <a class="" href="logout.php">
                    <i class="fa fa-power-off"></i>
                    <span>Logout</span>
                </a>
			</li>
        </ul>
    <!-- sidebar menu end-->
    </div>
</aside>
<!--sidebar end-->

