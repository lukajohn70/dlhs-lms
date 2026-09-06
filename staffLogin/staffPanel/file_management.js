// Global variables
let filesDataTable = null;
let currentView = 'table';
let allFilesData = [];

$(document).ready(function() {
    // Initial load
    loadMyFiles();
    
    // File upload handling components
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const uploadForm = document.getElementById('uploadForm');
    
    // Drag and drop events
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        fileInput.files = files;
        handleFiles(files);
    });
    
    // Click upload zone
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
    
    // Target audience selection toggle
    $('#uploadedFor').change(function() {
        const val = $(this).val();
        if (val === 'specific_class') {
            $('#targetClassRow').slideDown();
            $('#studentsSelectionRow').slideUp();
        } else if (val === 'specific_students') {
            $('#targetClassRow').slideDown();
            $('#studentsSelectionRow').slideDown();
            // Automatically load students if a class is already selected
            const classId = $('#targetClass').val();
            if (classId) {
                loadStudentsForClass(classId);
            }
        } else {
            $('#targetClassRow').slideUp();
            $('#studentsSelectionRow').slideUp();
        }
    });
    
    // Target class change loads students
    $('#targetClass').change(function() {
        const classId = $(this).val();
        const uploadedFor = $('#uploadedFor').val();
        if (uploadedFor === 'specific_students' && classId) {
            loadStudentsForClass(classId);
        }
    });

    // Subject composite change filters class options via AJAX
    $('#subject_composite').change(function() {
        const compositeVal = $(this).val();
        const targetClassSelect = $('#targetClass');
        const subjectHidden = $('#subject');
        
        // Clear options
        targetClassSelect.find('option:not(:first)').remove();
        
        if (!compositeVal) {
            subjectHidden.val('');
            targetClassSelect.val('');
            $('#studentsList').html('<p class="text-muted" style="font-size: 12px; margin: 0;">Please select a class first to load students.</p>');
            return;
        }
        
        const parts = compositeVal.split('|');
        const sId = parts[0];
        const ygId = parts[1];
        
        subjectHidden.val(sId);
        
        targetClassSelect.html('<option value="">-- Loading Arms --</option>');
        
        $.ajax({
            url: 'getTeacherAssignedClassesAndSubjects.php',
            type: 'GET',
            data: { type: 'classesBySubject', subjectId: sId, yearGroupId: ygId },
            dataType: 'json',
            success: function(response) {
                let options = '<option value="">-- Select Class --</option>';
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        options += `<option value="${item.classId}">${escapeHtml(item.className)}</option>`;
                    });
                } else {
                    options = '<option value="">No classes assigned</option>';
                }
                targetClassSelect.html(options);
            },
            error: function() {
                targetClassSelect.html('<option value="">Error loading classes</option>');
            }
        });
    });
    
    // Form submission
    uploadForm.addEventListener('submit', (e) => {
        e.preventDefault();
        uploadFiles();
    });
});

// Toggle upload type UI
function toggleUploadType(type) {
    if (type === 'file') {
        $('#uploadArea').show();
        $('#youtubeLinkArea').hide();
        $('#filePreview').show();
    } else {
        $('#uploadArea').hide();
        $('#youtubeLinkArea').show();
        $('#filePreview').hide();
    }
}

// Display file selections
function handleFiles(files) {
    const filePreview = document.getElementById('filePreview');
    filePreview.innerHTML = '';
    
    if (files.length === 0) return;
    
    let totalSize = 0;
    Array.from(files).forEach(file => {
        totalSize += file.size;
        const fileDiv = document.createElement('div');
        fileDiv.style.cssText = 'padding: 8px 12px; background: rgba(104, 138, 126, 0.05); border: 1px solid rgba(104, 138, 126, 0.15); border-radius: 6px; margin-bottom: 8px; font-size: 13px; display: flex; align-items: center; justify-content: space-between;';
        fileDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="${getFileIconClass(file.name)}" style="color: #688a7e; font-size: 16px;"></i>
                <span style="font-weight: 500;">${file.name}</span>
            </div>
            <span class="text-muted" style="font-size: 11px;">${formatFileSize(file.size)}</span>
        `;
        filePreview.appendChild(fileDiv);
    });
}

function getFileIconClass(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    
    if (filename.includes('youtube.com') || filename.includes('youtu.be') || ext === 'youtube') {
        return 'fa fa-youtube-play';
    }
    
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
        'mkv': 'fa fa-file-video-o',
        'mp3': 'fa fa-file-audio-o',
        'wav': 'fa fa-file-audio-o',
        'txt': 'fa fa-file-text-o',
        'youtube_link': 'fa fa-youtube-play'
    };
    return iconMap[ext] || 'fa fa-file-o';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Load students of class
function loadStudentsForClass(classId) {
    $('#studentsList').html('<p style="font-size: 12px; color: #777;"><i class="fa fa-spinner fa-spin"></i> Loading students...</p>');
    $.ajax({
        url: 'get_students_for_class.php',
        type: 'POST',
        data: { classId: classId },
        dataType: 'json',
        success: function(students) {
            let html = '';
            if (students && students.length > 0) {
                html += '<div class="checkbox-item" style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 6px;">';
                html += '<label><input type="checkbox" id="selectAllStudents" onchange="toggleAllStudents()"> <strong>Select All Students</strong></label>';
                html += '</div>';
                
                students.forEach(student => {
                    html += '<div class="checkbox-item">';
                    html += `<label><input type="checkbox" name="selectedStudents[]" value="${student.studentId}" class="studentCheckbox"> ${student.firstName} ${student.surname}</label>`;
                    html += '</div>';
                });
            } else {
                html = '<p class="text-muted" style="font-size: 12px; margin: 0;">No students found in this class.</p>';
            }
            $('#studentsList').html(html);
        },
        error: function() {
            $('#studentsList').html('<p class="text-danger" style="font-size: 12px; margin: 0;">Failed to load students.</p>');
        }
    });
}

function toggleAllStudents() {
    const isChecked = $('#selectAllStudents').is(':checked');
    $('.studentCheckbox').prop('checked', isChecked);
}

// Upload file sharing
function uploadFiles() {
    const formData = new FormData();
    const uploadType = $('input[name="uploadType"]:checked').val() || 'file';
    const files = document.getElementById('fileInput').files;
    const youtubeLink = $('#youtubeLink').val().trim();
    
    if (uploadType === 'file' && files.length === 0) {
        showPremiumModal('Please select a file to share.', 'File Required', 'warning');
        return;
    }
    
    if (uploadType === 'link' && !youtubeLink) {
        showPremiumModal('Please enter a YouTube URL.', 'URL Required', 'warning');
        return;
    }
    
    if (!$('#title').val().trim()) {
        showPremiumModal('Please enter a title for this file.', 'Title Required', 'warning');
        return;
    }
    
    if (!$('#subject').val()) {
        showPremiumModal('Please select a subject.', 'Subject Required', 'warning');
        return;
    }

    const uploadedFor = $('#uploadedFor').val();
    if (uploadedFor === 'specific_class' && !$('#targetClass').val()) {
        showPremiumModal('Please select a target class.', 'Class Required', 'warning');
        return;
    }
    
    if (uploadedFor === 'specific_students') {
        if (!$('#targetClass').val()) {
            showPremiumModal('Please select a class to load students.', 'Class Required', 'warning');
            return;
        }
        const selectedCount = document.querySelectorAll('.studentCheckbox:checked').length;
        if (selectedCount === 0) {
            showPremiumModal('Please select at least one student to share the file with.', 'No Students Selected', 'warning');
            return;
        }
    }

    formData.append('uploadType', uploadType);

    if (uploadType === 'file') {
        Array.from(files).forEach(file => {
            formData.append('files[]', file);
        });
    } else {
        formData.append('youtubeLink', youtubeLink);
    }
    
    formData.append('title', $('#title').val());
    formData.append('description', $('#description').val());
    formData.append('subject', $('#subject').val());
    formData.append('uploadedFor', $('#uploadedFor').val());
    formData.append('targetClass', $('#targetClass').val());
    
    // Add targeted student IDs
    document.querySelectorAll('.studentCheckbox:checked').forEach(cb => {
        formData.append('selectedStudents[]', cb.value);
    });

    // Show loading indicator
    showLoading('Uploading file(s)...');
    $('#uploadProgress').show();
    const progressBar = $('#uploadProgress .progress-bar');
    progressBar.css('width', '0%').text('0%');

    $.ajax({
        url: 'file_upload_handler.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const pct = Math.round((evt.loaded / evt.total) * 100);
                    progressBar.css('width', pct + '%').text(pct + '%');
                    $('#progressText').text(`Uploading... ${pct}%`);
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            hideLoading();
            $('#uploadProgress').hide();
            try {
                const res = JSON.parse(response);
                if (res.success) {
                    showPremiumModal('File shared successfully!', 'Upload Complete', 'success');
                    clearForm();
                    loadMyFiles();
                } else {
                    showPremiumModal('Sharing failed: ' + (res.message || 'Unknown error'), 'Upload Failed', 'error');
                }
            } catch (e) {
                showPremiumModal('Sharing failed: ' + response, 'Upload Error', 'error');
            }
        },
        error: function() {
            hideLoading();
            $('#uploadProgress').hide();
            showPremiumModal('A network error occurred during upload. Please try again.', 'Network Error', 'error');
        }
    });
}

function clearForm() {
    $('#uploadForm')[0].reset();
    $('#subject').val('');
    $('#filePreview').html('');
    $('#targetClassRow').hide();
    $('#studentsSelectionRow').hide();
    $('#targetClass').find('option:not(:first)').remove();
    
    // Reset to file mode
    $('#uploadTypeFile').prop('checked', true);
    toggleUploadType('file');
}

// Fetch shared files list
function loadMyFiles() {
    showLoading('Loading shared files...');
    $.ajax({
        url: 'get_my_files.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                allFilesData = response.files || [];
                displayFiles(allFilesData);
            } else {
                $('#filesList').html('<div class="alert alert-info">No files shared yet. Start sharing files now!</div>');
            }
        },
        error: function() {
            hideLoading();
            $('#filesList').html('<div class="alert alert-danger">Failed to retrieve shared files. Please refresh.</div>');
        }
    });
}

function displayFiles(files) {
    if (currentView === 'table') {
        displayFilesTable(files);
    } else {
        displayFilesGrid(files);
    }
}

function displayFilesTable(files) {
    if (files.length === 0) {
        $('#filesList').html('<div class="text-muted" style="text-align: center; padding: 20px;">No matching shared files found.</div>');
        return;
    }
    
    if (filesDataTable) {
        filesDataTable.destroy();
    }
    
    let html = '<table id="filesListTable" class="table table-striped table-bordered" style="width:100%">';
    html += '<thead><tr>';
    html += '<th>Title</th>';
    html += '<th>Subject</th>';
    html += '<th>Audience</th>';
    html += '<th>Visibility</th>';
    html += '<th>Size</th>';
    html += '<th>Downloads</th>';
    html += '<th>Shared Date</th>';
    html += '<th style="width: 50px;">Action</th>';
    html += '</tr></thead><tbody>';
    
    files.forEach(file => {
        let audience = 'All Students';
        if (file.uploadedFor === 'specific_class') {
            audience = 'Class Target';
        } else if (file.uploadedFor === 'specific_students') {
            audience = 'Selected Students';
        }
        
        let visibilityHtml = '';
        if (file.isPublishedToStudents == 1) {
            visibilityHtml = `<span class="badge badge-success" onclick="toggleVisibility(${file.fileId})" style="cursor: pointer; background-color: #2ec4b6; color: white;" title="Click to Hide from Students"><i class="fa fa-eye"></i> Visible</span>`;
        } else {
            visibilityHtml = `<span class="badge badge-warning" onclick="toggleVisibility(${file.fileId})" style="cursor: pointer; background-color: #ff9f1c; color: white;" title="Click to Publish to Students"><i class="fa fa-eye-slash"></i> Hidden</span>`;
        }
        
        html += '<tr>';
        html += `<td><strong>${escapeHtml(file.title)}</strong><br><small class="text-muted">${escapeHtml(file.originalName)}</small></td>`;
        html += `<td>${escapeHtml(file.subjectName || 'General')}</td>`;
        html += `<td><span class="label label-info">${audience}</span></td>`;
        html += `<td>${visibilityHtml}</td>`;
        html += `<td>${formatFileSize(file.fileSize)}</td>`;
        html += `<td><span class="badge" style="background-color: #688a7e;">${file.downloadCount || 0}</span></td>`;
        html += `<td>${formatDate(file.created_at)}</td>`;
        html += `<td>
            <button class="btn-delete-file" onclick="deleteFile(${file.fileId})" title="Delete Shared File">
                <i class="fa fa-trash"></i>
            </button>
        </td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    $('#filesList').html(html);
    
    filesDataTable = $('#filesListTable').DataTable({
        "responsive": true,
        "pageLength": 10,
        "order": [[6, 'desc']],
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });
}

function displayFilesGrid(files) {
    if (files.length === 0) {
        $('#filesList').html('<div class="text-muted" style="text-align: center; padding: 20px;">No matching shared files found.</div>');
        return;
    }
    
    if (filesDataTable) {
        filesDataTable.destroy();
        filesDataTable = null;
    }
    
    let html = '<div class="row">';
    files.forEach(file => {
        let audience = 'All Students';
        if (file.uploadedFor === 'specific_class') {
            audience = 'Class Target';
        } else if (file.uploadedFor === 'specific_students') {
            audience = 'Selected Students';
        }
        
        let visibilityHtml = '';
        if (file.isPublishedToStudents == 1) {
            visibilityHtml = `<span class="label label-success" onclick="toggleVisibility(${file.fileId})" style="cursor: pointer; background-color: #2ec4b6; font-size: 10px;" title="Click to Hide"><i class="fa fa-eye"></i> Visible</span>`;
        } else {
            visibilityHtml = `<span class="label label-warning" onclick="toggleVisibility(${file.fileId})" style="cursor: pointer; background-color: #ff9f1c; font-size: 10px;" title="Click to Publish"><i class="fa fa-eye-slash"></i> Hidden</span>`;
        }
        
        html += '<div class="col-md-6" style="margin-bottom: 15px;">';
        html += '<div class="file-card">';
        html += '<div class="file-icon-wrapper">';
        html += `<i class="${getFileIconClass(file.originalName)}"></i>`;
        html += '</div>';
        html += '<div class="file-info">';
        html += `<h5 class="file-title">${escapeHtml(file.title)}</h5>`;
        html += `<p class="file-meta">`;
        html += `<strong>Subject:</strong> ${escapeHtml(file.subjectName || 'General')}<br>`;
        html += `<strong>Target:</strong> <span class="label label-info">${audience}</span><br>`;
        html += `<strong>Visibility:</strong> ${visibilityHtml}<br>`;
        html += `<strong>Downloads:</strong> ${file.downloadCount || 0} | <strong>Size:</strong> ${formatFileSize(file.fileSize)}<br>`;
        html += `<strong>Shared:</strong> ${formatDate(file.created_at)}`;
        html += `</p>`;
        html += '</div>';
        html += '<div class="file-actions">';
        html += `<button class="btn-delete-file" onclick="deleteFile(${file.fileId})" title="Delete Shared File"><i class="fa fa-trash"></i></button>`;
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';
    $('#filesList').html(html);
}

// Delete shared file
function deleteFile(fileId) {
    showPremiumConfirm(
        'Are you sure you want to stop sharing and delete this file? Students will no longer be able to access it.',
        'Delete File',
        function() {
            showLoading('Deleting shared file...');
            $.ajax({
                url: 'delete_file.php',
                type: 'POST',
                data: { fileId: fileId },
                success: function(response) {
                    hideLoading();
                    try {
                        const res = JSON.parse(response);
                        if (res.success) {
                            showPremiumModal('File deleted successfully!', 'Deleted', 'success');
                            loadMyFiles();
                        } else {
                            showPremiumModal('Delete failed: ' + res.message, 'Delete Failed', 'error');
                        }
                    } catch(e) {
                        showPremiumModal('Delete failed: ' + response, 'Delete Error', 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showPremiumModal('A network error occurred during deletion. Please try again.', 'Network Error', 'error');
                }
            });
        }
    );
}

function searchFiles() {
    const val = $('#searchFiles').val().toLowerCase();
    const filtered = allFilesData.filter(file => {
        return file.title.toLowerCase().includes(val) || 
               file.originalName.toLowerCase().includes(val) ||
               (file.description && file.description.toLowerCase().includes(val)) ||
               (file.subjectName && file.subjectName.toLowerCase().includes(val));
    });
    displayFiles(filtered);
}

function switchView(view) {
    currentView = view;
    $('.view-btn').removeClass('active');
    if (view === 'table') {
        $('#tableViewBtn').addClass('active');
    } else {
        $('#gridViewBtn').addClass('active');
    }
    displayFiles(allFilesData);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}

function showLoading(msg = 'Processing...') {
    $('#progressText').text(msg);
    $('#loadingOverlay').addClass('active');
}

function hideLoading() {
    $('#loadingOverlay').removeClass('active');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}


// Toggle file visibility
function toggleVisibility(fileId) {
    showLoading('Updating visibility...');
    $.ajax({
        url: 'toggle_file_visibility.php',
        type: 'POST',
        data: { fileId: fileId },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                // Instantly update the local dataset to keep UI extremely fast
                const file = allFilesData.find(f => f.fileId == fileId);
                if (file) {
                    file.isPublishedToStudents = response.newStatus;
                }
                displayFiles(allFilesData);
            } else {
                showPremiumModal('Failed to update visibility: ' + (response.message || 'Unknown error'), 'Update Failed', 'error');
            }
        },
        error: function() {
            hideLoading();
            showPremiumModal('A network error occurred while updating visibility.', 'Network Error', 'error');
        }
    });
}
