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
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Assignment Management | DLHS</title>

    <!-- Core CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #00a0df;
            --secondary-color: #0acca2;
            --bg-light: #f4f7f6;
            --text-dark: #2c3e50;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .premium-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .premium-card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: #fff;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #dce4ec;
            padding: 10px 15px;
            height: auto;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 160, 223, 0.1);
        }

        .btn-premium {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            color: #fff;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-premium:hover {
            background: #0088bc;
            box-shadow: 0 4px 12px rgba(0, 160, 223, 0.3);
            color: #fff;
        }

        .arms-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            background: #fafafa;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 5px 0;
            cursor: pointer;
        }

        .checkbox-item input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        #filter-info {
            background: rgba(0, 160, 223, 0.05);
            border-left: 4px solid var(--primary-color);
            padding: 12px 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
            display: none;
        }

        /* Toast notifications */
        .toast-notify {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }

        .alert-toast {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>

<body>
    <section id="container">
        <?php include "header.php"; ?>
        <?php include "sideBar.php"; ?>

        <section id="main-content">
            <section class="wrapper">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header"><i class="fa fa-book"></i> Subject Assignment</h3>
                        <ol class="breadcrumb">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-tasks"></i>Academic Management</li>
                            <li>Subject Assignment</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <!-- Form Section -->
                    <div class="col-lg-4">
                        <div class="premium-card">
                            <div class="card-header">
                                <i class="fa fa-plus-circle"></i> Create New Assignment
                            </div>
                            <div class="card-body">
                                <form id="assignmentForm">
                                    <div class="form-group">
                                        <label>Year Group <span class="text-danger">*</span></label>
                                        <select class="form-control" name="yearGroup" id="yearGroup" required onchange="onYearGroupChange(this.value)">
                                            <option value="">-- Select Year Group --</option>
                                            <?php
                                            $res = $connection->query("SELECT * FROM yeargroup ORDER BY yearGroupName");
                                            while($row = $res->fetch_assoc()) {
                                                echo "<option value='{$row['yearGroupId']}'>{$row['yearGroupName']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Class Arms <span class="text-danger">*</span></label>
                                        <div class="arms-container">
                                            <div class="checkbox-item" style="border-bottom: 1px solid #eee; margin-bottom: 8px;">
                                                <input type="checkbox" id="selectAll"> <strong>Select All Arms</strong>
                                            </div>
                                            <div id="armsList">
                                                <div class="text-muted text-center py-2">Select a year group first</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Subject <span class="text-danger">*</span></label>
                                        <select class="form-control" name="subjectId" id="subjectId" required>
                                            <option value="">-- Select Subject --</option>
                                            <?php
                                            $res = $connection->query("SELECT * FROM subjects ORDER BY subjectName");
                                            while($row = $res->fetch_assoc()) {
                                                echo "<option value='{$row['subjectId']}'>{$row['subjectName']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Teacher <span class="text-danger">*</span></label>
                                        <select class="form-control" name="teacherId" id="teacherId" required>
                                            <option value="">-- Select Teacher --</option>
                                            <?php
                                            $res = $connection->query("SELECT staffId, surname, firstName FROM stafflogin ORDER BY surname");
                                            while($row = $res->fetch_assoc()) {
                                                $name = strtoupper($row['surname']) . " " . $row['firstName'];
                                                echo "<option value='{$row['staffId']}'>{$name}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-premium btn-block" id="btnSubmit">
                                        <i class="fa fa-save"></i> Save Assignment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Table Section -->
                    <div class="col-lg-8">
                        <div class="premium-card">
                            <div class="card-header">
                                <i class="fa fa-list"></i> Current Assignments
                            </div>
                            <div class="card-body">
                                <div id="filter-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span id="filter-text">Filtering results...</span>
                                        <button class="btn btn-xs btn-link" onclick="resetFilters()">Clear Filters</button>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table id="assignmentsTable" class="table table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Year Group</th>
                                                <th>Class/Arm</th>
                                                <th>Subject</th>
                                                <th>Teacher</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </section>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-notify"></div>

    <!-- Scripts -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="../../datatables/js/jquery.dataTables.min.js"></script>
    <script src="../../datatables/js/dataTables.responsive.min.js"></script>

    <script>
        let dataTable;
        let armsData = [];

        $(document).ready(function() {
            initTable();
            
            // Check for URL params
            const urlParams = new URLSearchParams(window.location.search);
            const yg = urlParams.get('yearGroupId');
            const cid = urlParams.get('classId');
            
            if (yg) {
                $('#yearGroup').val(yg).trigger('change');
                if (cid) {
                    setTimeout(() => {
                        $(`input[name="arms[]"][value="${cid}"]`).prop('checked', true).trigger('change');
                    }, 800);
                }
            }

            // Form Submit
            $('#assignmentForm').on('submit', function(e) {
                e.preventDefault();
                saveAssignment();
            });

            // Select All Toggle
            $('#selectAll').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('input[name="arms[]"]').prop('checked', isChecked).trigger('change');
            });

            // Dynamic Checkbox Listener
            $(document).on('change', 'input[name="arms[]"]', function() {
                updateFilterState();
            });
        });

        function initTable() {
            dataTable = $('#assignmentsTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'asc'], [1, 'asc']],
                ajax: {
                    url: 'subject_assignment_handler.php?action=fetch',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'yearGroupName' },
                    { data: 'className' },
                    { data: 'subjectName' },
                    { data: 'teacherName' },
                    { 
                        data: 'assignmentId',
                        className: 'text-center',
                        render: function(data) {
                            return `<button class="btn btn-danger btn-xs" onclick="deleteAssignment(${data})"><i class="fa fa-trash"></i></button>`;
                        }
                    }
                ],
                language: {
                    emptyTable: "No assignments found. Add one using the form."
                }
            });
        }

        function onYearGroupChange(val) {
            const list = $('#armsList');
            const ygName = $("#yearGroup option:selected").text();
            
            if (!val) {
                list.html('<div class="text-muted text-center py-2">Select a year group first</div>');
                resetTableFilter();
                return;
            }

            list.html('<div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Loading arms...</div>');
            
            $.get('subject_assignment_handler.php', { action: 'get_arms', yearGroupId: val }, function(res) {
                if (res.status === 'success') {
                    armsData = res.data;
                    if (armsData.length === 0) {
                        list.html('<div class="text-danger text-center py-2">No arms found for this group.</div>');
                    } else {
                        let html = '';
                        armsData.forEach(arm => {
                            html += `<label class="checkbox-item">
                                <input type="checkbox" name="arms[]" value="${arm.classId}"> ${arm.className}
                            </label>`;
                        });
                        list.html(html);
                    }
                }
                updateFilterState();
            });
        }

        function updateFilterState() {
            const ygVal = $('#yearGroup').val();
            const ygName = $("#yearGroup option:selected").text();
            const checked = $('input[name="arms[]"]:checked');
            const checkedNames = [];
            
            checked.each(function() {
                checkedNames.push($(this).parent().text().trim());
            });

            if (checked.length > 0) {
                // Filter by specific arms
                const regex = '^(' + checkedNames.map(n => n.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')$';
                dataTable.column(1).search(regex, true, false).draw();
                $('#filter-text').text(`Showing arms: ${checkedNames.join(', ')}`);
                $('#filter-info').fadeIn();
            } else if (ygVal) {
                // Filter by year group only
                dataTable.column(0).search(ygName).column(1).search('').draw();
                $('#filter-text').text(`Showing all for ${ygName}`);
                $('#filter-info').fadeIn();
            } else {
                resetTableFilter();
            }
        }

        function resetTableFilter() {
            dataTable.column(0).search('').column(1).search('').draw();
            $('#filter-info').fadeOut();
        }

        function resetFilters() {
            $('#yearGroup').val('').trigger('change');
            $('#selectAll').prop('checked', false);
            resetTableFilter();
        }

        function saveAssignment() {
            const btn = $('#btnSubmit');
            const classIds = $('input[name="arms[]"]:checked').map(function() { return this.value; }).get().join(',');
            
            if (!classIds) {
                notify('error', 'Selection Required', 'Please select at least one class arm.');
                return;
            }

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.post('subject_assignment_handler.php?action=assign', {
                classIds: classIds,
                subjectId: $('#subjectId').val(),
                teacherId: $('#teacherId').val()
            }, function(res) {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Assignment');
                if (res.status === 'success' || res.status === 'partial') {
                    notify('success', 'Saved', res.message);
                    dataTable.ajax.reload();
                } else {
                    notify('error', 'Failed', res.message);
                }
            });
        }

        function deleteAssignment(id) {
            if (!confirm('Are you sure you want to remove this assignment?')) return;

            $.post('subject_assignment_handler.php?action=delete', { id: id }, function(res) {
                if (res.status === 'success') {
                    notify('success', 'Deleted', 'Assignment removed successfully.');
                    dataTable.ajax.reload();
                } else {
                    notify('error', 'Error', res.message);
                }
            });
        }

        function notify(type, title, msg) {
            const container = $('#toastContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const toast = $(`
                <div class="alert ${alertClass} alert-toast">
                    <div class="d-flex align-items-center">
                        <i class="fa ${icon} fa-2x mr-3"></i>
                        <div>
                            <strong>${title}</strong><br>
                            <small>${msg}</small>
                        </div>
                    </div>
                </div>
            `);

            container.append(toast);
            setTimeout(() => {
                toast.fadeOut(() => toast.remove());
            }, 4000);
        }
    </script>
</body>
</html>
