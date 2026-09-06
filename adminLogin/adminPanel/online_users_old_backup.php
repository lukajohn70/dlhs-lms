<?php
session_start();
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/online_tracking_schema.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    header("Location: ../index.php");
    exit();
}

dlhsEnsureOnlineTrackingTables($connection);

// Get online users count by type
$statsQuery = "
    SELECT 
        userType,
        COUNT(*) as count
    FROM online_users
    WHERE isActive = 1
    GROUP BY userType
";

$stats = [
    'student' => 0,
    'staff' => 0,
    'admin' => 0,
    'total' => 0
];

$result = $connection->query($statsQuery);
while ($row = $result->fetch_assoc()) {
    $stats[$row['userType']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Get active test takers count
$activeTestsQuery = "SELECT COUNT(DISTINCT studentId) as count FROM active_test_takers WHERE isActive = 1";
$result = $connection->query($activeTestsQuery);
$activeTestTakers = $result->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Users - DLHS Admin</title>
    <link href="../../bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../../fontAwesome/font-awesome.min.css" rel="stylesheet">
    <link href="sideBar_style.css" rel="stylesheet">
    <link href="../../datatables/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            position: relative;
        }
        
        .stat-card.students {
            border-left-color: #3b82f6;
        }
        
        .stat-card.staff {
            border-left-color: #10b981;
        }
        
        .stat-card.admin {
            border-left-color: #f59e0b;
        }
        
        .stat-card.tests {
            border-left-color: #ef4444;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            opacity: 0.2;
        }
        
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            background: #5568d3;
        }
        
        .online-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .user-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        
        .user-row:hover {
            background: #f8f9fa;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .user-details h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .user-details p {
            margin: 2px 0 0 0;
            font-size: 12px;
            color: #718096;
        }
        
        .user-status {
            text-align: right;
        }
        
        .time-ago {
            font-size: 12px;
            color: #718096;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-student {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-staff {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-admin {
            background: #fed7aa;
            color: #92400e;
        }
        
        .badge-testing {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #718096;
        }
        
        .refresh-indicator {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include 'sideBar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fa fa-users"></i> Online Users & Activity</h1>
            <p>Real-time monitoring of students, staff, and active test takers</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card students">
                <i class="fa fa-graduation-cap stat-icon"></i>
                <div class="stat-label">Students Online</div>
                <div class="stat-value" id="studentCount"><?php echo $stats['student']; ?></div>
            </div>
            <div class="stat-card staff">
                <i class="fa fa-user-md stat-icon"></i>
                <div class="stat-label">Staff Online</div>
                <div class="stat-value" id="staffCount"><?php echo $stats['staff']; ?></div>
            </div>
            <div class="stat-card admin">
                <i class="fa fa-user-circle stat-icon"></i>
                <div class="stat-label">Admins Online</div>
                <div class="stat-value" id="adminCount"><?php echo $stats['admin']; ?></div>
            </div>
            <div class="stat-card tests">
                <i class="fa fa-file-text stat-icon"></i>
                <div class="stat-label">Taking Tests Now</div>
                <div class="stat-value" id="testTakerCount"><?php echo $activeTestTakers; ?></div>
            </div>
        </div>
        
        <!-- All Users List (Collapsible) -->
        <div class="section-card">
            <div class="section-header" style="cursor: pointer;" onclick="toggleAllUsersList()">
                <div>
                    <span class="section-title">
                        <i class="fa fa-list" id="allUsersIcon"></i>
                        All Online Users
                    </span>
                    <span class="badge" style="background: #667eea; color: white;" id="totalUsersBadge"><?php echo $stats['total']; ?> Total</span>
                    <span id="allUsersAutoRefresh" style="display: none; font-size: 12px; color: #718096; margin-left: 15px;">
                        <span class="refresh-indicator" style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block; margin-right: 5px;"></span>
                        Auto-refreshing
                    </span>
                </div>
                <button class="refresh-btn" onclick="event.stopPropagation(); loadAllUsers()">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
            <div id="allUsersList" style="display: none;">
                <!-- Loaded via AJAX -->
            </div>
        </div>
        
        <!-- Active Test Takers -->
        <div class="section-card">
            <div class="section-header">
                <div>
                    <span class="section-title">
                        <span class="online-indicator"></span>
                        Students Taking Tests Now
                    </span>
                    <span class="badge badge-testing" id="activeTestsBadge"><?php echo $activeTestTakers; ?> Active</span>
                </div>
                <div class="auto-refresh">
                    <span class="refresh-indicator"></span>
                    Auto-refreshing every 10s
                </div>
            </div>
            <div id="activeTestTakers">
                <!-- Loaded via AJAX -->
            </div>
        </div>
        
        <!-- Online Students -->
        <div class="section-card">
            <div class="section-header">
                <div>
                    <span class="section-title">Online Students</span>
                    <span class="badge badge-student" id="studentsBadge"><?php echo $stats['student']; ?> Online</span>
                </div>
                <button class="refresh-btn" onclick="loadOnlineUsers('student')">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
            <div id="onlineStudents">
                <!-- Loaded via AJAX -->
            </div>
        </div>
        
        <!-- Online Staff -->
        <div class="section-card">
            <div class="section-header">
                <div>
                    <span class="section-title">Online Staff</span>
                    <span class="badge badge-staff" id="staffBadge"><?php echo $stats['staff']; ?> Online</span>
                </div>
                <button class="refresh-btn" onclick="loadOnlineUsers('staff')">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
            <div id="onlineStaff">
                <!-- Loaded via AJAX -->
            </div>
        </div>
        
        <!-- Online Admins -->
        <div class="section-card">
            <div class="section-header">
                <div>
                    <span class="section-title">Online Administrators</span>
                    <span class="badge badge-admin" id="adminBadge"><?php echo $stats['admin']; ?> Online</span>
                </div>
                <button class="refresh-btn" onclick="loadOnlineUsers('admin')">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
            <div id="onlineAdmins">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <script src="../../libs/jquery.min.js"></script>
    <script>
        // Load online users by type
        function loadOnlineUsers(userType) {
            $.ajax({
                url: 'get_online_users.php',
                type: 'GET',
                data: { type: userType },
                success: function(response) {
                    $('#online' + userType.charAt(0).toUpperCase() + userType.slice(1) + 's').html(response);
                }
            });
        }
        
        // Load active test takers
        function loadActiveTestTakers() {
            $.ajax({
                url: 'get_active_test_takers.php',
                type: 'GET',
                success: function(response) {
                    $('#activeTestTakers').html(response);
                }
            });
        }
        
        // Update statistics
        function updateStats() {
            $.ajax({
                url: 'get_online_stats.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#studentCount').text(data.student);
                    $('#staffCount').text(data.staff);
                    $('#adminCount').text(data.admin);
                    $('#testTakerCount').text(data.testTakers);
                    
                    $('#studentsBadge').text(data.student + ' Online');
                    $('#staffBadge').text(data.staff + ' Online');
                    $('#adminBadge').text(data.admin + ' Online');
                    $('#activeTestsBadge').text(data.testTakers + ' Active');
                    $('#totalUsersBadge').text((data.student + data.staff + data.admin) + ' Total');
                }
            });
        }
        
        // Toggle All Users List
        function toggleAllUsersList() {
            var list = $('#allUsersList');
            var icon = $('#allUsersIcon');
            var autoRefreshIndicator = $('#allUsersAutoRefresh');
            
            if (list.is(':visible')) {
                list.slideUp();
                icon.removeClass('fa-chevron-up').addClass('fa-list');
                autoRefreshIndicator.fadeOut();
            } else {
                list.slideDown();
                icon.removeClass('fa-list').addClass('fa-chevron-up');
                autoRefreshIndicator.fadeIn();
                loadAllUsers(); // Load data when expanding
            }
        }
        
        // Load all users combined
        function loadAllUsers() {
            $.ajax({
                url: 'get_online_users.php',
                type: 'GET',
                data: { type: 'all' },
                success: function(response) {
                    $('#allUsersList').html(response);
                }
            });
        }
        
        // Initial load
        $(document).ready(function() {
            loadOnlineUsers('student');
            loadOnlineUsers('staff');
            loadOnlineUsers('admin');
            loadActiveTestTakers();
            
            // Auto-refresh every 10 seconds
            setInterval(function() {
                loadOnlineUsers('student');
                loadOnlineUsers('staff');
                loadOnlineUsers('admin');
                loadActiveTestTakers();
                updateStats();
                
                // Refresh "All Users" list if it's currently visible
                if ($('#allUsersList').is(':visible')) {
                    loadAllUsers();
                }
            }, 10000);
        });
    </script>
</body>
</html>
