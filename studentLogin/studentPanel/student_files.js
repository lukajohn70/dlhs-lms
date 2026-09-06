let allFilesData = [];

$(document).ready(function() {
    loadStudentFiles();
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
                allFilesData = response.files || [];
                displayFiles(allFilesData);
            } else {
                $('#filesGrid').html('<div class="col-md-12"><div class="alert alert-info" style="text-align: center;">No files shared with you yet. Check back later!</div></div>');
            }
        },
        error: function() {
            hideLoading();
            $('#filesGrid').html('<div class="col-md-12"><div class="alert alert-danger" style="text-align: center;">Failed to load shared files. Please refresh the page.</div></div>');
        }
    });
}

function displayFiles(files) {
    if (files.length === 0) {
        $('#filesGrid').html('<div class="col-md-12"><div class="text-muted" style="text-align: center; padding: 40px; font-size: 14px;">No matching shared files found.</div></div>');
        return;
    }
    
    let html = '';
    files.forEach(file => {
        const fileIcon = getFileIconClass(file.originalName);
        const fileSize = formatFileSize(file.fileSize);
        const shareDate = formatDate(file.created_at);
        const teacherName = file.teacherFirstName ? `${file.teacherFirstName} ${file.teacherSurname}` : 'Teacher';
        
        html += '<div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">';
        html += '<div class="file-card">';
        html += '<div>';
        html += `<span class="file-subject">${escapeHtml(file.subjectName || 'GENERAL')}</span>`;
        html += '<div class="file-top">';
        html += `<div class="file-icon-wrapper"><i class="${fileIcon}"></i></div>`;
        html += `<div><h4 class="file-title">${escapeHtml(file.title)}</h4>`;
        html += `<span class="text-muted" style="font-size: 11px;">By ${escapeHtml(teacherName)}</span></div>`;
        html += '</div>';
        
        if (file.description) {
            html += `<p class="file-desc">${escapeHtml(file.description)}</p>`;
        } else {
            html += '<p class="file-desc text-muted" style="font-style: italic;">No description provided.</p>';
        }
        html += '</div>';
        
        html += '<div>';
        html += '<div class="file-meta-row">';
        html += `<span><i class="fa fa-hdd-o"></i> ${fileSize}</span>`;
        html += `<span><i class="fa fa-calendar"></i> ${shareDate}</span>`;
        html += '</div>';
        
        html += `<a href="download_file.php?fileId=${file.fileId}" target="_blank" class="btn-download-premium" onclick="incrementLocalDownloadCount(this)">`;
        html += '<i class="fa fa-download"></i> Download File';
        html += '</a>';
        html += '</div>';
        
        html += '</div>'; // file-card
        html += '</div>'; // col
    });
    
    $('#filesGrid').html(html);
}

function incrementLocalDownloadCount(element) {
    // Quick success toast or UI indication that download started
    setTimeout(() => {
        // Simple reload to refresh download counts after click
        loadStudentFiles();
    }, 2000);
}

function getFileIconClass(filename) {
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
        'mkv': 'fa fa-file-video-o',
        'mp3': 'fa fa-file-audio-o',
        'wav': 'fa fa-file-audio-o',
        'txt': 'fa fa-file-text-o'
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

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}

function searchFiles() {
    const val = $('#searchFiles').val().toLowerCase();
    const filtered = allFilesData.filter(file => {
        const teacherName = file.teacherFirstName ? `${file.teacherFirstName} ${file.teacherSurname}` : '';
        return file.title.toLowerCase().includes(val) || 
               (file.description && file.description.toLowerCase().includes(val)) ||
               (file.subjectName && file.subjectName.toLowerCase().includes(val)) ||
               teacherName.toLowerCase().includes(val);
    });
    displayFiles(filtered);
}

function showLoading() {
    $('#loadingOverlay').addClass('active');
}

function hideLoading() {
    $('#loadingOverlay').removeClass('active');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
