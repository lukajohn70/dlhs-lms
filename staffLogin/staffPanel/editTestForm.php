<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";

$testId = isset($_GET['testId']) ? (int) $_GET['testId'] : 0;
if ($testId <= 0) {
    header('location:addTestForm.php');
    exit;
}

$staffId = isset($_SESSION['staffId']) ? $_SESSION['staffId'] : null;
if ($staffId && !isset($_SESSION['adminLoggedIn'])) {
    $stmt = $connection->prepare("SELECT * FROM tests WHERE testId = ? AND staffId = ?");
    $stmt->bind_param("is", $testId, $staffId);
} else {
    $stmt = $connection->prepare("SELECT * FROM tests WHERE testId = ?");
    $stmt->bind_param("i", $testId);
}
$stmt->execute();
$res = $stmt->get_result();
$test = $res->fetch_assoc();
$stmt->close();

if (!$test) {
    header('location:addTestForm.php?error=testnotfound');
    exit;
}

$configuredTestTypes = dlhsGetConfiguredTestTypes($connection);
$academicSessions = dlhsGetAcademicSessions($connection, false);
$currentAcademicSession = dlhsGetCurrentAcademicSessionName($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>Edit Test | DLHS CBT</title>

    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
    <link href="fontAwesome/css/brands.css" rel="stylesheet">
    <link href="fontAwesome/css/solid.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet">

    <!-- Custom DLHS Styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f7fa;
            color: #333333;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .edit-page-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid rgba(0, 51, 102, 0.08);
            box-shadow: 0 4px 16px rgba(0, 51, 102, 0.05);
            padding: 24px;
            margin-bottom: 22px;
            transition: box-shadow 0.2s ease;
        }

        .edit-page-card:hover {
            box-shadow: 0 8px 24px rgba(0, 51, 102, 0.08);
        }

        .card-header-custom {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #edf2f7;
        }

        .card-header-custom h4 {
            margin: 0;
            font-weight: 700;
            font-size: 16px;
            color: #003366;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .card-header-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(0, 51, 102, 0.08);
            color: #003366;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 13.5px;
            color: #2d3748;
            margin-bottom: 6px;
        }

        .form-group .form-control {
            height: 42px;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            box-shadow: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group .form-control:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        .preview-name-box {
            background: linear-gradient(135deg, #003366 0%, #0a5a8c 100%);
            color: #ffffff;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 22px;
            box-shadow: 0 6px 20px rgba(0, 51, 102, 0.15);
        }

        .preview-name-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #a5d8ff;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .preview-name-value {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.02em;
            word-break: break-word;
        }

        .conditional-box {
            padding: 16px;
            border-radius: 10px;
            background: #fffdf5;
            border: 1.5px solid #ffeeba;
            margin-top: 10px;
        }

        .conditional-box small {
            display: block;
            margin-top: 6px;
            color: #856404;
            font-size: 12px;
        }

        .btn-action-primary {
            background: #28a745;
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 12px 36px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(40, 167, 69, 0.25);
            transition: all 0.2s ease;
        }

        .btn-action-primary:hover {
            background: #218838;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(40, 167, 69, 0.35);
        }

        .btn-action-default {
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 50px;
            padding: 12px 28px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .btn-action-default:hover {
            background: #cbd5e1;
            color: #1e293b;
        }

        /* Confirmation Modal Overlay */
        .confirm-modal-overlay {
            display: none;
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .confirm-modal-box {
            background: #ffffff;
            border-radius: 16px;
            width: 90%;
            max-width: 480px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
            text-align: center;
            animation: modalPop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalPop {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <section id="container" class="">
        <?php include 'header.php'; ?>
        <?php include 'sideBar.php'; ?>

        <section id="main-content">
            <section class="wrapper">
                <!-- Page Breadcrumbs & Header -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-lg-12">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div>
                                <h3 class="page-header" style="margin:0; border-bottom:none; font-weight:700; color:#003366;">
                                    <i class="fa fa-pencil-square-o" style="color:#009999;"></i> Edit Test Details
                                </h3>
                                <ol class="breadcrumb" style="margin:5px 0 0 0; padding:0; background:none;">
                                    <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                                    <li><a href="addTestForm.php"><i class="fa fa-file-text-o"></i> Tests</a></li>
                                    <li class="active"><?php echo htmlspecialchars($test['testName']); ?></li>
                                </ol>
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="addQuestionForm.php?testId=<?php echo $testId; ?>" class="btn btn-default" style="border-radius:50px; font-weight:600;">
                                    <i class="fa fa-plus-circle" style="color:#009999;"></i> Add Questions
                                </a>
                                <a href="viewQuestionForm.php?testId=<?php echo $testId; ?>" class="btn btn-default" style="border-radius:50px; font-weight:600;">
                                    <i class="fa fa-eye" style="color:#0073b7;"></i> Preview Questions
                                </a>
                                <a href="addTestForm.php" class="btn btn-default" style="border-radius:50px; font-weight:600;">
                                    <i class="fa fa-arrow-left"></i> Return to All Tests
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Generated Name Highlight Banner -->
                <div class="preview-name-box">
                    <div class="preview-name-label">
                        <i class="fa fa-tag"></i> Generated Test Name Preview
                    </div>
                    <div class="preview-name-value" id="previewTestNameDisplay">
                        <?php echo htmlspecialchars($test['testName']); ?>
                    </div>
                </div>

                <!-- Main Edit Form -->
                <form id="editTestPageForm">
                    <input type="hidden" name="testId" id="testId" value="<?php echo $testId; ?>">
                    <input type="hidden" name="testName1" id="testName1" value="<?php echo htmlspecialchars($test['testName']); ?>">
                    <input type="hidden" name="startHour1" id="startHour1" value="<?php echo htmlspecialchars($test['startHour'] !== '' ? $test['startHour'] : '8'); ?>">
                    <input type="hidden" name="startMinute1" id="startMinute1" value="<?php echo htmlspecialchars($test['startMinute'] !== '' ? $test['startMinute'] : '0'); ?>">
                    <input type="hidden" name="amOrPm1" id="amOrPm1" value="<?php echo htmlspecialchars($test['amOrPm'] !== '' ? $test['amOrPm'] : 'AM'); ?>">
                    <input type="hidden" name="reviewOption1" id="reviewOption1" value="<?php echo htmlspecialchars($test['reviewOption'] !== '' ? $test['reviewOption'] : 'No'); ?>">

                    <!-- Section 1: Test Identity & Schedule -->
                    <div class="edit-page-card">
                        <div class="card-header-custom">
                            <div class="card-header-icon"><i class="fa fa-calendar"></i></div>
                            <h4>1. Test Identity & Schedule</h4>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="testDate1">Test Date <span class="text-danger">*</span></label>
                                    <input type="date" name="testDate1" id="testDate1" class="form-control" value="<?php echo htmlspecialchars($test['testDate']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="testDuration1">Duration (Minutes) <span class="text-danger">*</span></label>
                                    <input type="number" name="testDuration1" id="testDuration1" class="form-control" value="<?php echo htmlspecialchars($test['duration']); ?>" min="1" max="600" placeholder="e.g. 50" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="academicSession1">Academic Session <span class="text-danger">*</span></label>
                                    <select class="form-control" name="academicSession1" id="academicSession1" required>
                                        <option value="">... Select Session ...</option>
                                        <?php foreach ($academicSessions as $sessionRow): 
                                            $sName = isset($sessionRow['sessionName']) ? $sessionRow['sessionName'] : '';
                                            $isSel = ($test['academicSession'] === $sName) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo htmlspecialchars($sName); ?>" <?php echo $isSel; ?>>
                                                <?php echo htmlspecialchars($sName); ?><?php echo !empty($sessionRow['isCurrentSession']) ? ' (Current)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Academic Setup -->
                    <div class="edit-page-card">
                        <div class="card-header-custom">
                            <div class="card-header-icon"><i class="fa fa-graduation-cap"></i></div>
                            <h4>2. Academic Setup & Class Assignment</h4>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subject_composite1">Subject & Year Group <span class="text-danger">*</span></label>
                                    <select class="form-control" name="subject_composite1" id="subject_composite1" required onchange="handleSubjectChange()">
                                        <option value="">... Select Subject ...</option>
                                        <?php
                                        $currentComposite = $test['subject'] . '|' . $test['yearGroup'];
                                        if (isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
                                            $staffId = $_SESSION['staffId'];
                                            $subjectQuery = "SELECT DISTINCT s.subjectId, s.subjectName, yg.yearGroupId, yg.yearGroupName 
                                                             FROM subjects s 
                                                             INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
                                                             INNER JOIN classes c ON sta.classId = c.classId 
                                                             INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId 
                                                             WHERE sta.teacherId = '$staffId' 
                                                             ORDER BY s.subjectName ASC, yg.yearGroupName ASC";
                                        } else {
                                            $subjectQuery = "SELECT s.*, yg.yearGroupName, yg.yearGroupId 
                                                             FROM subjects s 
                                                             CROSS JOIN yeargroup yg 
                                                             ORDER BY s.subjectName ASC";
                                        }
                                        $res = $connection->query($subjectQuery);
                                        while ($row = $res->fetch_assoc()) {
                                            $val = $row['subjectId'] . '|' . $row['yearGroupId'];
                                            $label = $row['subjectName'] . ' ' . $row['yearGroupName'];
                                            $isSel = ($currentComposite === $val) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($val) . '" ' . $isSel . '>' . htmlspecialchars($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" name="subjectId1" id="subjectId1" value="<?php echo htmlspecialchars($test['subject']); ?>">
                                    <input type="hidden" name="yearGroup1" id="yearGroup1" value="<?php echo htmlspecialchars($test['yearGroup']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="classId1">Assigned Class / Arm <span class="text-danger">*</span></label>
                                    <select class="form-control" name="classId1" id="classId1" required onchange="updateGeneratedTestName()">
                                        <option value="">... Loading Classes ...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Test Type & Exam Delivery -->
                    <div class="edit-page-card">
                        <div class="card-header-custom">
                            <div class="card-header-icon"><i class="fa fa-cogs"></i></div>
                            <h4>3. Test Type & Delivery Settings</h4>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="testType1">Type of Test <span class="text-danger">*</span></label>
                                    <select class="form-control" name="testType1" id="testType1" required onchange="handleTestTypeChange()">
                                        <option value="">... Select Test Type ...</option>
                                        <?php foreach ($configuredTestTypes as $cType): 
                                            $isSel = ($test['testType'] === $cType) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo htmlspecialchars($cType); ?>" <?php echo $isSel; ?>>
                                                <?php echo htmlspecialchars($cType); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4" id="customTestTypeGroup" style="display:none;">
                                <div class="form-group">
                                    <label for="customTestType1">Custom Kind of Test <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="customTestType1" id="customTestType1" value="<?php echo htmlspecialchars($test['customTestType']); ?>" placeholder="e.g. MIDTERM, PRACTICAL, QUIZ">
                                    <small class="text-muted">Replaces OTHER in generated name.</small>
                                </div>
                            </div>
                            <div class="col-md-4" id="mockPaperGroup" style="display:none;">
                                <div class="form-group">
                                    <label for="mockPaperLabel1">Mock Paper <span class="text-danger">*</span></label>
                                    <select class="form-control" name="mockPaperLabel1" id="mockPaperLabel1" onchange="updateGeneratedTestName()">
                                        <option value="">... Select Mock Paper ...</option>
                                        <?php foreach (['PAPER 1', 'PAPER 2', 'PAPER 3'] as $paper): 
                                            $isSel = ($test['mockPaperLabel'] === $paper) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $paper; ?>" <?php echo $isSel; ?>><?php echo $paper; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Select paper for this mock exam.</small>
                                </div>
                            </div>
                        </div>

                        <hr style="margin:16px 0; border-color:#edf2f7;">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="essayOption1">Does Test Have Essay? <span class="text-danger">*</span></label>
                                    <select class="form-control" name="essayOption1" id="essayOption1" required onchange="handleEssayOptionChange()">
                                        <option value="No" <?php echo ($test['essayOption'] === 'No') ? 'selected' : ''; ?>>No</option>
                                        <option value="Yes" <?php echo ($test['essayOption'] === 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4" id="essayTimeContainer" style="<?php echo ($test['essayOption'] === 'Yes') ? '' : 'display:none;'; ?>">
                                <div class="form-group">
                                    <label for="essayTime1">Essay Duration (Minutes) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="essayTime1" id="essayTime1" value="<?php echo htmlspecialchars($test['essayTime']); ?>" min="1" max="300" placeholder="e.g. 30">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="randomizeQuestions1">Randomize Questions? <span class="text-danger">*</span></label>
                                    <select class="form-control" name="randomizeQuestions1" id="randomizeQuestions1" required>
                                        <option value="Yes" <?php echo ($test['randomizeQuestions'] === 'Yes') ? 'selected' : ''; ?>>Yes - Shuffle Questions</option>
                                        <option value="No" <?php echo ($test['randomizeQuestions'] === 'No') ? 'selected' : ''; ?>>No - Keep Fixed Order</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="randomizeOptions1">Randomize Options? <span class="text-danger">*</span></label>
                                    <select class="form-control" name="randomizeOptions1" id="randomizeOptions1" required>
                                        <option value="Yes" <?php echo ($test['randomizeOptions'] === 'Yes') ? 'selected' : ''; ?>>Yes - Shuffle Options (A-D)</option>
                                        <option value="No" <?php echo ($test['randomizeOptions'] === 'No') ? 'selected' : ''; ?>>No - Keep Fixed Options</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="reviewOptionSelect">Student Review on Completion? <span class="text-danger">*</span></label>
                                    <select class="form-control" id="reviewOptionSelect" onchange="$('#reviewOption1').val(this.value)">
                                        <option value="No" <?php echo ($test['reviewOption'] === 'No') ? 'selected' : ''; ?>>No - Disallow Review</option>
                                        <option value="Yes" <?php echo ($test['reviewOption'] === 'Yes') ? 'selected' : ''; ?>>Yes - Allow Review</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & Feedback Card -->
                    <div class="edit-page-card" style="text-align:right;">
                        <div id="formAlertMessage" style="text-align:left; margin-bottom:16px;"></div>
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <a href="addTestForm.php" class="btn btn-action-default">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                            <button type="button" class="btn btn-action-primary" onclick="openSaveConfirmation()">
                                <i class="fa fa-check-circle"></i> Update Test Details
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </section>
    </section>

    <!-- Confirmation Modal: Simple and Focused -->
    <div id="saveConfirmModal" class="confirm-modal-overlay">
        <div class="confirm-modal-box">
            <div style="width:60px; height:60px; border-radius:50%; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:28px;">
                <i class="fa fa-question-circle"></i>
            </div>
            <h4 style="margin:0 0 10px; font-weight:700; color:#0f172a;">Confirm Test Update</h4>
            <p id="confirmModalText" style="color:#64748b; font-size:14px; line-height:1.6; margin-bottom:24px;">
                Are you sure you want to update the details of this test?
            </p>
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn btn-default" onclick="closeSaveConfirmation()" style="border-radius:50px; padding:9px 24px; font-weight:600;">
                    Cancel
                </button>
                <button type="button" id="btnConfirmExecuteSave" class="btn btn-success" onclick="executeSaveTestDetails()" style="border-radius:50px; padding:9px 28px; font-weight:700;">
                    Yes, Update Test
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../libs/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        var initialClassId = "<?php echo htmlspecialchars($test['classId']); ?>";

        function openSaveConfirmation() {
            // Pre-validation
            var testDate = $('#testDate1').val();
            var duration = $('#testDuration1').val();
            var academicSession = $('#academicSession1').val();
            var subjectComposite = $('#subject_composite1').val();
            var testType = $('#testType1').val();

            if (!testDate) {
                showAlert("Please choose a test date.", "danger");
                $('#testDate1').focus();
                return;
            }
            if (!duration || parseInt(duration, 10) <= 0) {
                showAlert("Please enter a valid duration in minutes.", "danger");
                $('#testDuration1').focus();
                return;
            }
            if (!academicSession) {
                showAlert("Please choose an academic session.", "danger");
                $('#academicSession1').focus();
                return;
            }
            if (!subjectComposite) {
                showAlert("Please choose a subject.", "danger");
                $('#subject_composite1').focus();
                return;
            }
            if (!testType) {
                showAlert("Please choose a test type.", "danger");
                $('#testType1').focus();
                return;
            }

            if (testType === 'OTHER' && !$.trim($('#customTestType1').val())) {
                showAlert("Please enter the custom kind of test for OTHER.", "danger");
                $('#customTestType1').focus();
                return;
            }

            if (testType === 'MOCK' && !$('#mockPaperLabel1').val()) {
                showAlert("Please select a mock paper.", "danger");
                $('#mockPaperLabel1').focus();
                return;
            }

            if ($('#essayOption1').val() === 'Yes' && (!$.trim($('#essayTime1').val()) || parseInt($('#essayTime1').val(), 10) <= 0)) {
                showAlert("Please enter the essay duration in minutes.", "danger");
                $('#essayTime1').focus();
                return;
            }

            $('#saveConfirmModal').css('display', 'flex');
        }

        function closeSaveConfirmation() {
            $('#saveConfirmModal').hide();
        }

        function showAlert(message, type) {
            var icon = type === 'danger' ? 'fa-times-circle' : 'fa-check-circle';
            $('#formAlertMessage').html(
                '<div class="alert alert-' + type + '" style="border-radius:8px; font-weight:600;">' +
                '<i class="fa ' + icon + '"></i> ' + message +
                '</div>'
            );
            $('html, body').animate({ scrollTop: $('#formAlertMessage').offset().top - 120 }, 300);
        }

        function handleSubjectChange() {
            var composite = $('#subject_composite1').val();
            if (!composite) {
                $('#subjectId1').val('');
                $('#yearGroup1').val('');
                $('#classId1').html('<option value="">... Select Class ...</option>');
                updateGeneratedTestName();
                return;
            }

            var parts = composite.split('|');
            var subjectId = parts[0];
            var yearGroupId = parts[1];
            $('#subjectId1').val(subjectId);
            $('#yearGroup1').val(yearGroupId);

            $('#classId1').html('<option value="">Loading assigned classes...</option>');

            $.ajax({
                url: 'getTeacherAssignedClassesAndSubjects.php',
                type: 'GET',
                data: { type: 'classesBySubject', subjectId: subjectId, yearGroupId: yearGroupId },
                dataType: 'json',
                success: function(res) {
                    var options = '<option value="">... Select Class ...</option>';
                    if (res.success && res.data && res.data.length > 0) {
                        if (res.data.length > 1) {
                            options += '<option value="all">ALL ASSIGNED ARMS</option>';
                        }
                        res.data.forEach(function(item) {
                            var sel = (initialClassId && String(initialClassId) === String(item.classId)) ? ' selected' : '';
                            options += '<option value="' + item.classId + '"' + sel + '>' + item.className + '</option>';
                        });
                    }
                    $('#classId1').html(options);
                    if (initialClassId === 'all' || initialClassId === '0') {
                        $('#classId1').val('all');
                    }
                    updateGeneratedTestName();
                },
                error: function() {
                    $('#classId1').html('<option value="">Failed to load classes</option>');
                }
            });
        }

        function handleTestTypeChange() {
            var type = $('#testType1').val();
            if (type === 'OTHER') {
                $('#customTestTypeGroup').show();
            } else {
                $('#customTestTypeGroup').hide();
                $('#customTestType1').val('');
            }

            if (type === 'MOCK') {
                $('#mockPaperGroup').show();
            } else {
                $('#mockPaperGroup').hide();
                $('#mockPaperLabel1').val('');
            }
            updateGeneratedTestName();
        }

        function handleEssayOptionChange() {
            if ($('#essayOption1').val() === 'Yes') {
                $('#essayTimeContainer').show();
            } else {
                $('#essayTimeContainer').hide();
                $('#essayTime1').val('');
            }
        }

        function getSelectedTestTypeLabel() {
            var selectedType = $('#testType1').val() || '';
            var customType = $.trim($('#customTestType1').val() || '');

            if (selectedType === 'OTHER' && customType !== '') {
                return customType;
            }
            if (selectedType === 'MOCK') {
                var mockPaper = $.trim($('#mockPaperLabel1').val() || '');
                return $.trim([selectedType, mockPaper].join(' '));
            }
            return selectedType;
        }

        function updateGeneratedTestName() {
            var sessionText = $('#academicSession1').val() ? $('#academicSession1 option:selected').val() : '';
            var subjectText = $('#subject_composite1').val() ? $('#subject_composite1 option:selected').text() : '';
            var armVal = $('#classId1').val();
            var armText = (armVal && armVal !== 'all') ? $('#classId1 option:selected').text() : (armVal === 'all' ? 'ALL' : '');
            var testTypeText = getSelectedTestTypeLabel();

            var generatedName = $.trim([sessionText, subjectText, armText, testTypeText].filter(Boolean).join(' '));
            $('#testName1').val(generatedName);
            $('#previewTestNameDisplay').text(generatedName || 'Generating test name...');
        }

        function executeSaveTestDetails() {
            var btn = $('#btnConfirmExecuteSave');
            var origHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Updating...');

            var form_data = {
                testId: $('#testId').val(),
                testName: $('#testName1').val(),
                testDate: $('#testDate1').val(),
                testDuration: $('#testDuration1').val(),
                startHour: $('#startHour1').val(),
                startMinute: $('#startMinute1').val(),
                amOrPm: $('#amOrPm1').val(),
                subjectId: $('#subjectId1').val(),
                yearGroupId: $('#yearGroup1').val(),
                classId: $('#classId1').val(),
                academicSession: $('#academicSession1').val(),
                testType: $('#testType1').val(),
                customTestType: $.trim($('#customTestType1').val()),
                mockPaperLabel: $.trim($('#mockPaperLabel1').val()),
                reviewOption: $('#reviewOption1').val(),
                essayOption: $('#essayOption1').val(),
                theEssayTime: ($('#essayOption1').val() === 'Yes') ? $('#essayTime1').val() : '',
                randomizeQuestions: $('#randomizeQuestions1').val(),
                randomizeOptions: $('#randomizeOptions1').val()
            };

            $.ajax({
                url: 'editTest.php',
                type: 'POST',
                data: form_data,
                success: function(resp) {
                    closeSaveConfirmation();
                    var code = parseInt(resp, 10);
                    if (code === 0) {
                        window.location.replace("logout.php");
                    } else if (code === 1) {
                        showAlert('Test details updated successfully!', 'success');
                        setTimeout(function() {
                            if (confirm("Test successfully updated! Would you like to return to the Tests list?")) {
                                window.location.href = 'addTestForm.php';
                            }
                        }, 500);
                    } else if (code === 3) {
                        showAlert('Please select a valid test type.', 'danger');
                    } else if (code === 4) {
                        showAlert('Please select a valid academic session.', 'danger');
                    } else if (code === 5) {
                        showAlert('Unable to generate the test name from the selected fields.', 'danger');
                    } else if (code === 6) {
                        showAlert('Please enter the custom kind of test for OTHER.', 'danger');
                    } else if (code === 7) {
                        showAlert('Please select the mock paper.', 'danger');
                    } else {
                        showAlert('Could not update test. Server error (' + resp + ').', 'danger');
                    }
                },
                error: function() {
                    closeSaveConfirmation();
                    showAlert('Failed to connect to the server. Please check your network connection.', 'danger');
                },
                complete: function() {
                    btn.prop('disabled', false).html(origHtml);
                }
            });
        }

        // Initialize on load
        $(document).ready(function() {
            handleSubjectChange();
            handleTestTypeChange();

            $('#academicSession1').on('change', updateGeneratedTestName);
            $('#customTestType1, #mockPaperLabel1').on('input change', updateGeneratedTestName);
        });
    </script>
</body>
</html>
