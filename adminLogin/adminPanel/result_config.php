<?php
// result_config.php — School Term & Result Configuration Page (Admin Only)
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}
require_once '../../db_connection/dlhs_db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result & Term Configuration | DLHS</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
    <link href="fontAwesome/css/solid.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet">
    <script src="jQuery3.3.1.js"></script>
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Roboto, sans-serif; overflow-x: hidden; }
        .page-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.04); padding: 30px; margin-bottom: 24px; border: 1px solid #eef2f3; }
        .page-header-title { font-weight: 800; color: #003366; margin: 0 0 10px 0; }
        .section-title { font-size: 15px; font-weight: 700; color: #003366; text-transform: uppercase; border-bottom: 2px solid #003366; padding-bottom: 8px; margin: 25px 0 20px 0; display: flex; align-items: center; gap: 8px; }
        .form-label { font-weight: 700; font-size: 12px; color: #636e72; display: block; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.03em; }
        .form-control { border-radius: 8px; border: 1px solid #dfe6e9; padding: 10px 14px; font-size: 14px; height: auto; font-weight: 600; transition: all 0.2s; }
        .form-control:focus { border-color: #003366; box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1); outline: none; }
        .btn-save { background: linear-gradient(135deg, #003366, #005599); color: #ffd700; border: none; border-radius: 8px; font-weight: 700; padding: 12px 30px; font-size: 14px; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0, 51, 102, 0.2); }
        .btn-save:hover { filter: brightness(115%); color: #ffd700; transform: translateY(-1px); }
        .btn-save:active { transform: translateY(0); }
        .info-desc { font-size: 12px; color: #b2bec3; margin-top: 4px; font-weight: 500; }
        .status-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 12px; background: #e8f4fd; color: #003366; margin-top: 5px; }
        .grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media(max-width:768px) {
            .grid-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<section id="container">
    <?php include 'header.php'; ?>
    <?php include 'sideBar.php'; ?>
    
    <section id="main-content">
        <section class="wrapper">
            <!-- Header -->
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header-title"><i class="fa fa-gears"></i> Result &amp; Term Configuration</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li>Result Processing</li>
                        <li>Result Configuration</li>
                    </ol>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="row">
                <div class="col-lg-8 col-md-10">
                    <div class="page-card">
                        <h4 style="font-weight:700; color:#003366; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                            <i class="fa fa-calendar-check-o"></i> Set Academic Term &amp; Resumption Dates
                        </h4>
                        
                        <div class="alert alert-info" style="border-radius:10px; font-size:13px; background:#e8f4fd; border-color:#cbdcf7; color:#003366; line-height:1.5;">
                            <i class="fa fa-info-circle" style="font-size:16px;"></i> Configure important calendar dates for the active term. 
                            These dates will automatically populate the Printed Student Report Cards to display midterm vacation and term resumptions.
                        </div>

                        <form id="resultConfigForm" method="POST">
                            
                            <!-- Session & Term Selection -->
                            <div class="section-title"><i class="fa fa-sliders"></i> Scope Selection</div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label class="form-label">Academic Session</label>
                                    <input type="text" name="academicSession" id="academicSession" class="form-control" value="2024/2025" placeholder="e.g. 2024/2025">
                                    <div class="info-desc">Standard session format: YYYY/YYYY</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Academic Term</label>
                                    <select name="academicTerm" id="academicTerm" class="form-control">
                                        <option value="1">First Term</option>
                                        <option value="2">Second Term</option>
                                        <option value="3">Third Term</option>
                                    </select>
                                    <div class="info-desc">Active term for configuration</div>
                                </div>
                            </div>

                            <!-- Term Schedule -->
                            <div class="section-title"><i class="fa fa-calendar"></i> Main Term Schedule</div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label class="form-label">Term Start Date</label>
                                    <input type="date" name="termStartDate" id="termStartDate" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Term End Date</label>
                                    <input type="date" name="termEndDate" id="termEndDate" class="form-control">
                                </div>
                            </div>

                            <!-- Mid-Term & Next Term Details -->
                            <div class="section-title"><i class="fa fa-plane"></i> Vacation &amp; Resumptions</div>
                            <div class="form-group" style="margin-bottom:20px;">
                                <div class="grid-row">
                                    <div>
                                        <label class="form-label">Mid-Term Vacation Date</label>
                                        <input type="date" name="midTermVacationDate" id="midTermVacationDate" class="form-control">
                                    </div>
                                    <div>
                                        <label class="form-label">Mid-Term Resumption Date</label>
                                        <input type="date" name="midTermResumptionDate" id="midTermResumptionDate" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom:30px;">
                                <label class="form-label">Next Term Resumption Date</label>
                                <input type="date" name="nextTermResumptionDate" id="nextTermResumptionDate" class="form-control" style="max-width:50%;">
                                <div class="info-desc">Displayed at the bottom of the card for the following term</div>
                            </div>

                            <!-- Actions -->
                            <div style="border-top:1px solid #f1f5f9; padding-top:20px; display:flex; justify-content:space-between; align-items:center;">
                                <span class="status-pill" id="fetchStatus">
                                    <i class="fa fa-spinner fa-spin" id="spinner" style="display:none;"></i> 
                                    <span id="statusText">Ready</span>
                                </span>
                                <button type="button" class="btn-save" id="btnSaveConfig" onclick="saveConfig()">
                                    <i class="fa fa-save"></i> Save Configuration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </section>
</section>

<!-- Include Premium Modal -->
<script src="js/premium_modal.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/scripts.js"></script>

<script>
    $(document).ready(function() {
        // Trigger fetch when Session or Term dropdowns change
        $('#academicSession, #academicTerm').on('change keyup', function() {
            fetchExistingConfig();
        });
        
        // Initial fetch
        fetchExistingConfig();
    });

    function fetchExistingConfig() {
        var session = $('#academicSession').val();
        var term = $('#academicTerm').val();
        
        if (!session || !term) return;
        
        $('#spinner').show();
        $('#statusText').text('Loading settings...');
        
        $.ajax({
            url: 'get_result_config.php',
            type: 'POST',
            data: { academicSession: session, academicTerm: term },
            dataType: 'json',
            success: function(res) {
                $('#spinner').hide();
                if (res.success) {
                    if (res.data) {
                        $('#termStartDate').val(res.data.termStartDate || '');
                        $('#termEndDate').val(res.data.termEndDate || '');
                        $('#midTermVacationDate').val(res.data.midTermVacationDate || '');
                        $('#midTermResumptionDate').val(res.data.midTermResumptionDate || '');
                        $('#nextTermResumptionDate').val(res.data.nextTermResumptionDate || '');
                        $('#statusText').text('Settings loaded');
                    } else {
                        // Clear form fields if no existing configuration
                        $('#termStartDate').val('');
                        $('#termEndDate').val('');
                        $('#midTermVacationDate').val('');
                        $('#midTermResumptionDate').val('');
                        $('#nextTermResumptionDate').val('');
                        $('#statusText').text('New term setup');
                    }
                } else {
                    $('#statusText').text('Error fetching data');
                }
            },
            error: function() {
                $('#spinner').hide();
                $('#statusText').text('Connection failed');
            }
        });
    }

    function saveConfig() {
        var form = $('#resultConfigForm');
        
        $('#statusText').text('Saving...');
        $('#spinner').show();
        
        $.ajax({
            url: 'save_result_config.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                $('#spinner').hide();
                if (res.success) {
                    $('#statusText').text('Saved successfully');
                    showPremiumModal('Term result configuration has been successfully saved.', 'Success', 'success');
                } else {
                    $('#statusText').text('Save failed');
                    showPremiumModal(res.msg || 'An error occurred while saving dates.', 'Save Error', 'error');
                }
            },
            error: function() {
                $('#spinner').hide();
                $('#statusText').text('Connection failed');
                showPremiumModal('Failed to save settings due to a connection or server error.', 'Connection Error', 'error');
            }
        });
    }
</script>
</body>
</html>
