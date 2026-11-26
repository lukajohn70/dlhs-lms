<?php
session_start();
require_once 'userExpiredSession.php';

if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";

$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
$preselectedTestId = isset($_GET['testId']) ? (int) $_GET['testId'] : 0;
$questionTestOptions = array();

if ($staffId > 0) {
    $testsResult = $connection->query("SELECT * FROM tests WHERE staffId='{$staffId}' ORDER BY testName ASC");
    if ($testsResult) {
        while ($testRow = $testsResult->fetch_assoc()) {
            $setupSummary = dlhsBuildTestSetupSummary($connection, $testRow);
            $testType = dlhsNormalizeTestType(isset($testRow['testType']) ? $testRow['testType'] : '');
            $optionLabel = $testRow['testName'];
            if ($testType !== '') {
                $optionLabel .= " ({$testType})";
            }
            if (!$setupSummary['studentsAdded']) {
                $optionLabel .= " - add students first";
            }

            $questionTestOptions[] = array(
                'testId' => (int) $testRow['testId'],
                'label' => $optionLabel,
                'studentsAdded' => $setupSummary['studentsAdded'],
                'setupStatus' => $setupSummary['setupStatus'],
                'setupMessage' => $setupSummary['setupMessage'],
                'tableName' => isset($testRow['tableName']) ? $testRow['tableName'] : ''
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>Bulk Paste Questions | DLHS</title>

    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <script src="../../libs/jquery.min.js"></script>

    <style>
        .bulk-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            padding: 24px;
            margin-bottom: 24px;
        }
        .bulk-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin-bottom: 12px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        #bulkPasteEditor {
            min-height: 380px;
            max-height: 650px;
            overflow-y: auto;
            border-radius: 10px;
            border: 2px solid #cbd5e1;
            padding: 18px;
            font-size: 15px;
            line-height: 1.7;
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        #bulkPasteEditor:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.12);
        }
        #bulkPasteEditor:empty:before {
            content: attr(data-placeholder);
            color: #94a3b8;
            font-style: italic;
            pointer-events: none;
        }
        .parsed-q-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .parsed-q-card:hover {
            border-color: #e91e8c;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.07);
        }
        .parsed-editable-field {
            outline: none;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px dashed transparent;
            transition: all 0.2s;
        }
        .parsed-editable-field:hover, .parsed-editable-field:focus {
            background-color: #fff9db !important;
            border-color: #f59e0b;
        }
        .option-box {
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #edf2f7;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .option-box strong {
            color: #003366;
            min-width: 20px;
        }
        /* Confirmation Modal */
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
            border-radius: 14px;
            width: 90%;
            max-width: 480px;
            padding: 26px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <section id="container" class="">
        <?php include 'header.php'; ?>
        <?php include 'sideBarAddQuestions.php'; ?>

        <section id="main-content">
            <section class="wrapper">
                <!-- Page Breadcrumbs -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-lg-12">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div>
                                <h3 class="page-header" style="margin:0; border-bottom:none; font-weight:700; color:#003366;">
                                    <i class="fa fa-paste" style="color:#e91e8c;"></i> Bulk Paste Questions
                                </h3>
                                <ol class="breadcrumb" style="margin:5px 0 0 0; padding:0; background:none;">
                                    <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                                    <li><a href="addQuestionForm.php">Questions</a></li>
                                    <li class="active">Bulk Paste Tool</li>
                                </ol>
                            </div>
                            <div>
                                <a href="addQuestionForm.php<?php echo $preselectedTestId ? '?testId=' . $preselectedTestId : ''; ?>" class="btn btn-default" style="border-radius: 50px; font-weight:600;">
                                    <i class="fa fa-arrow-left"></i> Return to Questions Form
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Selection Card -->
                <div class="bulk-card">
                    <div class="row" style="align-items: center;">
                        <div class="col-md-3">
                            <label for="bulkTestSelect" style="font-weight:700; color:#003366; font-size:14px; margin:0;">
                                <i class="fa fa-check-square-o"></i> Target Test:
                            </label>
                        </div>
                        <div class="col-md-9">
                            <select id="bulkTestSelect" class="form-control" style="font-weight:600; height:42px; border-radius:8px; border:2px solid #cbd5e1;">
                                <option value="">-- Choose a Test to add questions --</option>
                                <?php foreach ($questionTestOptions as $opt): ?>
                                    <option value="<?php echo $opt['testId']; ?>" <?php echo ($preselectedTestId === $opt['testId']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Input Workspace Area -->
                <div id="bulkPasteInputArea" class="bulk-card">
                    <div class="alert alert-info" style="border-radius:8px; font-size:13px; line-height:1.6; margin-bottom:18px;">
                        <strong><i class="fa fa-info-circle"></i> Instructions:</strong> Paste questions and options below. Rich formatting (bold, italics, underlines, formulas, subscripts <sub>2</sub>, superscripts <sup>2</sup>, math symbols, tables) will be fully preserved!
                        <br><strong>Format Example:</strong> <code>1. Which country is located in West Africa? [A] Nigeria [B] Ghana [C] UK [D] USA</code>
                    </div>

                    <!-- Formatting Toolbar -->
                    <div class="bulk-toolbar">
                        <span style="font-size:12px; font-weight:700; color:#475569; margin-right:8px;"><i class="fa fa-pencil"></i> Quick Tools:</span>
                        <button type="button" class="btn btn-default btn-xs" onclick="formatBulkEditor('bold')" title="Bold (Ctrl+B)"><b>B</b></button>
                        <button type="button" class="btn btn-default btn-xs" onclick="formatBulkEditor('italic')" title="Italic (Ctrl+I)"><i>I</i></button>
                        <button type="button" class="btn btn-default btn-xs" onclick="formatBulkEditor('underline')" title="Underline (Ctrl+U)"><u>U</u></button>
                        <button type="button" class="btn btn-default btn-xs" onclick="formatBulkEditor('superscript')" title="Superscript (x²)">x²</button>
                        <button type="button" class="btn btn-default btn-xs" onclick="formatBulkEditor('subscript')" title="Subscript (x₂)">x₂</button>
                        <button type="button" class="btn btn-default btn-xs" onclick="formatBulkEditor('removeFormat')" title="Clear formatting"><i class="fa fa-eraser"></i> Clear Styles</button>
                        <button type="button" class="btn btn-default btn-xs" onclick="$('#csvImportFileInput').click()" style="color:#28a745; font-weight:600;" title="Import CSV File"><i class="fa fa-file-excel-o"></i> Import CSV</button>
                        <input type="file" id="csvImportFileInput" accept=".csv" style="display:none;" onchange="handleCsvFileSelected(event)">
                        <button type="button" class="btn btn-default btn-xs" onclick="clearBulkPasteEditor()" style="color:#d9534f; margin-left:auto;" title="Clear paste editor"><i class="fa fa-trash"></i> Clear All</button>
                    </div>

                    <!-- Full-Width Contenteditable Editor with spellcheck=false -->
                    <div id="bulkPasteEditor" contenteditable="true" spellcheck="false" class="form-control" data-placeholder="Paste your formatted questions here (Ctrl+V / Cmd+V)..."></div>

                    <!-- Action Buttons -->
                    <div style="margin-top:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <button type="button" class="btn" onclick="sendBulkPasteToEssay()" style="background:#0275d8; color:#fff; padding:12px 28px; border-radius:50px; font-weight:600; border:none; box-shadow:0 4px 12px rgba(2,117,216,0.2);">
                            <i class="fa fa-file-text-o"></i> Insert into Essay Questions
                        </button>
                        <button type="button" id="btnParseBulkQuestions" class="btn" onclick="processBulkPaste()" style="background:#003366; color:#fff; padding:12px 35px; border-radius:50px; font-weight:700; border:none; box-shadow:0 4px 14px rgba(0,51,102,0.25);">
                            <i class="fa fa-magic"></i> Parse & Preview Objective Questions
                        </button>
                    </div>
                </div>

                <!-- Full-Page Interactive Preview Area -->
                <div id="bulkPastePreviewArea" class="bulk-card" style="display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; border-bottom:1px solid #e2e8f0; padding-bottom:15px;">
                        <div>
                            <h4 style="margin:0; font-weight:700; color:#003366;">
                                Parsed Questions (<span id="parsedCount" style="color:#e91e8c;">0</span>)
                            </h4>
                            <span style="font-size:13px; color:#64748b;">Review, change correct options, or edit questions and options directly in place</span>
                        </div>
                        <div id="bulkPreviewPaginationTop"></div>
                    </div>

                    <!-- Question Cards Container -->
                    <div id="parsedQuestionsList"></div>

                    <!-- Bottom Pagination -->
                    <div id="bulkPreviewPaginationBottom" style="display:flex; justify-content:center; margin:20px 0;"></div>

                    <!-- Bottom Actions -->
                    <div style="margin-top:20px; padding-top:20px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <button type="button" class="btn" onclick="backToPaste()" style="background:#f1f5f9; color:#475569; padding:12px 28px; border-radius:50px; font-weight:600; border:none;">
                            <i class="fa fa-arrow-left"></i> Edit Source Text
                        </button>
                        <button type="button" id="btnSaveBulkQuestions" class="btn" onclick="openSaveConfirmation()" style="background:#16a34a; color:#fff; padding:13px 40px; border-radius:50px; font-weight:700; border:none; box-shadow:0 4px 14px rgba(22,163,74,0.25); font-size:15px;">
                            <i class="fa fa-check-circle"></i> Save All Questions to Database
                        </button>
                    </div>
                </div>
            </section>
        </section>
    </section>

    <!-- Confirmation Modal: Only for Confirmation Action -->
    <div id="saveConfirmModal" class="confirm-modal-overlay">
        <div class="confirm-modal-box">
            <div style="width:60px; height:60px; border-radius:50%; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:28px;">
                <i class="fa fa-question-circle"></i>
            </div>
            <h4 style="margin:0 0 10px; font-weight:700; color:#0f172a;">Confirm Question Upload</h4>
            <p id="confirmModalText" style="color:#64748b; font-size:14px; line-height:1.6; margin-bottom:24px;">
                Are you sure you want to save these questions to the selected test?
            </p>
            <div style="display:flex; justify-content:center; gap:12px;">
                <button type="button" class="btn btn-default" onclick="closeSaveConfirmation()" style="border-radius:50px; padding:9px 24px; font-weight:600;">
                    Cancel
                </button>
                <button type="button" id="btnConfirmExecuteSave" class="btn btn-success" onclick="executeSaveBulkQuestions()" style="border-radius:50px; padding:9px 28px; font-weight:700;">
                    Yes, Save Questions
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bulkPasteParser.js"></script>
    <script>
        var currentParsedQuestions = [];
        var bulkPreviewPage = 1;
        var bulkPreviewPageSize = 10;

        function formatBulkEditor(command) {
            document.execCommand(command, false, null);
            $('#bulkPasteEditor').focus();
        }

        function clearBulkPasteEditor() {
            $('#bulkPasteEditor').html('').focus();
        }

        function backToPaste() {
            $('#bulkPasteInputArea').show();
            $('#bulkPastePreviewArea').hide();
            $('html, body').animate({ scrollTop: $('#bulkPasteInputArea').offset().top - 80 }, 300);
        }

        // Clean Word XML and rich clipboard data on paste
        $('#bulkPasteEditor').on('paste', function(e) {
            try {
                var clipboardData = (e.originalEvent || e).clipboardData;
                if (clipboardData) {
                    var html = clipboardData.getData('text/html');
                    if (html && html.trim() !== '') {
                        e.preventDefault();
                        var cleaned = DLHSBulkParser.cleanRawHtml(html);
                        document.execCommand('insertHTML', false, cleaned);
                    }
                }
            } catch (err) {
                // Fallback to default paste
            }
        });

        function sendBulkPasteToEssay() {
            var testId = $('#bulkTestSelect').val();
            if (!testId) {
                alert("Please select a target test first.");
                $('#bulkTestSelect').focus();
                return;
            }
            var html = $('#bulkPasteEditor').html() || '';
            var plainText = ($('#bulkPasteEditor').text() || '').trim();
            if (!plainText && !/<img/i.test(html) && !/<table/i.test(html)) {
                alert("Please paste questions into the box first.");
                return;
            }
            // Store content in sessionStorage and redirect to addQuestionForm with essay anchor
            sessionStorage.setItem('dlhs_bulk_essay_content', html);
            window.location.href = 'addQuestionForm.php?testId=' + encodeURIComponent(testId) + '&loadEssay=1#essay';
        }

        function processBulkPaste() {
            var testId = $('#bulkTestSelect').val();
            if (!testId) {
                alert("Please select a target test first from the dropdown at the top.");
                $('#bulkTestSelect').focus();
                return;
            }

            var html = $('#bulkPasteEditor').html() || '';
            var plainText = ($('#bulkPasteEditor').text() || '').trim();
            if (!plainText && !/<img/i.test(html) && !/<table/i.test(html)) {
                alert("Please paste questions into the box first.");
                return;
            }

            var parseBtn = $('#btnParseBulkQuestions');
            var originalBtnHtml = parseBtn.html();
            parseBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Parsing Questions...');

            setTimeout(function() {
                try {
                    currentParsedQuestions = DLHSBulkParser.parse(html);

                    if (currentParsedQuestions.length === 0) {
                        if (confirm("No multiple-choice options (A-D) were found in the pasted content. Would you like to insert this content into the Essay questions editor instead?")) {
                            sendBulkPasteToEssay();
                        }
                        return;
                    }

                    bulkPreviewPage = 1;
                    renderBulkPreview(1);
                    $('#bulkPasteInputArea').hide();
                    $('#bulkPastePreviewArea').show();
                    $('html, body').animate({ scrollTop: $('#bulkPastePreviewArea').offset().top - 80 }, 300);
                } catch (err) {
                    console.error(err);
                    alert("An error occurred while parsing: " + err.message);
                } finally {
                    parseBtn.prop('disabled', false).html(originalBtnHtml);
                }
            }, 40);
        }

        function renderBulkPagination(totalPages) {
            if (totalPages <= 1) {
                $('#bulkPreviewPaginationTop, #bulkPreviewPaginationBottom').html('');
                return;
            }

            var startNum = (bulkPreviewPage - 1) * bulkPreviewPageSize + 1;
            var endNum = Math.min(bulkPreviewPage * bulkPreviewPageSize, currentParsedQuestions.length);
            var summaryText = '<span style="font-size:13px; font-weight:600; color:#003366; margin-right:12px;">Showing ' + startNum + '–' + endNum + ' of ' + currentParsedQuestions.length + ' questions</span>';

            var buttonsHtml = '<div class="btn-group btn-group-sm">';
            buttonsHtml += '<button type="button" class="btn btn-default" ' + (bulkPreviewPage <= 1 ? 'disabled' : '') + ' onclick="goToBulkPreviewPage(' + (bulkPreviewPage - 1) + ')"><i class="fa fa-chevron-left"></i> Prev</button>';
            buttonsHtml += '<span class="btn btn-default" style="cursor:default; font-weight:bold; background:#fff;">Page ' + bulkPreviewPage + ' / ' + totalPages + '</span>';
            buttonsHtml += '<button type="button" class="btn btn-default" ' + (bulkPreviewPage >= totalPages ? 'disabled' : '') + ' onclick="goToBulkPreviewPage(' + (bulkPreviewPage + 1) + ')">Next <i class="fa fa-chevron-right"></i></button>';
            buttonsHtml += '</div>';

            $('#bulkPreviewPaginationTop').html(summaryText + buttonsHtml);
            $('#bulkPreviewPaginationBottom').html(buttonsHtml);
        }

        function goToBulkPreviewPage(page) {
            var totalPages = Math.ceil(currentParsedQuestions.length / bulkPreviewPageSize);
            if (page < 1 || page > totalPages) return;
            syncVisiblePreviewEdits();
            bulkPreviewPage = page;
            renderBulkPreview(page);
            var listElem = document.getElementById('bulkPastePreviewArea');
            if (listElem) listElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function syncVisiblePreviewEdits() {
            $('.parsed-q-card').each(function() {
                var idx = $(this).data('index');
                if (currentParsedQuestions[idx]) {
                    var qText = $(this).find('.parsed-q-text').html();
                    if (qText !== undefined) currentParsedQuestions[idx].question = qText.trim();
                    var optA = $(this).find('.parsed-opt-a').html();
                    if (optA !== undefined) currentParsedQuestions[idx].optionA = optA.trim();
                    var optB = $(this).find('.parsed-opt-b').html();
                    if (optB !== undefined) currentParsedQuestions[idx].optionB = optB.trim();
                    var optC = $(this).find('.parsed-opt-c').html();
                    if (optC !== undefined) currentParsedQuestions[idx].optionC = optC.trim();
                    var optD = $(this).find('.parsed-opt-d').html();
                    if (optD !== undefined) currentParsedQuestions[idx].optionD = optD.trim();
                    var optE = $(this).find('.parsed-opt-e').html();
                    if (optE !== undefined) currentParsedQuestions[idx].optionE = optE.trim();
                    currentParsedQuestions[idx].correctOption = $(this).find('.parsed-correct').val();
                    currentParsedQuestions[idx].mark = $(this).find('.parsed-mark').val();
                }
            });
        }

        function renderBulkPreview(page) {
            bulkPreviewPage = page || bulkPreviewPage || 1;
            var totalQuestions = currentParsedQuestions.length;
            $('#parsedCount').text(totalQuestions);

            var totalPages = Math.ceil(totalQuestions / bulkPreviewPageSize);
            if (bulkPreviewPage > totalPages && totalPages > 0) bulkPreviewPage = totalPages;

            var startIndex = (bulkPreviewPage - 1) * bulkPreviewPageSize;
            var endIndex = Math.min(startIndex + bulkPreviewPageSize, totalQuestions);

            renderBulkPagination(totalPages);

            var html = '';
            for (var index = startIndex; index < endIndex; index++) {
                var q = currentParsedQuestions[index];
                html += '<div class="parsed-q-card" data-index="' + index + '">';
                html += '  <div style="margin-bottom:14px; font-size:15px; line-height:1.7;">';
                html += '    <strong style="color:#003366; font-size:16px;">Question ' + (index + 1) + ':</strong> ';
                html += '    <div class="parsed-q-text parsed-editable-field" contenteditable="true" spellcheck="false" data-index="' + index + '" data-field="question" style="display:inline-block; margin-left:6px; min-width:60%;">' + q.question + '</div>';
                html += '  </div>';
                html += '  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:10px; font-size:13px; color:#334155;">';
                html += '    <div class="option-box"><strong>A:</strong> <span class="parsed-opt parsed-opt-a parsed-editable-field" contenteditable="true" spellcheck="false" data-index="' + index + '" data-field="optionA" style="flex:1;">' + q.optionA + '</span></div>';
                html += '    <div class="option-box"><strong>B:</strong> <span class="parsed-opt parsed-opt-b parsed-editable-field" contenteditable="true" spellcheck="false" data-index="' + index + '" data-field="optionB" style="flex:1;">' + q.optionB + '</span></div>';
                html += '    <div class="option-box"><strong>C:</strong> <span class="parsed-opt parsed-opt-c parsed-editable-field" contenteditable="true" spellcheck="false" data-index="' + index + '" data-field="optionC" style="flex:1;">' + q.optionC + '</span></div>';
                html += '    <div class="option-box"><strong>D:</strong> <span class="parsed-opt parsed-opt-d parsed-editable-field" contenteditable="true" spellcheck="false" data-index="' + index + '" data-field="optionD" style="flex:1;">' + q.optionD + '</span></div>';
                if (q.optionE) {
                    html += '    <div class="option-box"><strong>E:</strong> <span class="parsed-opt parsed-opt-e parsed-editable-field" contenteditable="true" spellcheck="false" data-index="' + index + '" data-field="optionE" style="flex:1;">' + q.optionE + '</span></div>';
                }
                html += '  </div>';
                html += '  <div style="margin-top:14px; padding-top:12px; border-top:1px dashed #cbd5e1; display:flex; align-items:center; gap:20px; flex-wrap:wrap;">';
                html += '    <label style="margin:0; font-weight:600; font-size:13px; color:#475569;">Correct Option: <select class="parsed-correct" data-index="' + index + '" style="margin-left:6px; padding:4px 10px; border-radius:6px; border:1px solid #cbd5e1; font-weight:bold; color:#003366;">';
                ['A', 'B', 'C', 'D', 'E'].forEach(function(opt) {
                    if (opt === 'E' && !q.optionE) return;
                    var sel = (q.correctOption === opt) ? ' selected' : '';
                    html += '<option value="' + opt + '"' + sel + '>' + opt + '</option>';
                });
                html += '    </select></label>';
                html += '    <label style="margin:0; font-weight:600; font-size:13px; color:#475569;">Mark: <input type="number" class="parsed-mark" data-index="' + index + '" value="' + (q.mark || '1') + '" step="0.5" style="margin-left:6px; width:60px; padding:4px 8px; border-radius:6px; border:1px solid #cbd5e1; font-weight:bold;"></label>';
                html += '    <button type="button" class="btn btn-xs btn-danger" onclick="removeParsedQuestion(' + index + ')" style="margin-left:auto;"><i class="fa fa-trash"></i> Remove</button>';
                html += '  </div>';
                html += '</div>';
            }

            $('#parsedQuestionsList').html(html);
        }

        // Live in-memory sync
        $(document).on('input', '.parsed-editable-field', function() {
            var idx = $(this).data('index');
            var field = $(this).data('field');
            if (currentParsedQuestions[idx] && field) {
                currentParsedQuestions[idx][field] = $(this).html().trim();
            }
        });

        $(document).on('change', '.parsed-correct', function() {
            var idx = $(this).data('index');
            if (currentParsedQuestions[idx]) {
                currentParsedQuestions[idx].correctOption = $(this).val();
            }
        });

        $(document).on('input change', '.parsed-mark', function() {
            var idx = $(this).data('index');
            if (currentParsedQuestions[idx]) {
                currentParsedQuestions[idx].mark = $(this).val();
            }
        });

        function removeParsedQuestion(index) {
            syncVisiblePreviewEdits();
            currentParsedQuestions.splice(index, 1);
            if (currentParsedQuestions.length === 0) {
                backToPaste();
            } else {
                var totalPages = Math.ceil(currentParsedQuestions.length / bulkPreviewPageSize);
                if (bulkPreviewPage > totalPages) bulkPreviewPage = Math.max(1, totalPages);
                renderBulkPreview(bulkPreviewPage);
            }
        }

        // Confirmation modal handling (Only confirmation modals remain as modals)
        function openSaveConfirmation() {
            var testId = $('#bulkTestSelect').val();
            if (!testId) {
                alert("Please select a target test first.");
                $('#bulkTestSelect').focus();
                return;
            }
            if (!currentParsedQuestions || currentParsedQuestions.length === 0) {
                alert("No parsed questions to save.");
                return;
            }

            syncVisiblePreviewEdits();
            var testName = $('#bulkTestSelect option:selected').text();
            $('#confirmModalText').html('Are you sure you want to save all <strong>' + currentParsedQuestions.length + ' questions</strong> to test:<br><strong style="color:#003366;">' + testName + '</strong>?');
            $('#saveConfirmModal').css('display', 'flex');
        }

        function closeSaveConfirmation() {
            $('#saveConfirmModal').hide();
        }

        function executeSaveBulkQuestions() {
            var testId = $('#bulkTestSelect').val();
            var btn = $('#btnConfirmExecuteSave');
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Saving to Database...');

            $.ajax({
                url: 'bulkSaveQuestions.php',
                type: 'POST',
                data: {
                    testId: testId,
                    questions: JSON.stringify(currentParsedQuestions)
                },
                dataType: 'json',
                success: function(response) {
                    closeSaveConfirmation();
                    if (response && response.success) {
                        alert(response.message);
                        if (confirm("Questions have been saved! Would you like to view the questions list in Question Manager?")) {
                            window.location.href = 'viewQuestionForm.php?testId=' + encodeURIComponent(testId);
                        } else {
                            clearBulkPasteEditor();
                            backToPaste();
                        }
                    } else {
                        alert("Error: " + (response ? response.message : "Unknown error"));
                    }
                },
                error: function() {
                    closeSaveConfirmation();
                    alert("Failed to reach server. Please check your network connection.");
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        }

        // CSV parsing and importing
        function handleCsvFileSelected(event) {
            var file = event.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                processCSVContent(e.target.result);
            };
            reader.readAsText(file);
            event.target.value = '';
        }

        function parseCSV(text) {
            let lines = [];
            let row = [""];
            let inQuotes = false;
            for (let i = 0; i < text.length; i++) {
                let c = text[i];
                let next = text[i+1];
                if (c === '"') {
                    if (inQuotes && next === '"') {
                        row[row.length - 1] += '"';
                        i++;
                    } else {
                        inQuotes = !inQuotes;
                    }
                } else if (c === ',' && !inQuotes) {
                    row.push("");
                } else if ((c === '\r' || c === '\n') && !inQuotes) {
                    if (c === '\r' && next === '\n') { i++; }
                    lines.push(row);
                    row = [""];
                } else {
                    row[row.length - 1] += c;
                }
            }
            if (row.length > 1 || row[0] !== "") {
                lines.push(row);
            }
            return lines;
        }

        function processCSVContent(text) {
            var testId = $('#bulkTestSelect').val();
            if (!testId) {
                alert("Please select a target test first from the dropdown at the top.");
                $('#bulkTestSelect').focus();
                return;
            }

            var rows = parseCSV(text);
            if (rows.length === 0) {
                alert("The CSV file is empty.");
                return;
            }

            var header = rows[0];
            var expectedHeaders = ['questionserialno', 'question', 'optiona', 'optionb', 'optionc', 'optiond', 'correctoption', 'markforquestion'];
            var headerValid = true;
            if (header.length < 8) {
                headerValid = false;
            } else {
                for (var i = 0; i < 8; i++) {
                    var h = (header[i] || '').trim().toLowerCase();
                    if (h !== expectedHeaders[i]) {
                        headerValid = false;
                        break;
                    }
                }
            }

            if (!headerValid) {
                var gotHeaders = header.map(function(h) { return "'" + h.trim() + "'"; }).join(', ');
                alert("Invalid CSV Header Structure!\nGot: [" + gotHeaders + "]\nExpected: questionSerialNo, question, optionA, optionB, optionC, optionD, correctOption, markForQuestion");
                return;
            }

            var parsed = [];
            for (var r = 1; r < rows.length; r++) {
                var row = rows[r];
                if (row.length === 0 || (row.length === 1 && row[0].trim() === '')) continue;
                while (row.length < 8) row.push('');

                var qText = row[1].trim();
                var optA = row[2].trim();
                var optB = row[3].trim();
                var optC = row[4].trim();
                var optD = row[5].trim();
                var correct = (row[6].trim() || 'A').toUpperCase();
                var mark = row[7].trim() || '1';

                if (qText) {
                    parsed.push({
                        question: qText,
                        optionA: optA,
                        optionB: optB,
                        optionC: optC,
                        optionD: optD,
                        optionE: '',
                        correctOption: correct,
                        mark: mark
                    });
                }
            }

            if (parsed.length === 0) {
                alert("No valid question rows found in CSV.");
                return;
            }

            currentParsedQuestions = parsed;
            bulkPreviewPage = 1;
            renderBulkPreview(1);
            $('#bulkPasteInputArea').hide();
            $('#bulkPastePreviewArea').show();
            $('html, body').animate({ scrollTop: $('#bulkPastePreviewArea').offset().top - 80 }, 300);
        }

        // On document ready, handle URL params like fromCsv
        $(document).ready(function() {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('fromCsv') === '1') {
                var csvData = sessionStorage.getItem('dlhs_import_csv_data');
                if (csvData) {
                    sessionStorage.removeItem('dlhs_import_csv_data');
                    setTimeout(function() {
                        processCSVContent(csvData);
                    }, 100);
                }
            }
        });
    </script>
</body>
</html>
