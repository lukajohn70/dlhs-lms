<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
require_once 'userExpiredSession.php';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if (!isset($_SESSION['staffLoggedIn'])) {
    if ($isPost) {
        echo 0;
        exit;
    }
    header('location:../index.php');
    exit;
}

$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;

if ($isPost) {
    if (isset($_POST['testId'])) {
        $testId = mysqli_real_escape_string($connection, $_POST['testId']);
        $setStatusTo2 = 2;
        $query = "UPDATE tests SET status='$setStatusTo2' WHERE testId='$testId' AND staffId='$staffId'";
        $result = $connection->query($query);
        if (!$result) die($connection->error);
        echo $result ? 1 : 2;
        exit;
    }

    echo 0;
    exit;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$tests = array();
$result = $connection->query("SELECT testId, testName, testDate, duration, startHour, startMinute, amOrPm, status FROM tests WHERE staffId='$staffId' ORDER BY testDate DESC, testName ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>End / Cancel Test | DLHS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <style>
        .action-panel {
            border-radius: 8px;
            border: 1px solid #dde7f1;
            background: #fff;
            box-shadow: 0 8px 20px rgba(18, 38, 63, 0.05);
        }
        .test-list {
            display: grid;
            gap: 12px;
        }
        .test-card {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 15px;
            border: 1px solid #e2eaf3;
            border-radius: 8px;
            background: #fbfdff;
        }
        .test-card h4 {
            margin: 0 0 6px;
            color: #122033;
            font-weight: 700;
        }
        .test-card p {
            margin: 0;
            color: #65758b;
        }
        .status-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eef5ff;
            color: #1d5fbf;
        }
        .status-live {
            background: #fff4e5;
            color: #a15c00;
        }
        .status-ended {
            background: #eaf8f3;
            color: #0a7f5a;
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
                        <h3 class="page-header"><i class="fa fa-hourglass-end"></i> End / Cancel Test</h3>
                        <ol class="breadcrumb">
                            <li style="margin-left:-12px;"><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-hourglass-end"></i>End / Cancel Test</li>
                            <a href="#" style="color:#0acca2; padding-left:4px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-10">
                        <section class="panel action-panel">
                            <header class="panel-heading">Tests you can manage</header>
                            <div class="panel-body">
                                <div class="message4" style="color:green; font-weight:bold;"></div>
                                <div class="message5" style="color:red; font-weight:bold;"></div>
                                <?php if (empty($tests)) { ?>
                                    <div class="alert alert-info">No tests found for your account.</div>
                                <?php } else { ?>
                                    <div class="test-list">
                                        <?php foreach ($tests as $test) {
                                            $status = (int) $test['status'];
                                            $statusText = $status === 1 ? 'In progress' : ($status === 2 ? 'Ended' : 'Yet to start');
                                            $statusClass = $status === 1 ? ' status-live' : ($status === 2 ? ' status-ended' : '');
                                        ?>
                                            <div class="test-card">
                                                <div>
                                                    <h4><?php echo h($test['testName']); ?></h4>
                                                    <p><?php echo h($test['testDate']); ?> · <?php echo h($test['duration']); ?> minutes · <?php echo h($test['startHour']); ?>:<?php echo h($test['startMinute']); ?> <?php echo h($test['amOrPm']); ?></p>
                                                </div>
                                                <div>
                                                    <span class="status-pill<?php echo $statusClass; ?>"><?php echo h($statusText); ?></span>
                                                    <?php if ($status === 1) { ?>
                                                        <button type="button" class="btn btn-danger" onclick="endSelectedTest('<?php echo h($test['testId']); ?>', '<?php echo h($test['testName']); ?>')"><i class="fa fa-stop-circle"></i> End test</button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </section>
        <div class="text-right"><div class="credits"><?php include "footer.php"; ?></div></div>
    </section>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="js/scripts.js"></script>
    <script>
        function endSelectedTest(testId, testName) {
            if (!confirm('Are you sure you wish to end this test?')) {
                return;
            }

            $.ajax({
                url: 'endTest.php',
                type: 'POST',
                data: { testId: testId },
                success: function(response) {
                    response = $.trim(response);
                    if (response == '0') {
                        window.location.replace('logout.php');
                    } else if (response == '1') {
                        $('.message5').html('');
                        $('.message4').html('<i class="fa fa-check"></i> ' + testName + ' successfully ended.').fadeIn('slow');
                        setTimeout(function(){ window.location.reload(); }, 900);
                    } else {
                        $('.message4').html('');
                        $('.message5').html('<i class="fa fa-times"></i> Could not end test. Please try again.').fadeIn('slow');
                    }
                }
            });
        }
    </script>
  </body>
</html>
