<script>
	function setNext()
	{
		<?php
			$setTo=1;
			$_SESSION['setNext']= $setTo;
		?>
	}
</script>
<!--sidebar start-->
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
                <a class="" href="setCalendarYear.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Set Calendar Year</span>
                </a>
            </li>
			<li>
                <a class="" href="addStaffForm.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Staff</span>
                </a>
            </li>
			<li>
                <a class="" href="addYearGroupForm.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Year Groups</span>
                </a>
            </li>
			<li>
                <a class="" href="addClassForm.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Classes</span>
                </a>
            </li>
			<li>
                <a class="" href="addStudentForm.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Students</span>
                </a>
            </li>
			<li>
                <a class="" href="addSubjectForm.php" onclick="setNext()">
					<i class="icon_document_alt"></i>
					<span>Subjects</span>
                </a>
            </li>
			<li>
                <a class="" href="subjectAssignment.php" onclick="setNext()">
					<i class="icon_group"></i>
					<span>Subject Assignment</span>
                </a>
            </li>
			<li>                     
                <a class="" href="#">
                    <i class="icon_piechart"></i>
                    <span>Charts</span>
                </a>
			</li>
            <li class="sub-menu">
                <a href="javascript:;" class="">
                    <i class="icon_documents_alt"></i>
                    <span>Queries</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
                    <li><a class="" href="reportQueries.php">Query Reports</a></li>
					<li><a class="" href="namesAndIds.php">Names & IDs</a></li>
                </ul>
            </li>
            <li class="sub-menu active">
                <a href="javascript:;" class="">
                    <i class="icon_documents_alt"></i>
                    <span>Tests</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
					<li><a class="" href="allTestsForm.php">All Tests</a></li>
                    <li class="active"><a class="" href="view_teacher_questions.php">View Teacher Questions</a></li>
                    <li><a class="" href="/dlhs/staffLogin/staffPanel/addTestForm.php" target="_blank">Create Test</a></li>
                    <li><a class="" href="yetToBeStartedTests.php">Yet to be started</a></li>
					<li><a class="" href="testsInProgress.php">Tests in progress</a></li>
                    <li><a class="" href="endedTests.php">Tests ended</a></li>
                    <li><a class="" href="/dlhs/staffLogin/staffPanel/addQuestionForm.php" target="_blank">Add/Manage Questions</a></li>
                    <li><a class="" href="/dlhs/staffLogin/staffPanel/addStudentsToTestForm.php" target="_blank">Manage Students for Test</a></li>

                </ul>
            </li>
			<li class="sub-menu">
                <a href="javascript:;" class="" style="font-size:15px;">
                    <i class="icon_documents_alt"></i>
                    <span>Result processing</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
					<li><a class="" href="assignFormTeachersForm.php">Form teachers</a></li>
                    <li><a class="" href="resultSettingsForm.php">Result settings</a></li>
					<li><a class="" href="manageGradingForm.php">Manage Grading</a></li>
					<li><a class="" href="manageCharacterForm.php">Manage Character</a></li>
					<li><a class="" href="managePsychomotorForm.php">Manage Psychomotor</a></li>
					<li><a class="" href="manageHouseForm.php">Manage House</a></li>
					<li><a class="" href="manageSportForm.php">Manage Sport Activities</a></li>
					<li><a class="" href="generateResultForm.php">Generate result</a></li>
                </ul>
            </li>
			<li class="sub-menu">
                <a href="javascript:;" class="">
                    <i class="icon_cogs"></i>
                    <span>Settings</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
					
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

