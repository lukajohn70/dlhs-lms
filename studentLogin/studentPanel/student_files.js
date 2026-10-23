let materialsData = [];
let submissionsData = [];
let teachersData = [];
let currentMaterialFilter = 'all';

$(document).ready(function() {
    loadStudentFiles();
    setupDropzone();
    setupUploadForm();
});

function loadStudentFiles() {
    showLoading();
    $.ajax({
        url: 'get_student_files.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                materialsData = response.files || [];
                submissionsData = response.mySubmissions || [];
                teachersData = response.teachers || [];

                // Update Badges & Stats
                $('#badgeMaterials').text(materialsData.length);
                $('#badgeSubmissions').text(submissionsData.length);
                $('#statReceivedCount').text(materialsData.length);
                $('#statSubmissionCount').text(submissionsData.length);

                // Populate Teachers dropdown
                populateTeachersDropdown(teachersData);

                // Render both tabs
                filterMaterials();
                filterSubmissions();
            } else {
                $('#materialsGrid').html('<div class="col-md-12"><div class="alert alert-warning text-center">Failed to load files: ' + escapeHtml(response.message || 'Unknown error') + '</div></div>');
            }
        },
        error: function() {
            hideLoading();
            $('#materialsGrid').html('<div class="col-md-12"><div class="alert alert-danger text-center">Failed to connect to file service. Please refresh the page.</div></div>');
        }
    });
}

function populateTeachersDropdown(teachers) {
    const $select = $('#targetStaffId');
    $select.find('option:not(:first)').remove();

    if (!teachers || teachers.length === 0) {
        $select.append('<option value="" disabled>No assigned teachers found for your class</option>');
        return;
    }

    teachers.forEach(t => {
        const roleLabel = t.subjectName ? ` (${t.subjectName})` : '';
        $select.append(`<option value="${t.staffId}" data-subject-id="${t.subjectId || 0}">${escapeHtml(t.teacherName)}${roleLabel}</option>`);
    });
}

function handleTeacherSelect(el) {
    const selectedOpt = $(el).find('option:selected');
    const subjectId = selectedOpt.data('subject-id') || 0;
    $('#selectedSubjectId').val(subjectId);
}

// Material Filtering
function setMaterialFilter(filter, el) {
    currentMaterialFilter = filter;
    $(el).siblings('.filter-pill').removeClass('active');
    $(el).addClass('active');
    filterMaterials();
}

function filterMaterials() {
    const q = ($('#searchMaterials').val() || '').toLowerCase();
    const filtered = materialsData.filter(file => {
        const matchesQuery = !q ||
            (file.title && file.title.toLowerCase().includes(q)) ||
            (file.originalName && file.originalName.toLowerCase().includes(q)) ||
            (file.teacherName && file.teacherName.toLowerCase().includes(q)) ||
            (file.subjectName && file.subjectName.toLowerCase().includes(q)) ||
            (file.description && file.description.toLowerCase().includes(q));

        let matchesFilter = true;
        if (currentMaterialFilter === 'staff') {
            matchesFilter = (file.senderType === 'staff');
        } else if (currentMaterialFilter === 'admin') {
            matchesFilter = (file.senderType === 'admin');
        }

        return matchesQuery && matchesFilter;
    });

    displayMaterials(filtered);
}

function displayMaterials(files) {
    if (files.length === 0) {
        $('#materialsGrid').html('<div class="col-md-12"><div class="alert alert-info text-center" style="margin-top: 15px;">No study materials found matching your criteria.</div></div>');
        return;
    }

    let html = '';
    files.forEach(file => {
        const fileIcon = getFileIconClass(file.originalName);
        const fileSize = formatFileSize(file.fileSize);
        const shareDate = formatDate(file.created_at);
        const isAdmin = (file.senderType === 'admin');
        const senderBadge = isAdmin 
            ? '<span class="label label-primary" style="background: linear-gradient(135deg, #0077b6 0%, #00AEEF 100%);"><i class="fa fa-shield"></i> Administration</span>'
            : `<span class="label label-default" style="background: #0284c7; color: white;"><i class="fa fa-user"></i> ${escapeHtml(file.teacherName || 'Teacher')}</span>`;

        html += '<div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">';
        html += '<div class="file-card">';
        html += '<div>';
        html += `<span class="file-subject">${escapeHtml(file.subjectName || 'GENERAL ACADEMIC')}</span>`;
        html += '<div style="display: flex; align-items: flex-start; margin-bottom: 10px;">';
        html += `<div class="file-icon-wrapper"><i class="${fileIcon}"></i></div>`;
        html += '<div style="flex: 1; min-width: 0;">';
        html += `<h4 class="file-title" title="${escapeHtml(file.title)}">${escapeHtml(file.title)}</h4>`;
        html += `<div>${senderBadge}</div>`;
        html += '</div>';
        html += '</div>';

        if (file.description) {
            html += `<p class="file-desc" title="${escapeHtml(file.description)}">${escapeHtml(file.description)}</p>`;
        } else {
            html += '<p class="file-desc text-muted" style="font-style: italic;">No additional notes provided.</p>';
        }
        html += '</div>';

        html += '<div>';
        html += '<div class="file-meta-row">';
        html += `<span><i class="fa fa-hdd-o"></i> ${fileSize}</span>`;
        html += `<span><i class="fa fa-calendar"></i> ${shareDate}</span>`;
        html += '</div>';

        html += '<div style="display: flex; gap: 8px; margin-top: 12px;">';
        html += `<button type="button" class="btn-dlhs-outline" style="flex: 1;" onclick="previewDocument(${file.fileId})"><i class="fa fa-eye"></i> Preview</button>`;
        html += `<a href="download_file.php?fileId=${file.fileId}" class="btn-dlhs-primary" style="flex: 1;" onclick="incrementLocalDownloadCount(this)"><i class="fa fa-download"></i> Download</a>`;
        html += '</div>';

        html += '</div>';
        html += '</div>';
        html += '</div>';
    });

    $('#materialsGrid').html(html);
}

// Submissions Filtering & Display
function filterSubmissions() {
    const q = ($('#searchSubmissions').val() || '').toLowerCase();
    const filtered = submissionsData.filter(file => {
        return !q ||
            (file.title && file.title.toLowerCase().includes(q)) ||
            (file.originalName && file.originalName.toLowerCase().includes(q)) ||
            (file.teacherName && file.teacherName.toLowerCase().includes(q)) ||
            (file.subjectName && file.subjectName.toLowerCase().includes(q));
    });

    displaySubmissions(filtered);
}

function displaySubmissions(submissions) {
    if (submissions.length === 0) {
        $('#submissionsContainer').html('<div class="alert alert-info text-center" style="margin-top: 15px;">You have not submitted any documents to your teachers yet. Click "Submit New Document" above to get started.</div>');
        return;
    }

    let html = '<table class="submission-table">' +
        '<thead><tr>' +
        '<th>Document</th>' +
        '<th>Submitted To</th>' +
        '<th>Subject</th>' +
        '<th>Size</th>' +
        '<th>Date Submitted</th>' +
        '<th>Status</th>' +
        '<th style="width: 140px; text-align: center;">Actions</th>' +
        '</tr></thead><tbody>';

    submissions.forEach(sub => {
        const fileIcon = getFileIconClass(sub.originalName);
        const fileSize = formatFileSize(sub.fileSize);
        const subDate = formatDate(sub.created_at);
        const teacherName = sub.teacherName || 'Assigned Teacher';

        html += '<tr>';
        html += `<td>
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="${fileIcon}" style="font-size: 20px; color: #00AEEF;"></i>
                <div>
                    <strong>${escapeHtml(sub.title)}</strong><br>
                    <small class="text-muted">${escapeHtml(sub.originalName)}</small>
                </div>
            </div>
        </td>`;
        html += `<td><strong>${escapeHtml(teacherName)}</strong></td>`;
        html += `<td><span class="label label-default">${escapeHtml(sub.subjectName || 'General')}</span></td>`;
        html += `<td>${fileSize}</td>`;
        html += `<td>${subDate}</td>`;
        html += `<td><span class="label label-success" style="background: #10b981;"><i class="fa fa-check-circle"></i> Delivered</span></td>`;
        html += `<td style="text-align: center;">
            <button type="button" class="btn btn-default btn-xs" onclick="previewDocument(${sub.fileId})" title="Preview"><i class="fa fa-eye"></i></button>
            <a href="download_file.php?fileId=${sub.fileId}" class="btn btn-primary btn-xs" style="background: #00AEEF; border: none;" title="Download My File"><i class="fa fa-download"></i></a>
        </td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';
    $('#submissionsContainer').html(html);
}

// Modal handling
function openSubmitModal() {
    $('#studentUploadForm')[0].reset();
    $('#selectedFileInfo').hide().empty();
    $('#studentUploadProgress').hide().find('.progress-bar').css('width', '0%');
    $('#btnSubmitFile').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit to Teacher');
    $('#submitModal').modal('show');
}

// Drag & drop dropzone
function setupDropzone() {
    const $dropzone = $('#dropzoneBox');
    
    $dropzone.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $dropzone.addClass('dragover');
    });

    $dropzone.on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $dropzone.removeClass('dragover');
    });

    $dropzone.on('drop', function(e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('studentFileInput').files = files;
            handleFileSelected(document.getElementById('studentFileInput'));
        }
    });
}

function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const infoHtml = `<div style="padding: 8px 12px; background: rgba(0, 174, 239, 0.08); border-radius: 6px; border: 1px solid rgba(0, 174, 239, 0.2); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="${getFileIconClass(file.name)}" style="color: #00AEEF; font-size: 18px;"></i>
                <span style="font-weight: 600; font-size: 13px; color: #020b1a;">${escapeHtml(file.name)}</span>
            </div>
            <span class="text-muted" style="font-size: 11px;">${formatFileSize(file.size)}</span>
        </div>`;
        $('#selectedFileInfo').html(infoHtml).show();

        // Auto-fill title if empty
        if (!$('#docTitle').val().trim()) {
            const cleanName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
            $('#docTitle').val(cleanName);
        }
    }
}

// AJAX Upload Form
function setupUploadForm() {
    $('#studentUploadForm').on('submit', function(e) {
        e.preventDefault();

        const teacherId = $('#targetStaffId').val();
        if (!teacherId) {
            alert('Please select a teacher to submit your document to.');
            return;
        }

        const title = $('#docTitle').val().trim();
        if (!title) {
            alert('Please provide a title for your document.');
            return;
        }

        const fileInput = document.getElementById('studentFileInput');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Please choose a file to attach.');
            return;
        }

        const formData = new FormData(this);
        const $progress = $('#studentUploadProgress');
        const $bar = $progress.find('.progress-bar');
        const $btn = $('#btnSubmitFile');

        $progress.show();
        $bar.css('width', '0%').text('0%');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting Document...');

        $.ajax({
            url: 'student_file_upload_handler.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        $bar.css('width', percent + '%').text(percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit to Teacher');
                if (response.success) {
                    $('#submitModal').modal('hide');
                    alert(response.message || 'Document submitted successfully!');
                    loadStudentFiles();
                } else {
                    alert('Submission error: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit to Teacher');
                alert('An error occurred during submission: ' + error);
            }
        });
    });
}

// Preview Document Modal
function previewDocument(fileId) {
    $('#previewModalBody').html('<p class="text-center text-muted" style="padding: 40px;"><i class="fa fa-spinner fa-spin"></i> Loading preview...</p>');
    $('#previewModal').modal('show');

    $.ajax({
        url: 'get_file_preview.php',
        type: 'GET',
        data: { fileId: fileId },
        success: function(response) {
            $('#previewModalBody').html(response);
        },
        error: function() {
            $('#previewModalBody').html('<div class="alert alert-danger text-center">Failed to load preview for this document.</div>');
        }
    });
}

function incrementLocalDownloadCount(el) {
    setTimeout(() => {
        loadStudentFiles();
    }, 2500);
}

// Utility Helpers
function getFileIconClass(filename) {
    if (!filename) return 'fa fa-file-o';
    const ext = filename.split('.').pop().toLowerCase();
    const iconMap = {
        'pdf': 'fa fa-file-pdf-o',
        'doc': 'fa fa-file-word-o',
        'docx': 'fa fa-file-word-o',
        'xls': 'fa fa-file-excel-o',
        'xlsx': 'fa fa-file-excel-o',
        'ppt': 'fa fa-file-powerpoint-o',
        'pptx': 'fa fa-file-powerpoint-o',
        'jpg': 'fa fa-file-image-o',
        'jpeg': 'fa fa-file-image-o',
        'png': 'fa fa-file-image-o',
        'gif': 'fa fa-file-image-o',
        'mp4': 'fa fa-file-video-o',
        'avi': 'fa fa-file-video-o',
        'mov': 'fa fa-file-video-o',
        'mp3': 'fa fa-file-audio-o',
        'wav': 'fa fa-file-audio-o',
        'zip': 'fa fa-file-archive-o',
        'rar': 'fa fa-file-archive-o',
        'txt': 'fa fa-file-text-o'
    };
    return iconMap[ext] || 'fa fa-file-o';
}

function formatFileSize(bytes) {
    if (!bytes || bytes <= 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}

function showLoading() {
    $('#loadingOverlay').addClass('active');
}

function hideLoading() {
    $('#loadingOverlay').removeClass('active');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
