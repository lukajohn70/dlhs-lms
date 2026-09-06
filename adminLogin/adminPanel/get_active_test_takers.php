<?php
session_start();
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/online_tracking_schema.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    die('Unauthorized');
}

dlhsEnsureOnlineTrackingTables($connection);

// Get active test takers
$query = "
    SELECT 
        studentId,
        studentName,
        testId,
        testName,
        startedAt,
        lastActivityAt,
        remainingTime,
        questionsAnswered,
        totalQuestions,
        ipAddress
    FROM active_test_takers
    WHERE isActive = 1
    ORDER BY startedAt DESC
";

$result = $connection->query($query);

if ($result->num_rows == 0) {
    echo '<div class="no-users">
            <i class="fa fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 10px;"></i>
            <p>No students are currently taking tests</p>
          </div>';
    exit;
}

echo '<style>
    .test-taker-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 1px solid #fee2e2;
        border-radius: 8px;
        margin-bottom: 12px;
        background: #fef2f2;
        transition: all 0.2s;
    }
    
    .test-taker-row:hover {
        border-color: #fecaca;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
    }
    
    .test-info {
        flex: 1;
    }
    
    .test-name {
        font-weight: 600;
        color: #991b1b;
        margin-bottom: 6px;
        font-size: 15px;
    }
    
    .student-name {
        color: #2d3748;
        margin-bottom: 4px;
        font-size: 14px;
    }
    
    .test-meta {
        font-size: 12px;
        color: #718096;
    }
    
    .test-progress {
        text-align: right;
    }
    
    .progress-text {
        font-size: 12px;
        color: #718096;
        margin-bottom: 4px;
    }
    
    .progress-bar-container {
        width: 150px;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
        margin: 4px 0;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #ef4444, #dc2626);
        border-radius: 4px;
        transition: width 0.3s;
    }
    
    .time-remaining {
        font-size: 13px;
        font-weight: 600;
        color: #dc2626;
    }
</style>';

while ($row = $result->fetch_assoc()) {
    $initials = getInitials($row['studentName']);
    $timeAgo = getTimeAgo($row['startedAt']);
    $lastActive = getTimeAgo($row['lastActivityAt']);
    
    $progress = $row['totalQuestions'] > 0 ? ($row['questionsAnswered'] / $row['totalQuestions']) * 100 : 0;
    $progressPercent = round($progress, 1);
    
    $remainingMinutes = $row['remainingTime'] ? floor($row['remainingTime'] / 60) : 0;
    $remainingSeconds = $row['remainingTime'] ? $row['remainingTime'] % 60 : 0;
    $timeDisplay = $row['remainingTime'] ? "{$remainingMinutes}m {$remainingSeconds}s left" : "Time not tracked";
    
    echo '<div class="test-taker-row">
            <div class="user-info">
                <div class="user-avatar" style="background: linear-gradient(135deg, #ef4444, #dc2626);">' . htmlspecialchars($initials) . '</div>
                <div class="test-info">
                    <div class="test-name"><i class="fa fa-file-text"></i> ' . htmlspecialchars($row['testName']) . '</div>
                    <div class="student-name"><i class="fa fa-user"></i> ' . htmlspecialchars($row['studentName']) . '</div>
                    <div class="test-meta">
                        <i class="fa fa-clock-o"></i> Started ' . $timeAgo . ' &nbsp;•&nbsp; 
                        <i class="fa fa-circle"></i> Last active ' . $lastActive . ' &nbsp;•&nbsp; 
                        <i class="fa fa-map-marker"></i> ' . htmlspecialchars($row['ipAddress']) . '
                    </div>
                </div>
            </div>
            <div class="test-progress">
                <div class="progress-text">' . $row['questionsAnswered'] . ' of ' . $row['totalQuestions'] . ' questions</div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: ' . $progressPercent . '%"></div>
                </div>
                <div class="time-remaining"><i class="fa fa-hourglass-half"></i> ' . $timeDisplay . '</div>
            </div>
          </div>';
}

function getInitials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' min ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    } else {
        return date('M j, g:i A', $time);
    }
}
?>
