<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}
include "../../db_connection/dlhs_db_connection.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>Change Password | Admin DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <style>
        .compliance-list {
            list-style: none;
            padding-left: 0;
            margin-top: 15px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .compliance-item {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #6c757d;
            transition: color 0.3s ease;
        }
        .compliance-item i {
            margin-right: 10px;
            width: 16px;
        }
        .compliance-item.met {
            color: #28a745;
            font-weight: 600;
        }
        .compliance-item.not-met {
            color: #dc3545;
        }
        .compliance-item i.fa-check-circle { display: none; }
        .compliance-item.met i.fa-check-circle { display: inline-block; color: #28a745; }
        .compliance-item.met i.fa-circle-o { display: none; }
        
        .password-strength-meter {
            height: 4px;
            background-color: #eee;
            margin-top: 5px;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
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
                        <h3 class="page-header"><i class="fa fa-key"></i> Change Password</h3>
                        <ol class="breadcrumb">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-key"></i>Change Password</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-md-8">
                        <section class="panel">
                            <header class="panel-heading">
                                <?php echo isset($_GET['force']) ? 'Security Action Required' : 'Update Security Credentials'; ?>
                            </header>
                            <div class="panel-body">
                                <?php if (isset($_GET['force'])) { ?>
                                    <div class="alert alert-block alert-danger fade in">
                                        <h4><i class="fa fa-warning"></i> Action Required!</h4>
                                        <p>Your account is currently secured with a default password. For your protection and system integrity, you must set a new, strong password before accessing the administrative dashboard.</p>
                                    </div>
                                <?php } ?>

                                <form id="changePasswordForm">
                                    <div class="form-group">
                                        <label for="password1">New Password</label>
                                        <input type="password" name="password1" class="form-control" id="password1" placeholder="Enter new strong password" autocomplete="new-password">
                                        <div class="password-strength-meter">
                                            <div id="strengthFill" class="password-strength-fill"></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="password2">Confirm New Password</label>
                                        <input type="password" name="password2" class="form-control" id="password2" placeholder="Re-enter to confirm" autocomplete="new-password">
                                    </div>

                                    <ul class="compliance-list" id="complianceList">
                                        <li class="compliance-item" data-rule="length"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least 8 characters long</li>
                                        <li class="compliance-item" data-rule="uppercase"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one uppercase letter</li>
                                        <li class="compliance-item" data-rule="lowercase"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one lowercase letter</li>
                                        <li class="compliance-item" data-rule="number"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one number</li>
                                        <li class="compliance-item" data-rule="no-default"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> Not a default password (1234, 4321, etc.)</li>
                                        <li class="compliance-item" data-rule="no-repeat"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> No simple repeating sequences (e.g. 1111)</li>
                                        <li class="compliance-item" data-rule="no-sequence"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> No ascending/descending sequences (e.g. abcd)</li>
                                        <li class="compliance-item" data-rule="match"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> Passwords must match</li>
                                    </ul>

                                    <div style="margin-top: 15px;">
                                        <button type="button" class="btn btn-default" onclick="dlhsGeneratePassword()">
                                            <i class="fa fa-magic"></i> Generate Secure Password
                                        </button>
                                    </div>

                                    <div style="margin-top: 20px;">
                                        <button type="submit" class="btn btn-primary" id="submit" disabled>
                                            <i class="fa fa-save"></i> Update Password
                                        </button>
                                    </div>
                                    
                                    <div id="statusMessage" style="margin-top: 15px;"></div>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </section>
    </section>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="changePasswordAjax.js"></script>
    <script>
        function dlhsGeneratePassword() {
            var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
            var retVal = "";
            retVal += "ABCDEFGHIJKLMNOPQRSTUVWXYZ".charAt(Math.floor(Math.random() * 26));
            retVal += "abcdefghijklmnopqrstuvwxyz".charAt(Math.floor(Math.random() * 26));
            retVal += "0123456789".charAt(Math.floor(Math.random() * 10));
            retVal += "!@#$%^&*()_+".charAt(Math.floor(Math.random() * 12));
            for (var i = 0; i < 10; i++) {
                retVal += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            retVal = retVal.split('').sort(function(){return 0.5-Math.random()}).join('');
            $('#password1').val(retVal).attr('type', 'text').trigger('input');
            $('#password2').val(retVal).attr('type', 'text').trigger('input');
            alert("Generated Password: " + retVal + "\n\nIt has been filled in both fields for you.");
        }
    </script>
</body>
</html>
