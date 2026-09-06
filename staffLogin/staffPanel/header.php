<?php
	//function to get the number of already taken tests for the notification area.
	function getPending($connection)
	{
		$testStatus=0; //status of not yet started tests.
		$staffId= isset($_SESSION['staffId']) ? $_SESSION['staffId'] : null;
		
		if($staffId){
			$query41 = "SELECT * FROM tests WHERE status='$testStatus' AND staffId='$staffId'";
		}else{
			$query41 = "SELECT * FROM tests WHERE status='$testStatus'";
		}
		$result41 = $connection->query($query41);
		
		$totalPendingreturned=$result41->num_rows;
		return $totalPendingreturned;		
	}
	
	//function to get the number of tests in progress.
	function getInProgress($connection)
	{
		$testStatus=1; //status of not yet started tests.
		$staffId= isset($_SESSION['staffId']) ? $_SESSION['staffId'] : null;
		
		if($staffId){
			$query42 = "SELECT * FROM tests WHERE status='$testStatus' AND staffId='$staffId'";
		}else{
			$query42 = "SELECT * FROM tests WHERE status='$testStatus'";
		}
		$result42 = $connection->query($query42);
		
		$totalInProgress=$result42->num_rows;
		return $totalInProgress;			
	}
	
	//function to get the number of completed and submitted tests for the notification area.
	function getTaken($connection)
	{
		$testStatus=2; //status of not yet started tests.
		$staffId= isset($_SESSION['staffId']) ? $_SESSION['staffId'] : null;
		
		if($staffId){
			$query43 = "SELECT * FROM tests WHERE status='$testStatus' AND staffId='$staffId'";
		}else{
			$query43 = "SELECT * FROM tests WHERE status='$testStatus'";
		}
		$result43 = $connection->query($query43);
		
		$totalTakenreturned=$result43->num_rows;
		return $totalTakenreturned;		
	}
	
	$numberPending=getPending($connection);
	$numberInProgress=getInProgress($connection);
	$numberTaken=getTaken($connection);
?>

<header class="header dark-bg" style="position: relative; z-index: 10000;">
    <div class="toggle-nav" style="padding: 15px; margin-left: -15px; cursor: pointer; touch-action: manipulation;">
        <div class="icon-reorder tooltips" data-original-title="Toggle Navigation" data-placement="bottom" style="font-size: 24px;"><i class="icon_menu"></i></div>
    </div>

    <!--logo start-->
    <a href="javascript:void(0)" class="logo">DLHS <span class="lite">Kaduna</span></a>
    <!--logo end-->

    <div class="top-nav notification-row">                
    <!-- notificatoin dropdown start-->
        <ul class="nav pull-right top-menu">
        <!-- task notificatoin start -->
			<li id="task_notificatoin_bar" class="dropdown">
                <a data-toggle="dropdown" class="dropdown-toggle" href="javascript:void(0)">
                    <i class="icon-task-l"></i>
                    <span class="badge bg-important">3</span>
                </a>
                <ul class="dropdown-menu extended tasks-bar">
                    <div class="notify-arrow notify-arrow-blue"></div>
                        <li>
                            <p class="blue">Tests Notifications</p>
                        </li>
						<li>
                            <a href="javascript:void(0)">
                                <div class="task-info">
                                    <div class="desc">Not yet started</div>
									<div class="percent"><?php echo $numberPending; ?></div>
                                </div>
                                <div class="progress progress-striped">
                                    <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                                    </div>
                                </div>
                            </a>
                        </li>
						<li>
                            <a href="javascript:void(0)">
                                <div class="task-info">
                                    <div class="desc">In progress</div>
									<div class="percent"><?php echo $numberInProgress; ?></div>
                                </div>
                                <div class="progress progress-striped">
                                    <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)">
                                <div class="task-info">
                                    <div class="desc">Completed & Submitted </div>
                                    <div class="percent"><?php echo $numberTaken; ?></div>
                                </div>
                                <div class="progress progress-striped">
									<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="4" aria-valuemin="0" aria-valuemax="15" style="width: 100%">
                                    </div>
                                </div>
							</a>
                        </li>
                </ul>
                    </li>
                    <!-- task notificatoin end -->
                                    
                    <!-- user login dropdown start-->
                    <li class="dropdown">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="javascript:void(0)">
                            <span class="profile-ava">
                                <img alt="" src="<?php echo isset($_SESSION['passportNameAndLocation']) ? $_SESSION['passportNameAndLocation'] : '../images/dlhslogo3.jpg'; ?>" style="width:30px;">
                            </span>
                            <span class="username"><?php echo isset($_SESSION['staffName']) ? $_SESSION['staffName'] : 'Admin'; ?></span>
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu extended logout">
                            <div class="log-arrow-up"></div>
                            <li class="eborder-top">
                                <a href="javascript:void(0)"><i class="icon_profile"></i> My Profile</a>
                            </li>
                            <li>
                                <a href="javascript:void(0)"><i class="icon_mail_alt"></i> My Inbox</a>
                            </li>
							<li>
                                <a href="changePassword.php"><i class="icon_key_alt"></i> Change Password</a>
                            </li>
                            <li>
                                <a href="logout.php"><i class="fa fa-power-off"></i> Logout</a>
                            </li>
                        </ul>
                    </li>
                    <!-- user login dropdown end -->
                </ul>
                <!-- notificatoin dropdown end-->
            </div>
      </header>      
      <!--header end-->