<?php
// Modern Header for Admin Dashboard
?>
<header class="header dark-bg" style="border-bottom: 1px solid rgba(255,255,255,0.1); height: 60px; display: flex; align-items: center; padding: 0 20px; position: relative; z-index: 10000;">
    <div class="toggle-nav" style="cursor: pointer; margin-right: 20px; font-size: 24px; color: #fff; padding: 15px; margin-left: -15px; touch-action: manipulation;">
        <i class="fa fa-bars"></i>
    </div>
    
    <!--logo start-->
    <a href="index.php" class="logo" style="color: #fff; font-size: 20px; font-weight: 700; text-transform: uppercase; text-decoration: none;">
        DLHS <span class="lite" style="color: #00a0df;">Admin</span>
    </a>
    <!--logo end-->

    <div class="top-nav notification-row" style="margin-left: auto;">                
        <ul class="nav pull-right top-menu">
            <li class="dropdown">
                <a href="logout.php" style="color: #fff; text-decoration: none;">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</header>
