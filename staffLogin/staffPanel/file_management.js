// Global variables
let receivedFilesData = [];
let sentFilesData = [];
let receivedFilter = 'all';
let sentFilter = 'all';
let currentView = 'table';
let receivedDataTable = null;
let sentDataTable = null;

$(document).ready(function() {
    // Initial load
    loadMyFiles();

    // Setup initial audience state
    handleAudienceChange();
    
    // File upload handling components
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
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
    
    // YouTube link live preview listener
    $('#youtubeLink').on('input change paste keyup', function() {
        updateYouTubePreview($(this).val());
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
        
        targetClassSelect.find('option:not(:first)').remove();
        
        if (!compositeVal) {
            subjectHidden.val('');
            targetClassSelect.val('');
            $('#studentsList').html('<p class="text-muted" style="font-size: 12px; margin: 0;">Please select a class first to load students.</p>');
            $('#multiClassesList').html('<p class="text-muted" style="font-size: 12px; margin: 0;">Please select a subject first to load classes.</p>');
            return;
        }
        
        const parts = compositeVal.split('|');
        const sId = parts[0];
        const ygId = parts[1];
        
        subjectHidden.val(sId);
        
        targetClassSelect.html('<option value="">-- Loading Arms --</option>');
        $('#multiClassesList').html('<p class="text-muted" style="font-size: 12px; margin: 0;"><i class="fa fa-spinner fa-spin"></i> Loading classes...</p>');
        
        $.ajax({
            url: 'getTeacherAssignedClassesAndSubjects.php',
            type: 'GET',
            data: { type: 'classesBySubject', subjectId: sId, yearGroupId: ygId },
            dataType: 'json',
            success: function(response) {
                let options = '<option value="">-- Select Class --</option>';
                let multiOptions = '';
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        options += `<option value="${item.classId}">${escapeHtml(item.className)}</option>`;
                        multiOptions += `<div class="checklist-item"><label style="margin: 0; font-weight: normal; cursor: pointer;"><input type="checkbox" name="targetClasses[]" value="${item.classId}" class="multiClassCheckbox"> ${escapeHtml(item.className)}</label></div>`;
                    });
                } else {
                    options = '<option value="">No classes assigned</option>';
                    multiOptions = '<p class="text-muted" style="font-size: 12px; margin: 0;">No classes assigned for this subject.</p>';
                }
                targetClassSelect.html(options);
                $('#multiClassesList').html(multiOptions);
            },
            error: function() {
                targetClassSelect.html('<option value="">Error loading classes</option>');
                $('#multiClassesList').html('<p class="text-danger" style="font-size: 12px; margin: 0;">Error loading classes.</p>');
            }
        });
    });
    
    // Form submission
    uploadForm.addEventListener('submit', (e) => {
        e.preventDefault();
        uploadFiles();
    });
});

// Audience selector change handler
function handleAudienceChange() {
    const val = $('#uploadedFor').val();
    const btn = $('#submitShareBtn');
    const star = $('#subjectRequiredStar');

    // Reset visibility
    $('#adminNoticePanel').hide();
    $('#allTeachersNoticePanel').hide();
    $('#teachersSelectionRow').hide();
    $('#targetClassRow').hide();
    $('#multiClassRow').hide();
    $('#studentsSelectionRow').hide();

    if (val === 'admin') {
        $('#adminNoticePanel').slideDown(150);
        btn.html('<i class="fa fa-shield"></i> Submit Document to Administration');
        star.hide();
    } else if (val === 'all_teachers') {
        $('#allTeachersNoticePanel').slideDown(150);
        btn.html('<i class="fa fa-users"></i> Broadcast to All Teachers');
        star.hide();
    } else if (val === 'subject_teachers') {
        btn.html('<i class="fa fa-book"></i> Share with Department Colleagues');
        star.show();
    } else if (val === 'specific_teachers') {
        $('#teachersSelectionRow').slideDown(150);
        btn.html('<i class="fa fa-user-plus"></i> Share with Selected Teachers');
        star.hide();
    } else if (val === 'specific_class') {
        $('#targetClassRow').slideDown(150);
        btn.html('<i class="fa fa-paper-plane"></i> Share with Class Arm');
        star.show();
    } else if (val === 'multiple_classes') {
        $('#multiClassRow').slideDown(150);
        btn.html('<i class="fa fa-paper-plane"></i> Share with Class Arms');
        star.show();
    } else if (val === 'specific_students') {
        $('#targetClassRow').slideDown(150);
        $('#studentsSelectionRow').slideDown(150);
        btn.html('<i class="fa fa-paper-plane"></i> Share with Students');
        star.show();
        const classId = $('#targetClass').val();
        if (classId) {
            loadStudentsForClass(classId);
        }
    } else {
        // All students
        btn.html('<i class="fa fa-paper-plane"></i> Share with All Students');
        star.show();
    }
}

function toggleSelectAllColleagues() {
    const isChecked = $('#selectAllColleagues').is(':checked');
    $('.colleagueCheckbox').prop('checked', isChecked);
}

function filterColleaguesChecklist() {
    const q = ($('#teacherSearchStaff').val() || '').toLowerCase();
    $('.colleague-item').each(function() {
        const text = $(this).attr('data-text') || '';
        if (!q || text.includes(q)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

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
    const fileInput = document.getElementById('fileInput');
    filePreview.innerHTML = '';
    
    if (files.length === 0) return;
    
    const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
    const ALLOWED_EXTS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'odt', 'ods', 'odp', 'zip', 'rar', '7z', 'tar', 'gz', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'avi', 'mov', 'mkv', 'mp3', 'wav', 'm4a', 'aac', 'ogg'];
    
    for (let i = 0; i < files.length; i++) {
        const ext = files[i].name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXTS.includes(ext)) {
            showPremiumModal(`"${escapeHtml(files[i].name)}" has an unsupported format (.${ext}). Allowed formats: PDF, Word, Excel, PowerPoint, Text, ZIP, Images, Audio, Video.`, 'Unsupported File Type', 'warning');
            fileInput.value = '';
            filePreview.innerHTML = '';
            return;
        }
        if (files[i].size > MAX_FILE_SIZE) {
            showPremiumModal(`"${escapeHtml(files[i].name)}" is ${formatFileSize(files[i].size)}, which exceeds the 100MB limit.`, 'File Too Large', 'warning');
            fileInput.value = '';
            filePreview.innerHTML = '';
            return;
        }
    }
    
    Array.from(files).forEach(file => {
        const fileDiv = document.createElement('div');
        fileDiv.style.cssText = 'padding: 8px 12px; background: rgba(0, 174, 239, 0.05); border: 1px solid rgba(0, 174, 239, 0.15); border-radius: 6px; margin-bottom: 8px; font-size: 13px; display: flex; align-items: center; justify-content: space-between;';
        fileDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="${getFileIconClass(file.name)}" style="color: #00AEEF; font-size: 16px;"></i>
                <span style="font-weight: 500;">${escapeHtml(file.name)}</span>
            </div>
            <span class="text-muted" style="font-size: 11px;">${formatFileSize(file.size)}</span>
        `;
        filePreview.appendChild(fileDiv);
    });
}

function getFileIconClass(filename) {
    if (!filename) return 'fa fa-file-o';
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
        'zip': 'fa fa-file-archive-o',
        'rar': 'fa fa-file-archive-o',
        'txt': 'fa fa-file-text-o'
    };
    return iconMap[ext] || 'fa fa-file-o';
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
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
                html += '<div class="checklist-item" style="margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">';
                html += '<label style="margin: 0; font-weight: 700; cursor: pointer;"><input type="checkbox" id="selectAllStudents" onchange="toggleAllStudents()"> Select All Students</label>';
                html += '</div>';
                
                students.forEach(student => {
                    html += '<div class="checklist-item">';
                    html += `<label style="margin: 0; font-weight: normal; cursor: pointer;"><input type="checkbox" name="selectedStudents[]" value="${student.studentId}" class="studentCheckbox"> ${escapeHtml(student.firstName)} ${escapeHtml(student.surname)}</label>`;
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
        showPremiumModal('Please enter a title for this document.', 'Title Required', 'warning');
        return;
    }

    const uploadedFor = $('#uploadedFor').val();
    
    // Validate subject requirement for subject_teachers and student options
    if (uploadedFor === 'subject_teachers' || uploadedFor === 'specific_class' || uploadedFor === 'multiple_classes' || uploadedFor === 'specific_students' || uploadedFor === 'all') {
        if (!$('#subject').val()) {
            showPremiumModal('Please select a subject for this document.', 'Subject Required', 'warning');
            return;
        }
    }

    if (uploadedFor === 'specific_teachers') {
        const selectedTeachers = document.querySelectorAll('.colleagueCheckbox:checked');
        if (selectedTeachers.length === 0) {
            showPremiumModal('Please select at least one colleague to share this document with.', 'No Teachers Selected', 'warning');
            return;
        }
        selectedTeachers.forEach(cb => {
            formData.append('selectedTeachers[]', cb.value);
        });
    }

    if (uploadedFor === 'specific_class' && !$('#targetClass').val()) {
        showPremiumModal('Please select a target class.', 'Class Required', 'warning');
        return;
    }

    if (uploadedFor === 'multiple_classes') {
        const selectedClasses = document.querySelectorAll('.multiClassCheckbox:checked');
        if (selectedClasses.length === 0) {
            showPremiumModal('Please select at least one class arm.', 'Classes Required', 'warning');
            return;
        }
        selectedClasses.forEach(cb => {
            formData.append('targetClasses[]', cb.value);
        });
    }
    
    if (uploadedFor === 'specific_students') {
        const selectedCount = document.querySelectorAll('.studentCheckbox:checked').length;
        if (selectedCount === 0) {
            showPremiumModal('Please select at least one student.', 'No Students Selected', 'warning');
            return;
        }
        document.querySelectorAll('.studentCheckbox:checked').forEach(cb => {
            formData.append('selectedStudents[]', cb.value);
        });
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
    formData.append('categoryId', $('#categoryId').val() || 5);
    formData.append('description', $('#description').val());
    formData.append('subject', $('#subject').val() || 0);
    formData.append('uploadedFor', uploadedFor);
    formData.append('targetClass', $('#targetClass').val() || '');

    // Show loading
    showLoading('Sharing document...');
    $('#uploadProgress').show();
    const progressBar = $('#uploadProgress .progress-bar');
    progressBar.css('width', '20%');

    $.ajax({
        url: 'file_upload_handler.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const pct = Math.round((evt.loaded / evt.total) * 100);
                    progressBar.css('width', pct + '%');
                }
            }, false);
            return xhr;
        },
        success: function(res) {
            hideLoading();
            $('#uploadProgress').hide();
            if (res.success) {
                showPremiumModal(res.message || 'Document shared successfully!', 'Success', 'success');
                clearForm();
                loadMyFiles();
            } else {
                showPremiumModal('Sharing failed: ' + (res.message || 'Unknown error'), 'Failed', 'error');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            $('#uploadProgress').hide();
            
            let errorMsg = 'A network error occurred. Please check connection and try again.';
            let errorTitle = 'Sharing Failed';

            if (xhr.status === 413) {
                errorMsg = 'The file you are uploading exceeds the server upload limit (upload_max_filesize / post_max_size in php.ini). Please choose a smaller file or increase limits in php.ini.';
                errorTitle = 'File Too Large';
            } else if (xhr.status === 401 || (xhr.responseJSON && xhr.responseJSON.sessionExpired)) {
                errorMsg = 'Your session has expired. Please refresh the page and log in again.';
                errorTitle = 'Session Expired';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    if (parsed.message) {
                        errorMsg = parsed.message;
                    }
                } catch(e) {
                    const cleanText = xhr.responseText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                    if (cleanText.length > 0 && cleanText.length < 300) {
                        errorMsg = cleanText;
                    } else if (cleanText.length >= 300) {
                        errorMsg = cleanText.substring(0, 250) + '...';
                    }
                }
            } else if (error) {
                errorMsg = 'Error: ' + error;
            }

            showPremiumModal(errorMsg, errorTitle, 'error');
        }
    });
}

function clearForm() {
    $('#uploadForm')[0].reset();
    $('#subject').val('');
    $('#filePreview').html('');
    $('#youtubePreviewBox').hide();
    $('#uploadTypeFile').prop('checked', true);
    toggleUploadType('file');
    handleAudienceChange();
}

// Fetch shared files list
function loadMyFiles() {
    showLoading('Loading documents...');
    $.ajax({
        url: 'get_my_files.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                receivedFilesData = response.receivedFiles || [];
                sentFilesData = response.sentFiles || [];

                // Update Stats
                const s = response.stats || {};
                $('#statReceivedCount').text(s.totalReceived || 0);
                $('#statReceivedSub').html(`Received <small style="opacity: 0.85;">(${s.fromAdmin || 0} Admin &bull; ${s.fromTeachers || 0} Staff)</small>`);
                $('#statSentCount').text(s.totalSent || 0);
                $('#statAdminSubmissions').text((s.adminPending || 0) + (s.adminReviewed || 0));
                $('#statAdminSubmissionsSub').html(`To Admin <small style="color: #fef08a;">(${s.adminReviewed || 0} Reviewed &bull; ${s.adminPending || 0} Pending)</small>`);

                // Downloads sum
                let totalDownloads = 0;
                sentFilesData.forEach(f => {
                    totalDownloads += parseInt(f.downloadCount || 0);
                });
                $('#statDownloads').text(totalDownloads);

                // Update badges
                $('#badgeReceivedTab').text(receivedFilesData.length);
                $('#badgeSentTab').text(sentFilesData.length);

                renderReceivedFiles(receivedFilesData);
                renderSentFiles(sentFilesData);
            }
        },
        error: function() {
            hideLoading();
            $('#receivedFilesContainer').html('<div class="alert alert-danger">Failed to load received documents.</div>');
            $('#sentFilesContainer').html('<div class="alert alert-danger">Failed to load your shared documents.</div>');
        }
    });
}

// Filter functions
function setReceivedFilter(filter, el) {
    receivedFilter = filter;
    $(el).siblings('.filter-pill').removeClass('active');
    $(el).addClass('active');
    filterReceivedFiles();
}

function filterReceivedFiles() {
    const q = ($('#searchReceivedFiles').val() || '').toLowerCase();
    const filtered = receivedFilesData.filter(file => {
        const matchesQuery = !q ||
            (file.title && file.title.toLowerCase().includes(q)) ||
            (file.originalName && file.originalName.toLowerCase().includes(q)) ||
            (file.senderName && file.senderName.toLowerCase().includes(q)) ||
            (file.subjectName && file.subjectName.toLowerCase().includes(q)) ||
            (file.categoryName && file.categoryName.toLowerCase().includes(q));

        let matchesFilter = true;
        if (receivedFilter === 'admin') {
            matchesFilter = (file.uploaderType === 'admin');
        } else if (receivedFilter === 'teachers') {
            matchesFilter = (file.uploaderType === 'staff');
        } else if (receivedFilter === 'students') {
            matchesFilter = (file.uploaderType === 'student');
        } else if (receivedFilter === 'subject') {
            matchesFilter = (file.uploadedFor === 'subject_teachers');
        }

        return matchesQuery && matchesFilter;
    });

    renderReceivedFiles(filtered);
}

function setSentFilter(filter, el) {
    sentFilter = filter;
    $(el).siblings('.filter-pill').removeClass('active');
    $(el).addClass('active');
    filterSentFiles();
}

function filterSentFiles() {
    const q = ($('#searchSentFiles').val() || '').toLowerCase();
    const filtered = sentFilesData.filter(file => {
        const matchesQuery = !q ||
            (file.title && file.title.toLowerCase().includes(q)) ||
            (file.originalName && file.originalName.toLowerCase().includes(q)) ||
            (file.subjectName && file.subjectName.toLowerCase().includes(q)) ||
            (file.categoryName && file.categoryName.toLowerCase().includes(q));

        let matchesFilter = true;
        if (sentFilter === 'admin') {
            matchesFilter = (file.uploadedFor === 'admin');
        } else if (sentFilter === 'teachers') {
            matchesFilter = inArray(file.uploadedFor, ['all_teachers', 'subject_teachers', 'specific_teachers']);
        } else if (sentFilter === 'students') {
            matchesFilter = inArray(file.uploadedFor, ['all', 'specific_class', 'multiple_classes', 'specific_students']);
        }

        return matchesQuery && matchesFilter;
    });

    renderSentFiles(filtered);
}

function inArray(needle, haystack) {
    return haystack.indexOf(needle) !== -1;
}

// Render Received Files
function renderReceivedFiles(files) {
    if (files.length === 0) {
        $('#receivedFilesContainer').html('<div class="alert alert-info" style="text-align: center; margin-top: 15px;">No documents found matching this filter.</div>');
        return;
    }

    if (currentView === 'table') {
        renderReceivedTable(files);
    } else {
        renderReceivedGrid(files);
    }
}

function renderReceivedTable(files) {
    if (receivedDataTable) {
        receivedDataTable.destroy();
    }

    let html = '<table id="receivedTable" class="table table-striped table-bordered" style="width:100%">' +
        '<thead><tr>' +
        '<th>Document</th>' +
        '<th>Sender</th>' +
        '<th>Subject / Category</th>' +
        '<th>Scope</th>' +
        '<th>Size</th>' +
        '<th>Date</th>' +
        '<th style="width: 100px;">Actions</th>' +
        '</tr></thead><tbody>';

    files.forEach(file => {
        let senderHtml = '';
        if (file.uploaderType === 'admin') {
            senderHtml = '<span class="label label-primary" style="background: linear-gradient(135deg, #0077b6 0%, #00AEEF 100%);"><i class="fa fa-shield"></i> Administration</span>';
        } else if (file.uploaderType === 'student') {
            let className = file.studentClass ? ` <small class="text-muted">(${escapeHtml(file.studentClass)})</small>` : '';
            senderHtml = `<span class="label label-info" style="background-color: #0284c7;"><i class="fa fa-graduation-cap"></i> Student</span><br><strong>${escapeHtml(file.senderName)}</strong>${className}`;
        } else {
            senderHtml = `<strong>${escapeHtml(file.senderName)}</strong>`;
        }

        let scopeHtml = '<span class="label label-default">Staff Broadcast</span>';
        if (file.uploaderType === 'student') {
            scopeHtml = '<span class="label label-warning" style="background-color: #f59e0b;"><i class="fa fa-file-text-o"></i> Submission</span>';
        } else if (file.uploadedFor === 'subject_teachers') {
            scopeHtml = '<span class="label label-info">Department</span>';
        } else if (file.uploadedFor === 'specific_teachers') {
            scopeHtml = '<span class="label label-success">Direct to You</span>';
        }

        html += '<tr>';
        html += `<td><strong>${escapeHtml(file.title)}</strong><br><small class="text-muted">${escapeHtml(file.originalName)}</small></td>`;
        html += `<td>${senderHtml}</td>`;
        html += `<td>${escapeHtml(file.subjectName || 'General')} <br><small class="text-muted">${escapeHtml(file.categoryName || '')}</small></td>`;
        html += `<td>${scopeHtml}</td>`;
        html += `<td>${formatFileSize(file.fileSize)}</td>`;
        html += `<td>${formatDate(file.created_at)}</td>`;
        html += `<td>
            <button class="btn btn-default btn-action-sm" onclick="previewFile(${file.fileId})" title="Preview"><i class="fa fa-eye"></i></button>
            <a href="../../download_file.php?fileId=${file.fileId}" class="btn btn-primary btn-action-sm" title="Download"><i class="fa fa-download"></i></a>
            <button class="btn btn-default btn-action-sm" onclick="copyFileLink('${escapeHtml(file.filePath)}', '${escapeHtml(file.title)}')" title="Copy Link"><i class="fa fa-link"></i></button>
        </td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';
    $('#receivedFilesContainer').html(html);

    receivedDataTable = $('#receivedTable').DataTable({
        "pageLength": 10,
        "order": [[5, "desc"]]
    });
}

function renderReceivedGrid(files) {
    if (receivedDataTable) {
        receivedDataTable.destroy();
        receivedDataTable = null;
    }

    let html = '<div class="row">';
    files.forEach(file => {
        let senderBadge = '';
        if (file.uploaderType === 'admin') {
            senderBadge = '<span class="label label-primary"><i class="fa fa-shield"></i> Administration</span>';
        } else if (file.uploaderType === 'student') {
            let className = file.studentClass ? ` (${escapeHtml(file.studentClass)})` : '';
            senderBadge = `<span class="label label-warning" style="background-color: #f59e0b;"><i class="fa fa-graduation-cap"></i> Student: ${escapeHtml(file.senderName)}${className}</span>`;
        } else {
            senderBadge = `<span class="label label-default">${escapeHtml(file.senderName)}</span>`;
        }

        html += '<div class="col-md-6" style="margin-bottom: 15px;">';
        html += '<div class="file-card-box">';
        html += '<div style="display: flex; align-items: flex-start; margin-bottom: 10px;">';
        html += `<i class="${getFileIconClass(file.originalName)} file-card-icon"></i>`;
        html += '<div style="flex: 1; min-width: 0;">';
        html += `<h5 style="font-weight: 700; margin: 0 0 4px 0; color: #1e293b; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${escapeHtml(file.title)}</h5>`;
        html += `<p style="margin: 0; font-size: 12px; color: #64748b;">${escapeHtml(file.originalName)}</p>`;
        html += '</div>';
        html += '</div>';
        html += '<div style="font-size: 12px; color: #475569; margin-bottom: 12px;">';
        html += `<strong>From:</strong> ${senderBadge}<br>`;
        html += `<strong>Subject:</strong> ${escapeHtml(file.subjectName || 'General')} &bull; <strong>Size:</strong> ${formatFileSize(file.fileSize)}<br>`;
        html += `<strong>Date:</strong> ${formatDate(file.created_at)}`;
        html += '</div>';
        html += '<div style="display: flex; gap: 6px;">';
        html += `<button class="btn btn-default btn-sm" onclick="previewFile(${file.fileId})" style="flex: 1;"><i class="fa fa-eye"></i> Preview</button>`;
        html += `<a href="../../download_file.php?fileId=${file.fileId}" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;"><i class="fa fa-download"></i> Download</a>`;
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';
    $('#receivedFilesContainer').html(html);
}

// Render Sent Files
function renderSentFiles(files) {
    if (files.length === 0) {
        $('#sentFilesContainer').html('<div class="alert alert-info" style="text-align: center; margin-top: 15px;">You have not shared any files under this filter.</div>');
        return;
    }

    if (currentView === 'table') {
        renderSentTable(files);
    } else {
        renderSentGrid(files);
    }
}

function renderSentTable(files) {
    if (sentDataTable) {
        sentDataTable.destroy();
    }

    let html = '<table id="sentTable" class="table table-striped table-bordered" style="width:100%">' +
        '<thead><tr>' +
        '<th>Document</th>' +
        '<th>Shared With</th>' +
        '<th>Subject / Category</th>' +
        '<th>Status / Visibility</th>' +
        '<th>Size</th>' +
        '<th>Downloads</th>' +
        '<th style="width: 110px;">Actions</th>' +
        '</tr></thead><tbody>';

    files.forEach(file => {
        let aud = 'All Students';
        if (file.uploadedFor === 'admin') {
            aud = '<span class="label label-warning" style="background-color: #d97706;"><i class="fa fa-shield"></i> Administration</span>';
        } else if (file.uploadedFor === 'all_teachers') {
            aud = '<span class="label label-info">All Teachers</span>';
        } else if (file.uploadedFor === 'subject_teachers') {
            aud = '<span class="label label-primary">Department</span>';
        } else if (file.uploadedFor === 'specific_teachers') {
            aud = `<span class="label label-info">${file.recipientStaffCount || 0} Colleagues</span>`;
        } else if (file.uploadedFor === 'specific_class') {
            aud = `<span class="label label-default">${escapeHtml(file.targetClassName || 'Class')}</span>`;
        }

        let statusHtml = '';
        if (file.uploadedFor === 'admin') {
            if (file.adminStatus === 'reviewed') {
                statusHtml = `<span class="badge" style="background-color: #0284c7; cursor: pointer;" onclick="viewAdminFeedback(${file.fileId})" title="Click to view feedback"><i class="fa fa-check"></i> Reviewed</span>`;
            } else if (file.adminStatus === 'acknowledged') {
                statusHtml = `<span class="badge" style="background-color: #10b981; cursor: pointer;" onclick="viewAdminFeedback(${file.fileId})"><i class="fa fa-check-circle"></i> Acknowledged</span>`;
            } else {
                statusHtml = '<span class="badge" style="background-color: #f59e0b;"><i class="fa fa-clock-o"></i> Pending Review</span>';
            }
        } else if (inArray(file.uploadedFor, ['all', 'specific_class', 'multiple_classes', 'specific_students'])) {
            if (file.isPublishedToStudents == 1) {
                statusHtml = `<span class="badge" onclick="toggleVisibility(${file.fileId})" style="cursor: pointer; background-color: #2ec4b6;" title="Click to Hide"><i class="fa fa-eye"></i> Visible</span>`;
            } else {
                statusHtml = `<span class="badge" onclick="toggleVisibility(${file.fileId})" style="cursor: pointer; background-color: #94a3b8;" title="Click to Publish"><i class="fa fa-eye-slash"></i> Hidden</span>`;
            }
        } else {
            statusHtml = '<span class="badge" style="background-color: #00AEEF;"><i class="fa fa-check"></i> Active</span>';
        }

        html += '<tr>';
        html += `<td><strong>${escapeHtml(file.title)}</strong><br><small class="text-muted">${escapeHtml(file.originalName)}</small></td>`;
        html += `<td>${aud}</td>`;
        html += `<td>${escapeHtml(file.subjectName || 'General')} <br><small class="text-muted">${escapeHtml(file.categoryName || '')}</small></td>`;
        html += `<td>${statusHtml}</td>`;
        html += `<td>${formatFileSize(file.fileSize)}</td>`;
        html += `<td><span class="badge" style="background: #0077b6;">${file.downloadCount || 0}</span></td>`;
        html += `<td>
            <button class="btn btn-default btn-action-sm" onclick="previewFile(${file.fileId})" title="Preview"><i class="fa fa-eye"></i></button>
            <a href="../../download_file.php?fileId=${file.fileId}" class="btn btn-primary btn-action-sm" title="Download"><i class="fa fa-download"></i></a>
            <button class="btn btn-danger btn-action-sm" onclick="deleteFile(${file.fileId})" title="Delete"><i class="fa fa-trash"></i></button>
        </td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';
    $('#sentFilesContainer').html(html);

    sentDataTable = $('#sentTable').DataTable({
        "pageLength": 10,
        "order": [[0, "asc"]]
    });
}

function renderSentGrid(files) {
    if (sentDataTable) {
        sentDataTable.destroy();
        sentDataTable = null;
    }

    let html = '<div class="row">';
    files.forEach(file => {
        let aud = file.uploadedFor === 'admin' ? '<span class="label label-warning">To: Admin</span>' : `<span class="label label-info">${file.uploadedFor}</span>`;

        html += '<div class="col-md-6" style="margin-bottom: 15px;">';
        html += '<div class="file-card-box">';
        html += '<div style="display: flex; align-items: flex-start; margin-bottom: 10px;">';
        html += `<i class="${getFileIconClass(file.originalName)} file-card-icon"></i>`;
        html += '<div style="flex: 1; min-width: 0;">';
        html += `<h5 style="font-weight: 700; margin: 0 0 4px 0; color: #1e293b; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${escapeHtml(file.title)}</h5>`;
        html += `<p style="margin: 0; font-size: 12px; color: #64748b;">${escapeHtml(file.originalName)}</p>`;
        html += '</div>';
        html += '</div>';
        html += '<div style="font-size: 12px; color: #475569; margin-bottom: 12px;">';
        html += `<strong>Target:</strong> ${aud} &bull; <strong>Downloads:</strong> ${file.downloadCount || 0}<br>`;
        html += `<strong>Subject:</strong> ${escapeHtml(file.subjectName || 'General')} &bull; <strong>Size:</strong> ${formatFileSize(file.fileSize)}<br>`;
        html += `<strong>Date:</strong> ${formatDate(file.created_at)}`;
        html += '</div>';
        html += '<div style="display: flex; gap: 6px;">';
        html += `<button class="btn btn-default btn-sm" onclick="previewFile(${file.fileId})" style="flex: 1;"><i class="fa fa-eye"></i> Preview</button>`;
        html += `<a href="../../download_file.php?fileId=${file.fileId}" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;"><i class="fa fa-download"></i> Download</a>`;
        html += `<button class="btn btn-danger btn-sm" onclick="deleteFile(${file.fileId})"><i class="fa fa-trash"></i></button>`;
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';
    $('#sentFilesContainer').html(html);
}

// Preview Modal
function previewFile(fileId) {
    $('#previewModalBody').html('<p class="text-center" style="padding: 40px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading preview...</p>');
    $('#previewModal').modal('show');
    $.ajax({
        url: 'get_file_preview.php',
        type: 'POST',
        data: { fileId: fileId },
        success: function(html) {
            $('#previewModalBody').html(html);
        },
        error: function() {
            $('#previewModalBody').html('<div class="alert alert-danger">Failed to load preview.</div>');
        }
    });
}

// View Admin Feedback
function viewAdminFeedback(fileId) {
    const file = sentFilesData.find(f => f.fileId == fileId);
    if (!file) return;

    $('#fbDocTitle').text(file.title);
    $('#fbDocMeta').text(`Subject: ${file.subjectName || 'General'} &bull; Submitted on ${formatDate(file.created_at)}`);

    let statusBadge = '<span class="label label-warning">Pending Review</span>';
    if (file.adminStatus === 'reviewed') {
        statusBadge = '<span class="label label-info"><i class="fa fa-check"></i> Reviewed & Feedback Provided</span>';
    } else if (file.adminStatus === 'acknowledged') {
        statusBadge = '<span class="label label-success"><i class="fa fa-check-circle"></i> Submission Acknowledged</span>';
    }
    $('#fbStatusBadge').html(statusBadge);

    const notes = file.adminNotes ? file.adminNotes : 'No written feedback was added by administration yet.';
    $('#fbNotesBox').text(notes);

    if (file.adminReviewedAt) {
        $('#fbReviewedDate').text(`Reviewed on ${formatDate(file.adminReviewedAt)}`);
    } else {
        $('#fbReviewedDate').text('');
    }

    $('#adminFeedbackModal').modal('show');
}

// Visibility toggle
function toggleVisibility(fileId) {
    showLoading('Updating visibility...');
    $.ajax({
        url: 'toggle_file_visibility.php',
        type: 'POST',
        data: { fileId: fileId },
        dataType: 'json',
        success: function(res) {
            hideLoading();
            if (res.success) {
                loadMyFiles();
            } else {
                showPremiumModal('Failed to update visibility: ' + res.message, 'Error', 'error');
            }
        },
        error: function() {
            hideLoading();
            showPremiumModal('Network error updating visibility.', 'Error', 'error');
        }
    });
}

// Delete file
function deleteFile(fileId) {
    showPremiumConfirm(
        'Are you sure you want to stop sharing and delete this document? Recipients will no longer have access to it.',
        'Delete Document',
        function() {
            showLoading('Deleting document...');
            $.ajax({
                url: 'delete_file.php',
                type: 'POST',
                data: { fileId: fileId },
                dataType: 'json',
                success: function(res) {
                    hideLoading();
                    if (res.success) {
                        showPremiumModal('Document deleted successfully!', 'Deleted', 'success');
                        loadMyFiles();
                    } else {
                        showPremiumModal('Delete failed: ' + res.message, 'Error', 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showPremiumModal('Network error during deletion.', 'Error', 'error');
                }
            });
        }
    );
}

function copyFileLink(filePath, title) {
    let fullUrl = filePath;
    if (!filePath.startsWith('http://') && !filePath.startsWith('https://')) {
        fullUrl = window.location.origin + '/' + filePath.replace(/^\/+/, '');
    }
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(fullUrl).then(() => {
            showPremiumModal(`Direct resource link for "<strong>${escapeHtml(title)}</strong>" copied to clipboard:<br><br><input type="text" class="form-control" value="${fullUrl}" readonly onclick="this.select()">`, 'Link Copied', 'success');
        }).catch(() => {
            promptCopy(fullUrl, title);
        });
    } else {
        promptCopy(fullUrl, title);
    }
}

function promptCopy(fullUrl, title) {
    showPremiumModal(`Direct resource link for "<strong>${escapeHtml(title)}</strong>":<br><br><input type="text" class="form-control" value="${fullUrl}" readonly onclick="this.select()"><p class="text-muted" style="margin-top:6px; font-size:12px;">Press Ctrl+C to copy.</p>`, 'Resource Link', 'info');
}

function switchView(view) {
    currentView = view;
    if (view === 'table') {
        $('#tableViewBtn').addClass('active');
        $('#gridViewBtn').removeClass('active');
    } else {
        $('#gridViewBtn').addClass('active');
        $('#tableViewBtn').removeClass('active');
    }
    renderReceivedFiles(receivedFilesData);
    renderSentFiles(sentFilesData);
}

function updateYouTubePreview(url) {
    if (!url) {
        $('#youtubePreviewBox').slideUp(150);
        return;
    }
    const regExp = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i;
    const match = url.match(regExp);
    if (match && match[1]) {
        const videoId = match[1];
        $('#ytThumbImg').attr('src', `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`);
        $('#ytVideoIdText').text(`Video ID: ${videoId}`);
        $('#youtubePreviewBox').stop(true, true).slideDown(200);
    } else {
        $('#youtubePreviewBox').slideUp(150);
    }
}

function showLoading(msg) {
    $('#progressText').text(msg || 'Loading...');
    $('#loadingOverlay').css('display', 'flex');
}

function hideLoading() {
    $('#loadingOverlay').hide();
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
