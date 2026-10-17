<?php
session_start();
require_once 'userExpiredSession.php';
require_once 'sessionTime.php';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if (!isset($_SESSION['staffLoggedIn']) || (time() - $_SESSION['staffLast_login']) > $allottedTime) {
    if ($isPost) {
        require_once 'unsetSessions.php';
        echo 0;
        exit;
    }
    header('location:../index.php');
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";
$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;

if ($isPost) {
    if (isset($_POST['testId'])) {
        $testId = mysqli_real_escape_string($connection, $_POST['testId']);
        $query = "SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
        $result = $connection->query($query);
        if (!$result) die($connection->error);
        $row = $result->fetch_array(MYSQLI_NUM);
        if (!$row) {
            echo 0;
            exit;
        }

        $testStatus = $row[11];
        if ($testStatus == 0) {
            echo 1;
        } elseif ($testStatus == 1) {
            echo 2;
        } elseif ($testStatus == 2) {
            echo 3;
        }
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
    <title>Timing & Reschedule | DLHS</title>
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
        .timing-form {
            display: grid;
            gap: 16px;
            max-width: 720px;
        }
        .test-summary {
            padding: 14px 16px;
            border: 1px solid #e2eaf3;
            border-radius: 8px;
            background: #fbfdff;
            color: #34495e;
        }
        .radio-row {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }
    </style>
    <script>
        function isNumber(evt) {
            var iKeyCode = (evt.which) ? evt.which : evt.keyCode;
            return !(iKeyCode < 48 || iKeyCode > 57);
        }
    </script>
  </head>
  <body>
    <section id="container" class="">
        <?php include 'header.php'; ?>
        <?php include 'sideBar.php'; ?>
        <section id="main-content">
            <section class="wrapper">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header"><i class="fa fa-clock-o"></i> Timing & Reschedule</h3>
                        <ol class="breadcrumb">
                            <li style="margin-left:-12px;"><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-clock-o"></i>Timing & Reschedule</li>
                            <a href="#" style="color:#0acca2; padding-left:4px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-10">
                        <section class="panel action-panel">
                            <header class="panel-heading">Modify time before a test starts</header>
                            <div class="panel-body">
                                <div class="message4" style="color:green; font-weight:bold;"></div>
                                <div class="message5" style="color:red; font-weight:bold;"></div>
                                <form class="timing-form" id="timingForm">
                                    <div class="form-group">
                                        <label>Select test</label>
                                        <select class="form-control" id="testId2" name="testId2" required>
                                            <option value="">Select a test</option>
                                            <?php foreach ($tests as $test) { ?>
                                                <option
                                                    value="<?php echo h($test['testId']); ?>"
                                                    data-name="<?php echo h($test['testName']); ?>"
                                                    data-date="<?php echo h($test['testDate']); ?>"
                                                    data-duration="<?php echo h($test['duration']); ?>"
                                                    data-time="<?php echo h($test['startHour']); ?>:<?php echo h($test['startMinute']); ?> <?php echo h($test['amOrPm']); ?>"
                                                    data-status="<?php echo h($test['status']); ?>">
                                                    <?php echo h($test['testName']); ?> - <?php echo h($test['testDate']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div id="testDetails" class="test-summary">Select a test to view timing details.</div>
                                    <div class="form-group">
                                        <label>Time to add/subtract in minutes</label>
                                        <input type="text" name="testDuration2" required onkeypress="return isNumber(event)" id="testDuration2" class="form-control" placeholder="Example: 10">
                                    </div>
                                    <div class="radio-row">
                                        <label><input type="radio" name="addOrSubtract" value="add"> Add time to selected test</label>
                                        <label><input type="radio" name="addOrSubtract" value="subtract"> Subtract time from selected test</label>
                                    </div>
                                    <div>
                                        <button type="reset" class="btn btn-default">Reset</button>
                                        <button type="button" class="btn btn-success" onclick="addOrSubtractTestTime()"><i class="fa fa-check"></i> Change time</button>
                                    </div>
                                    <div class="message6" id="message6" style="color:red; font-weight:bold;"></div>
                                </form>
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
        $('#testId2').on('change', function() {
            var selected = $(this).find(':selected');
            if (!selected.val()) {
                $('#testDetails').html('Select a test to view timing details.');
                return;
            }

            var status = selected.data('status');
            var statusText = status == 0 ? 'Yet to start' : (status == 1 ? 'In progress' : 'Ended');
            $('#testDetails').html(
                '<strong>' + selected.data('name') + '</strong><br>' +
                'Date: ' + selected.data('date') + '<br>' +
                'Duration: ' + selected.data('duration') + ' minutes<br>' +
                'Start time: ' + selected.data('time') + '<br>' +
                'Status: ' + statusText
            );
        });

        function addOrSubtractTestTime() {
            $('.message4, .message5, .message6').html('');

            var testId = $('#testId2').val();
            var minutes = $('#testDuration2').val();
            var addOrSubtract = $('input[name="addOrSubtract"]:checked').val();

            if (!testId) {
                $('.message6').html('<i class="fa fa-info-circle"></i> Select a test.');
                return;
            }
            if (!minutes) {
                $('.message6').html('<i class="fa fa-info-circle"></i> Enter the number of minutes.');
                return;
            }
            if (!addOrSubtract) {
                $('.message6').html('<i class="fa fa-info-circle"></i> Choose add or subtract.');
                return;
            }

            $.ajax({
                url: 'modifyTimeBeforeTestStarts.php',
                type: 'POST',
                data: { testId: testId },
                success: function(response) {
                    response = $.trim(response);
                    if (response == '0') {
                        window.location.replace('logout.php');
                    } else if (response == '1') {
                        submitTimeChange(testId, minutes, addOrSubtract);
                    } else if (response == '2') {
                        $('.message6').html('<i class="fa fa-times"></i> This test has already started. Use selected-student timing controls instead.');
                    } else if (response == '3') {
                        $('.message6').html('<i class="fa fa-times"></i> This test has ended and cannot be changed here.');
                    }
                }
            });
        }

        function submitTimeChange(testId, minutes, addOrSubtract) {
            $.ajax({
                url: 'editOverAllTestTime.php',
                type: 'POST',
                data: {
                    testId: testId,
                    timeValue: minutes,
                    timeAction: addOrSubtract
                },
                success: function(response) {
                    response = $.trim(response);
                    if (response == '0') {
                        window.location.replace('logout.php');
                    } else if (response == '1') {
                        $('.message6').css('color', 'green').html('<i class="fa fa-check"></i> Test time updated successfully.');
                        setTimeout(function(){ window.location.reload(); }, 900);
                    } else {
                        $('.message6').css('color', 'red').html('<i class="fa fa-times"></i> Could not update the test time. Please try again.');
                    }
                }
            });
        }
    </script>
  </body>
</html>
