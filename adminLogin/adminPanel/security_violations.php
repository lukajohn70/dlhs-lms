<?php
session_start();
require_once 'userExpiredSession.php';
require_once '../../db_connection/dlhs_db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    header("Location: ../index.php");
    exit();
}

// Get filter parameters
$filterTestId = isset($_GET['testId']) ? intval($_GET['testId']) : 0;
$filterStudentId = isset($_GET['studentId']) ? intval($_GET['studentId']) : 0;
$filterViolationType = isset($_GET['violationType']) ? $_GET['violationType'] : '';
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$whereConditions = [];
$params = [];
$types = '';

if ($filterTestId > 0) {
    $whereConditions[] = "sv.testId = ?";
    $params[] = $filterTestId;
    $types .= 'i';
}

if ($filterStudentId > 0) {
    $whereConditions[] = "sv.studentId = ?";
    $params[] = $filterStudentId;
    $types .= 'i';
}

if ($filterViolationType) {
    $whereConditions[] = "sv.violation LIKE ?";
    $params[] = "%{$filterViolationType}%";
    $types .= 's';
}

if ($filterDate) {
    $whereConditions[] = "DATE(sv.timestamp) = ?";
    $params[] = $filterDate;
    $types .= 's';
}

if ($searchTerm) {
    $whereConditions[] = "(CONCAT(s.firstName, ' ', s.surname) LIKE ? OR t.testName LIKE ? OR sv.violation LIKE ?)";
    $searchPattern = "%{$searchTerm}%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $types .= 'sss';
}

$whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get violations
$query = "
    SELECT 
        sv.*,
        CONCAT(s.firstName, ' ', s.surname) as studentName,
        t.testName
    FROM security_violations sv
    LEFT JOIN studentlogin s ON sv.studentId = s.studentId
    LEFT JOIN tests t ON sv.testId = t.testId
    $whereClause
    ORDER BY sv.timestamp DESC
    LIMIT 500
";

$stmt = $connection->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$violations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as totalViolations,
        COUNT(DISTINCT studentId) as totalStudents,
        COUNT(DISTINCT testId) as totalTests,
        SUM(CASE WHEN autoSubmit = 1 THEN 1 ELSE 0 END) as autoSubmits,
        SUM(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 ELSE 0 END) as todayViolations
    FROM security_violations
";
$statsResult = $connection->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Get top violators
$topViolatorsQuery = "
    SELECT 
        sv.studentId,
        CONCAT(s.firstName, ' ', s.surname) as studentName,
        COUNT(*) as violationCount,
        MAX(sv.timestamp) as lastViolation
    FROM security_violations sv
    LEFT JOIN studentlogin s ON sv.studentId = s.studentId
    GROUP BY sv.studentId
    ORDER BY violationCount DESC
    LIMIT 10
";
$topViolators = $connection->query($topViolatorsQuery)->fetch_all(MYSQLI_ASSOC);

// Get violation types distribution
$violationTypesQuery = "
    SELECT 
        CASE
            WHEN violation LIKE '%screenshot%' OR violation LIKE '%PrintScreen%' THEN 'Screenshot Attempts'
            WHEN violation LIKE '%fullscreen%' THEN 'Fullscreen Violations'
            WHEN violation LIKE '%tab%' OR violation LIKE '%window%' OR violation LIKE '%switch%' THEN 'Tab/Window Switching'
            WHEN violation LIKE '%right%click%' THEN 'Right-Click Attempts'
            WHEN violation LIKE '%copy%' OR violation LIKE '%paste%' THEN 'Copy/Paste Attempts'
            WHEN violation LIKE '%print%' THEN 'Print Attempts'
            WHEN violation LIKE '%DevTools%' OR violation LIKE '%F12%' THEN 'DevTools Attempts'
            WHEN violation LIKE '%top area%' THEN 'Restore Button Attempts'
            ELSE 'Other Violations'
        END as violationType,
        COUNT(*) as count
    FROM security_violations
    GROUP BY violationType
    ORDER BY count DESC
";
$violationTypes = $connection->query($violationTypesQuery)->fetch_all(MYSQLI_ASSOC);

// Get all tests for filter dropdown
$testsQuery = "SELECT testId, testName FROM tests ORDER BY testName";
$tests = $connection->query($testsQuery)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Security Violations Log - DLHS Admin">
    <link rel="shortcut icon" href="../images/dlhslogo3.jpg">

    <title>Security Violations | DLHS Admin</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link href="../../datatables/css/dataTables.bootstrap.min.css" rel="stylesheet">
    
    <style>
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 32px;
            color: #2c3e50;
            font-weight: bold;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
        }
        .stat-card.danger { border-left-color: #e74c3c; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-card.info { border-left-color: #3498db; }
        .stat-card.success { border-left-color: #2ecc71; }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .violation-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .violation-badge.high { background: #e74c3c; color: white; }
        .violation-badge.medium { background: #f39c12; color: white; }
        .violation-badge.low { background: #3498db; color: white; }
        
        .auto-submit-badge {
            background: #c0392b;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .table-responsive {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .violation-detail {
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
  <section id="container" class="">
    <?php include 'header.php'; ?>
    <?php include 'sideBar.php'; ?>
           
    <section id="main-content">
        <section class="wrapper">            
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header"><i class="fa fa-shield"></i> Security Violations Log</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li><i class="fa fa-shield"></i>Security Violations</li>
                    </ol>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card danger">
                        <h3><?php echo number_format((float)($stats['totalViolations'] ?? 0)); ?></h3>
                        <p>Total Violations</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <h3><?php echo number_format((float)($stats['totalStudents'] ?? 0)); ?></h3>
                        <p>Students with Violations</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card info">
                        <h3><?php echo number_format((float)($stats['todayViolations'] ?? 0)); ?></h3>
                        <p>Today's Violations</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <h3><?php echo number_format((float)($stats['autoSubmits'] ?? 0)); ?></h3>
                        <p>Auto-Submitted Exams</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="filter-section">
                        <h4 style="margin-top:0;">
                            <i class="fa fa-filter"></i> Filters & Search
                        </h4>
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Test</label>
                                        <select name="testId" class="form-control">
                                            <option value="0">All Tests</option>
                                            <?php foreach ($tests as $test): ?>
                                                <option value="<?php echo $test['testId']; ?>" 
                                                    <?php echo $filterTestId == $test['testId'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($test['testName']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Violation Type</label>
                                        <select name="violationType" class="form-control">
                                            <option value="">All Types</option>
                                            <option value="screenshot" <?php echo $filterViolationType == 'screenshot' ? 'selected' : ''; ?>>Screenshot Attempts</option>
                                            <option value="fullscreen" <?php echo $filterViolationType == 'fullscreen' ? 'selected' : ''; ?>>Fullscreen Violations</option>
                                            <option value="tab" <?php echo $filterViolationType == 'tab' ? 'selected' : ''; ?>>Tab Switching</option>
                                            <option value="right-click" <?php echo $filterViolationType == 'right-click' ? 'selected' : ''; ?>>Right-Click</option>
                                            <option value="copy" <?php echo $filterViolationType == 'copy' ? 'selected' : ''; ?>>Copy/Paste</option>
                                            <option value="print" <?php echo $filterViolationType == 'print' ? 'selected' : ''; ?>>Print Attempts</option>
                                            <option value="DevTools" <?php echo $filterViolationType == 'DevTools' ? 'selected' : ''; ?>>DevTools</option>
                                            <option value="top area" <?php echo $filterViolationType == 'top area' ? 'selected' : ''; ?>>Restore Button</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filterDate); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Search</label>
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Student name, test name..." 
                                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php if ($filterTestId || $filterStudentId || $filterViolationType || $filterDate || $searchTerm): ?>
                            <a href="security_violations.php" class="btn btn-default btn-sm">
                                <i class="fa fa-times"></i> Clear Filters
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Violation Types Chart -->
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                        <h4><i class="fa fa-pie-chart"></i> Violation Types Distribution</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Violation Type</th>
                                    <th width="100">Count</th>
                                    <th width="150">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalCount = array_sum(array_column($violationTypes, 'count'));
                                foreach ($violationTypes as $type): 
                                    $percentage = $totalCount > 0 ? ($type['count'] / $totalCount) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['violationType']); ?></td>
                                    <td><strong><?php echo $type['count']; ?></strong></td>
                                    <td>
                                        <div class="progress" style="margin:0;">
                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="table-responsive">
                        <h4><i class="fa fa-users"></i> Top Violators</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th width="100">Violations</th>
                                    <th width="150">Last Violation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topViolators as $violator): ?>
                                <tr>
                                    <td>
                                        <a href="?studentId=<?php echo $violator['studentId']; ?>">
                                            <?php echo htmlspecialchars($violator['studentName']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="violation-badge <?php 
                                            echo $violator['violationCount'] > 10 ? 'high' : 
                                                ($violator['violationCount'] > 5 ? 'medium' : 'low'); 
                                        ?>">
                                            <?php echo $violator['violationCount']; ?>
                                        </span>
                                    </td>
                                    <td class="violation-detail">
                                        <?php echo date('M j, g:i A', strtotime($violator['lastViolation'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Violations Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="table-responsive">
                        <h4>
                            <i class="fa fa-list"></i> Violation Records 
                            <span class="badge" style="background: #3498db;">
                                <?php echo count($violations); ?> records
                            </span>
                        </h4>
                        <table class="table table-bordered table-striped table-hover" id="violationsTable">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Student</th>
                                    <th>Test</th>
                                    <th>Violation</th>
                                    <th width="100">Count</th>
                                    <th width="150">Timestamp</th>
                                    <th width="100">IP Address</th>
                                    <th width="80">Auto-Submit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rowNum = 1;
                                foreach ($violations as $violation): 
                                ?>
                                <tr>
                                    <td><?php echo $rowNum++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($violation['studentName'] ?? 'Unknown'); ?></strong>
                                        <br>
                                        <small class="violation-detail">ID: <?php echo $violation['studentId']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($violation['testName'] ?? 'Unknown'); ?>
                                        <br>
                                        <small class="violation-detail">
                                            Test ID: <?php echo $violation['testId']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($violation['violation']); ?></strong>
                                        <?php if ($violation['screenResolution']): ?>
                                        <br>
                                        <small class="violation-detail">
                                            Screen: <?php echo htmlspecialchars($violation['screenResolution']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="violation-badge <?php 
                                            echo $violation['violationCount'] > 5 ? 'high' : 
                                                ($violation['violationCount'] > 2 ? 'medium' : 'low'); 
                                        ?>">
                                            <?php echo $violation['violationCount']; ?>
                                        </span>
                                    </td>
                                    <td class="violation-detail">
                                        <?php echo date('M j, Y', strtotime($violation['timestamp'])); ?>
                                        <br>
                                        <?php echo date('g:i:s A', strtotime($violation['timestamp'])); ?>
                                    </td>
                                    <td class="violation-detail">
                                        <?php echo htmlspecialchars($violation['ipAddress']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($violation['autoSubmit']): ?>
                                            <span class="auto-submit-badge">YES</span>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">No</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </section>
    </section>
    
    <div class="text-right">
        <div class="credits">
            <?php include "footer.php"; ?>
        </div>
    </div>
  </section>

  <script src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.scrollTo.min.js"></script>
  <script src="js/jquery.nicescroll.js"></script>
  <script src="../../datatables/js/jquery.dataTables.min.js"></script>
  <script src="../../datatables/js/dataTables.bootstrap.min.js"></script>
  <script src="js/scripts.js"></script>
  
  <script>
    $(document).ready(function() {
        $('#violationsTable').DataTable({
            "order": [[ 5, "desc" ]],
            "pageLength": 50,
            "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]]
        });
    });
  </script>
</body>
</html>

