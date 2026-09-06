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
			<li class="active">
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
                    <i class="icon_desktop"></i>
                    <span>UI Elements</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
                    <li><a class="" href="general.php">Elements</a></li>
                    <li><a class="" href="buttons.php">Buttons</a></li>
                    <li><a class="" href="grids.php">Grids</a></li>
                </ul>
            </li>
            <li>
                <a class="" href="widgets.php">
                    <i class="icon_genius"></i>
                    <span>Widgets</span>
                </a>
            </li>
            <li>                     
                <a class="" href="chart-chartjs.php">
                    <i class="icon_piechart"></i>
                    <span>Charts</span>
                    
                </a>
                
            </li>
                     
            <li class="sub-menu">
                <a href="javascript:;" class="">
                    <i class="icon_table"></i>
                    <span>Tables</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">
                    <li><a class="" href="basic_table.php">Basic Table</a></li>
                </ul>
            </li>
            
            <li class="sub-menu">
                <a href="javascript:;" class="">
                    <i class="icon_documents_alt"></i>
                    <span>Pages</span>
                    <span class="menu-arrow arrow_carrot-right"></span>
                </a>
                <ul class="sub">                          
                    <li><a class="" href="profile.php">Profile</a></li>
                    <li><a class="" href="login.php"><span>Login Page</span></a></li>
                    <li><a class="" href="blank.php">Blank Page</a></li>
                    <li><a class="" href="404.php">404 Error</a></li>
                </ul>
            </li>
            
        </ul>
        <!-- sidebar menu end-->
    </div>
</aside>
<!--sidebar end-->

