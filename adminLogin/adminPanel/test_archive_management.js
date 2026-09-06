// Test Archive Management JavaScript

let testsTable;
let selectedTests = [];

$(document).ready(function() {
    console.log('Document ready - initializing...');
    loadStatistics();
    loadSessions();
    initializeDataTable();
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('input[name="testCheckbox[]"]').prop('checked', this.checked);
        updateSelectedTests();
    });
    
    console.log('Initialization complete');
});

function initializeDataTable() {
    console.log('Initializing DataTable...');
    console.log('Filter values:', {
        session: $('#filterSession').val(),
        term: $('#filterTerm').val(),
        testType: $('#filterTestType').val(),
        status: $('#filterStatus').val()
    });
    
    testsTable = $('#testsTable').DataTable({
        "ajax": {
            "url": "get_tests_with_archive.php",
            "type": "POST",
            "data": function(d) {
                d.session = $('#filterSession').val();
                d.term = $('#filterTerm').val();
                d.testType = $('#filterTestType').val();
                d.status = $('#filterStatus').val();
                console.log('Sending data:', d);
            },
            "dataSrc": "",
            "error": function(xhr, error, thrown) {
                console.error('DataTable AJAX error:', error, thrown);
                console.log('XHR status:', xhr.status);
                console.log('Response:', xhr.responseText);
                alert('Error loading tests: ' + error + '\nCheck console for details.');
            }
        },
        "columns": [
            {
                "data": null,
                "render": function(data, type, row) {
                    return '<input type="checkbox" name="testCheckbox[]" value="' + row.testId + '" onchange="updateSelectedTests()">';
                },
                "orderable": false
            },
            { "data": "testId" },
            { "data": "testName" },
            { "data": "subjectName" },
            { "data": "academicSession" },
            { "data": "term" },
            { "data": "testType" },
            { "data": "testDate" },
            {
                "data": null,
                "render": function(data, type, row) {
                    if (row.isArchived == 1) {
                        return '<span class="archive-badge"><i class="fa fa-archive"></i> Archived</span>';
                    } else {
                        let statusText = row.status == 0 ? 'Not Started' : (row.status == 1 ? 'In Progress' : 'Ended');
                        return '<span class="active-badge">' + statusText + '</span>';
                    }
                }
            },
            {
                "data": null,
                "render": function(data, type, row) {
                    let buttons = '';
                    if (row.isArchived == 1) {
                        buttons += '<button class="btn btn-sm btn-success" onclick="restoreTest(' + row.testId + ')" title="Restore"><i class="fa fa-undo"></i></button> ';
                    } else {
                        buttons += '<button class="btn btn-sm btn-warning" onclick="archiveSingleTest(' + row.testId + ')" title="Archive"><i class="fa fa-archive"></i></button> ';
                    }
                    buttons += '<button class="btn btn-sm btn-info" onclick="viewTestDetails(' + row.testId + ')" title="View Details"><i class="fa fa-eye"></i></button> ';
                    buttons += '<button class="btn btn-sm btn-primary" onclick="updateTestMetadata(' + row.testId + ')" title="Edit Metadata"><i class="fa fa-edit"></i></button>';
                    return buttons;
                },
                "orderable": false
            }
        ],
        "order": [[1, "desc"]],
        "pageLength": 25,
        "responsive": true
    });
}

function loadStatistics() {
    $.ajax({
        url: "get_archive_statistics.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            $('#activeTestsCount').text(data.activeTests);
            $('#archivedTestsCount').text(data.archivedTests);
            $('#totalTestsCount').text(data.totalTests);
        }
    });
}

function loadSessions() {
    $.ajax({
        url: "get_academic_sessions.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            let sessionOptions = '<option value="">All Sessions</option>';
            data.forEach(function(session) {
                sessionOptions += '<option value="' + session.sessionName + '">' + session.sessionName + '</option>';
            });
            $('#filterSession').html(sessionOptions);
        }
    });
}

function showFilter() {
    $('#filterSection').slideToggle();
}

function applyFilters() {
    testsTable.ajax.reload();
}

function clearFilters() {
    $('#filterSession').val('');
    $('#filterTerm').val('');
    $('#filterTestType').val('');
    $('#filterStatus').val('active');
    testsTable.ajax.reload();
}

function updateSelectedTests() {
    selectedTests = [];
    $('input[name="testCheckbox[]"]:checked').each(function() {
        selectedTests.push($(this).val());
    });
}

function showArchiveModal() {
    updateSelectedTests();
    
    if (selectedTests.length === 0) {
        $('.message10').html('<i class="fa fa-exclamation-triangle"></i> Please select at least one test to archive').fadeIn();
        setTimeout(function() { $('.message10').fadeOut(); }, 3000);
        return;
    }
    
    $('#archiveCount').text(selectedTests.length);
    $('#archiveModal').modal('show');
}

function confirmArchive() {
    let reason = $('#archiveReason').val();
    
    $.ajax({
        url: "archive_tests.php",
        type: "POST",
        data: {
            testIds: selectedTests,
            reason: reason
        },
        success: function(response) {
            if (response == 1) {
                $('#archiveModal').modal('hide');
                $('.message10').html('<i class="fa fa-check"></i> Tests archived successfully').css('color', 'green').fadeIn();
                setTimeout(function() { $('.message10').fadeOut(); }, 3000);
                testsTable.ajax.reload();
                loadStatistics();
                $('#archiveReason').val('');
                $('#selectAll').prop('checked', false);
            } else {
                alert('Error archiving tests: ' + response);
            }
        }
    });
}

function archiveSingleTest(testId) {
    if (confirm('Are you sure you want to archive this test?')) {
        $.ajax({
            url: "archive_tests.php",
            type: "POST",
            data: {
                testIds: [testId],
                reason: ''
            },
            success: function(response) {
                if (response == 1) {
                    $('.message10').html('<i class="fa fa-check"></i> Test archived successfully').css('color', 'green').fadeIn();
                    setTimeout(function() { $('.message10').fadeOut(); }, 3000);
                    testsTable.ajax.reload();
                    loadStatistics();
                } else {
                    alert('Error archiving test');
                }
            }
        });
    }
}

function restoreTest(testId) {
    if (confirm('Are you sure you want to restore this test from archive?')) {
        $.ajax({
            url: "restore_tests.php",
            type: "POST",
            data: { testIds: [testId] },
            success: function(response) {
                if (response == 1) {
                    $('.message10').html('<i class="fa fa-check"></i> Test restored successfully').css('color', 'green').fadeIn();
                    setTimeout(function() { $('.message10').fadeOut(); }, 3000);
                    testsTable.ajax.reload();
                    loadStatistics();
                } else {
                    alert('Error restoring test');
                }
            }
        });
    }
}

function bulkRestoreTests() {
    updateSelectedTests();
    
    if (selectedTests.length === 0) {
        $('.message10').html('<i class="fa fa-exclamation-triangle"></i> Please select at least one test to restore').fadeIn();
        setTimeout(function() { $('.message10').fadeOut(); }, 3000);
        return;
    }
    
    if (confirm('Restore ' + selectedTests.length + ' selected test(s) from archive?')) {
        $.ajax({
            url: "restore_tests.php",
            type: "POST",
            data: { testIds: selectedTests },
            success: function(response) {
                if (response == 1) {
                    $('.message10').html('<i class="fa fa-check"></i> Tests restored successfully').css('color', 'green').fadeIn();
                    setTimeout(function() { $('.message10').fadeOut(); }, 3000);
                    testsTable.ajax.reload();
                    loadStatistics();
                    $('#selectAll').prop('checked', false);
                } else {
                    alert('Error restoring tests');
                }
            }
        });
    }
}

function showSessionModal() {
    $.ajax({
        url: "get_academic_sessions.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            let html = '<table class="table table-bordered">';
            html += '<thead><tr><th>Session</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            
            data.forEach(function(session) {
                let currentBadge = session.isCurrentSession == 1 ? '<span class="label label-success">Current</span>' : '';
                html += '<tr>';
                html += '<td><strong>' + session.sessionName + '</strong> ' + currentBadge + '</td>';
                html += '<td>' + session.startDate + '</td>';
                html += '<td>' + session.endDate + '</td>';
                html += '<td>' + (session.isCurrentSession == 1 ? 'Active' : 'Inactive') + '</td>';
                html += '<td><button class="btn btn-sm btn-primary" onclick="setCurrentSession(\'' + session.sessionName + '\')">Set as Current</button></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $('#sessionsList').html(html);
            $('#sessionModal').modal('show');
        }
    });
}

function setCurrentSession(sessionName) {
    if (confirm('Set ' + sessionName + ' as the current academic session?')) {
        $.ajax({
            url: "set_current_session.php",
            type: "POST",
            data: { sessionName: sessionName },
            success: function(response) {
                if (response == 1) {
                    alert('Current session updated successfully');
                    $('#sessionModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error updating session');
                }
            }
        });
    }
}

function viewTestDetails(testId) {
    window.location.href = 'view_test_details.php?testId=' + testId;
}

function updateTestMetadata(testId) {
    // Open modal to update session, term, and test type
    let html = '<div class="modal fade" id="metadataModal" tabindex="-1">';
    html += '<div class="modal-dialog"><div class="modal-content">';
    html += '<div class="modal-header"><h4>Update Test Metadata</h4></div>';
    html += '<div class="modal-body">';
    html += '<div class="form-group"><label>Academic Session</label>';
    html += '<input type="text" id="metaSession" class="form-control" placeholder="e.g., 2024/2025"></div>';
    html += '<div class="form-group"><label>Term</label>';
    html += '<select id="metaTerm" class="form-control">';
    html += '<option value="First Term">First Term</option>';
    html += '<option value="Second Term">Second Term</option>';
    html += '<option value="Third Term">Third Term</option>';
    html += '</select></div>';
    html += '<div class="form-group"><label>Test Type</label>';
    html += '<select id="metaTestType" class="form-control">';
    html += '<option value="CA1">CA1</option>';
    html += '<option value="CA2">CA2</option>';
    html += '<option value="CA3">CA3</option>';
    html += '<option value="Exam">Exam</option>';
    html += '<option value="Mock">Mock</option>';
    html += '<option value="Quiz">Quiz</option>';
    html += '<option value="Other">Other</option>';
    html += '</select></div></div>';
    html += '<div class="modal-footer">';
    html += '<button class="btn btn-default" onclick="$(\'#metadataModal\').modal(\'hide\')">Cancel</button>';
    html += '<button class="btn btn-primary" onclick="saveTestMetadata(' + testId + ')">Save</button>';
    html += '</div></div></div></div>';
    
    $('body').append(html);
    $('#metadataModal').modal('show');
}

function saveTestMetadata(testId) {
    let session = $('#metaSession').val();
    let term = $('#metaTerm').val();
    let testType = $('#metaTestType').val();
    
    $.ajax({
        url: "update_test_metadata.php",
        type: "POST",
        data: {
            testId: testId,
            session: session,
            term: term,
            testType: testType
        },
        success: function(response) {
            if (response == 1) {
                $('#metadataModal').modal('hide');
                $('.message10').html('<i class="fa fa-check"></i> Metadata updated successfully').css('color', 'green').fadeIn();
                setTimeout(function() { $('.message10').fadeOut(); }, 3000);
                testsTable.ajax.reload();
            } else {
                alert('Error updating metadata');
            }
        }
    });
}
