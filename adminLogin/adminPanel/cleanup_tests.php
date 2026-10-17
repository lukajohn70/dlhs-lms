<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
require_once 'userExpiredSession.php';

if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

$statusMessage = "";
$statusType = "";

// Fetch the 6 most recent tests to preserve
$preserveQuery = "SELECT * FROM tests ORDER BY testId DESC LIMIT 6";
$preserveResult = $connection->query($preserveQuery);
$preserveTests = [];
$preserveIds = [];
if ($preserveResult) {
    while ($row = $preserveResult->fetch_assoc()) {
        $preserveTests[] = $row;
        $preserveIds[] = $row['testId'];
    }
}

// Fetch all other tests that will be deleted
$deleteQuery = "SELECT * FROM tests";
if (!empty($preserveIds)) {
    $deleteQuery .= " WHERE testId NOT IN (" . implode(',', $preserveIds) . ")";
}
$deleteQuery .= " ORDER BY testId DESC";
$deleteResult = $connection->query($deleteQuery);
$deleteTests = [];
if ($deleteResult) {
    while ($row = $deleteResult->fetch_assoc()) {
        $deleteTests[] = $row;
    }
}

// Handle execution of cleanup
if (isset($_POST['execute_cleanup'])) {
    $deletedCount = 0;
    $tablesDropped = 0;
    $errors = [];
    
    if (empty($deleteTests)) {
        $statusMessage = "No tests require cleanup! The database is already pristine.";
        $statusType = "info";
    } else {
        $connection->autocommit(FALSE);
        try {
            foreach ($deleteTests as $test) {
                $testId = $test['testId'];
                $questionsTable = $test['tableName'];
                $examineesTable = $test['examineesTableName'];
                $answersTable = $test['answersTable'];
                
                // Drop Questions Table
                if (!empty($questionsTable)) {
                    $dropQuery = "DROP TABLE IF EXISTS `$questionsTable`";
                    if ($connection->query($dropQuery)) {
                        $tablesDropped++;
                    } else {
                        $errors[] = "Failed to drop questions table `$questionsTable` for test ID $testId";
                    }
                }
                
                // Drop Examinees Table
                if (!empty($examineesTable)) {
                    $dropQuery = "DROP TABLE IF EXISTS `$examineesTable`";
                    if ($connection->query($dropQuery)) {
                        $tablesDropped++;
                    } else {
                        $errors[] = "Failed to drop examinees table `$examineesTable` for test ID $testId";
                    }
                }
                
                // Drop Answers Table
                if (!empty($answersTable)) {
                    $dropQuery = "DROP TABLE IF EXISTS `$answersTable`";
                    if ($connection->query($dropQuery)) {
                        $tablesDropped++;
                    } else {
                        $errors[] = "Failed to drop answers table `$answersTable` for test ID $testId";
                    }
                }
                
                // Delete test record
                $deleteStmt = $connection->prepare("DELETE FROM tests WHERE testId = ?");
                $deleteStmt->bind_param("i", $testId);
                if ($deleteStmt->execute()) {
                    $deletedCount++;
                } else {
                    $errors[] = "Failed to delete test record for test ID $testId";
                }
                $deleteStmt->close();
            }
            
            if (empty($errors)) {
                $connection->commit();
                $statusMessage = "Cleanup successfully completed! Deleted <strong>$deletedCount</strong> tests and dropped <strong>$tablesDropped</strong> associated dynamic tables.";
                $statusType = "success";
                
                // Refresh lists
                $deleteTests = [];
            } else {
                $connection->rollback();
                $statusMessage = "Cleanup aborted due to errors: <br>" . implode("<br>", $errors);
                $statusType = "danger";
            }
        } catch (Exception $e) {
            $connection->rollback();
            $statusMessage = "Database exception occurred: " . $e->getMessage();
            $statusType = "danger";
        }
        $connection->autocommit(TRUE);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test Cleanup | DLHS Admin</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <style>
        body {
            font-family: 'Outfit', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
        }
        .cleanup-container {
            padding: 30px 15px;
        }
        .header-panel {
            background: linear-gradient(135deg, #1a5f7a, #0881a3);
            color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-panel h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header-panel p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .panel-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            background: white;
        }
        .panel-custom .panel-heading {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            padding: 15px 20px;
        }
        .preserve-heading {
            background-color: #28a745 !important;
            color: white !important;
        }
        .cleanup-heading {
            background-color: #dc3545 !important;
            color: white !important;
        }
        .test-card {
            border-left: 4px solid;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 6px;
            background-color: #fafafa;
            transition: transform 0.2s ease;
        }
        .test-card:hover {
            transform: translateY(-2px);
        }
        .preserve-card {
            border-left-color: #28a745;
        }
        .delete-card {
            border-left-color: #dc3545;
        }
        .test-title {
            font-weight: 700;
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        .test-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        .table-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-family: monospace;
            background-color: #eaeaea;
            color: #444;
            margin-right: 5px;
            margin-bottom: 4px;
        }
        .action-area {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .btn-cleanup {
            font-size: 18px;
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }
        .btn-cleanup:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.4);
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
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
                        <h3 class="page-header"><i class="fa fa-database"></i> Database Test Cleanup</h3>
                        <ol class="breadcrumb">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-database"></i>Database</li>
                            <li><i class="fa fa-trash"></i>Test Cleanup</li>
                        </ol>
                    </div>
                </div>

                <div class="cleanup-container">
                    <?php if (!empty($statusMessage)): ?>
                        <div class="alert alert-<?php echo $statusType; ?> alert-dismissible" role="alert" style="border-radius: 8px; font-size: 16px; padding: 20px;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?php echo $statusMessage; ?>
                        </div>
                    <?php endif; ?>

                    <div class="header-panel">
                        <h1>Smart Database Cleanup Tool</h1>
                        <p>Keep your school LMS database fast and neat. This tool will permanently remove all legacy/unused test records and drop their dynamically generated examinee, answer, and question tables, <strong>while preserving your 6 most recently created tests</strong>.</p>
                    </div>

                    <div class="row">
                        <!-- Left Column: Protected Tests -->
                        <div class="col-md-6">
                            <section class="panel panel-custom">
                                <header class="panel-heading preserve-heading">
                                    <i class="fa fa-shield"></i> Protected Tests (6 Most Recent)
                                </header>
                                <div class="panel-body">
                                    <?php if (empty($preserveTests)): ?>
                                        <div class="empty-state">
                                            <i class="fa fa-info-circle"></i>
                                            <p>No tests created yet.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($preserveTests as $index => $test): ?>
                                            <div class="test-card preserve-card">
                                                <div class="test-title">
                                                    <span class="label label-success" style="margin-right: 8px;">#<?php echo $index + 1; ?></span>
                                                    <?php echo htmlspecialchars($test['testName']); ?>
                                                </div>
                                                <div class="test-meta">
                                                    <i class="fa fa-calendar"></i> <?php echo htmlspecialchars($test['testDate']); ?> &nbsp;|&nbsp; 
                                                    <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($test['duration']); ?> mins
                                                </div>
                                                <div>
                                                    <span class="table-badge"><i class="fa fa-table"></i> Quest: <?php echo htmlspecialchars($test['tableName']); ?></span>
                                                    <span class="table-badge"><i class="fa fa-table"></i> Exam: <?php echo htmlspecialchars($test['examineesTableName']); ?></span>
                                                    <span class="table-badge"><i class="fa fa-table"></i> Answer: <?php echo htmlspecialchars($test['answersTable']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>

                        <!-- Right Column: Legacy Tests for Cleanup -->
                        <div class="col-md-6">
                            <section class="panel panel-custom">
                                <header class="panel-heading cleanup-heading">
                                    <i class="fa fa-trash"></i> Legacy Tests to be Cleaned Up
                                </header>
                                <div class="panel-body">
                                    <?php if (empty($deleteTests)): ?>
                                        <div class="empty-state">
                                            <i class="fa fa-check-circle" style="color: #28a745;"></i>
                                            <p style="font-weight: 700; color: #28a745;">Database is Clean!</p>
                                            <p>Only the 6 most recent tests (or fewer) exist in the database.</p>
                                        </div>
                                    <?php else: ?>
                                        <div style="max-height: 520px; overflow-y: auto; padding-right: 5px;">
                                            <?php foreach ($deleteTests as $test): ?>
                                                <div class="test-card delete-card">
                                                    <div class="test-title">
                                                        <?php echo htmlspecialchars($test['testName']); ?>
                                                    </div>
                                                    <div class="test-meta">
                                                        <i class="fa fa-calendar"></i> <?php echo htmlspecialchars($test['testDate']); ?> &nbsp;|&nbsp; 
                                                        <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($test['duration']); ?> mins
                                                    </div>
                                                    <div>
                                                        <span class="table-badge text-danger"><i class="fa fa-times"></i> Drop: <?php echo htmlspecialchars($test['tableName']); ?></span>
                                                        <span class="table-badge text-danger"><i class="fa fa-times"></i> Drop: <?php echo htmlspecialchars($test['examineesTableName']); ?></span>
                                                        <span class="table-badge text-danger"><i class="fa fa-times"></i> Drop: <?php echo htmlspecialchars($test['answersTable']); ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>
                    </div>

                    <?php if (!empty($deleteTests)): ?>
                        <div class="action-area">
                            <form method="post" onsubmit="return confirm('⚠️ CRITICAL WARNING: You are about to permanently delete all legacy tests and drop all their associated question, answer, and examinee tables.\n\nOnly the 6 protected tests listed on the left will remain.\n\nAre you absolutely sure you wish to proceed?');">
                                <h3 style="margin-top: 0; color: #dc3545; font-weight: 700;"><i class="fa fa-exclamation-triangle"></i> Confirmation Required</h3>
                                <p style="color: #666; margin-bottom: 20px;">Clicking the button below will run a transaction to drop all listed tables and clean up test records.</p>
                                <button type="submit" name="execute_cleanup" class="btn btn-danger btn-cleanup"><i class="fa fa-magic"></i> Execute Safe Cleanup Now</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </section>
        <!--main content end-->
    </section>
    <!-- container section end -->

    <!-- javascripts -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
