<?php
	$numberPending = 0;
	$numberInProgress = 0;
	$numberTaken = 0;

	$headerStaffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
	$headerWhere = $headerStaffId > 0
		? "status IN (0, 1, 2) AND staffId = '{$headerStaffId}'"
		: "status IN (0, 1, 2)";

	if (isset($connection) && $connection instanceof mysqli) {
		$headerCountsRes = $connection->query("SELECT status, COUNT(*) AS cnt FROM tests WHERE {$headerWhere} GROUP BY status");
		if ($headerCountsRes) {
			while ($hRow = $headerCountsRes->fetch_assoc()) {
				$st = (int) $hRow['status'];
				if ($st === 0) {
					$numberPending = (int) $hRow['cnt'];
				} elseif ($st === 1) {
					$numberInProgress = (int) $hRow['cnt'];
				} elseif ($st === 2) {
					$numberTaken = (int) $hRow['cnt'];
				}
			}
		}
	}

	if (!function_exists('getPending')) {
		function getPending($connection) { global $numberPending; return $numberPending; }
	}
	if (!function_exists('getInProgress')) {
		function getInProgress($connection) { global $numberInProgress; return $numberInProgress; }
	}
	if (!function_exists('getTaken')) {
		function getTaken($connection) { global $numberTaken; return $numberTaken; }
	}
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