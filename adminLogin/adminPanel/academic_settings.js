(function($){
    function initDataTable(selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().destroy();
        }
        return $(selector).DataTable({
            paging: true,
            searching: false,
            info: false,
            order: []
        });
    }

    let sessionsTable = null;
    let termsTable = null;
    let examTypesTable = null;

    function renderSessions(data) {
        if (!sessionsTable) {
            sessionsTable = initDataTable('#sessionsTable');
        }
        sessionsTable.clear();
        data.forEach(function(session, index){
            const isActive = parseInt(session.isActive, 10) === 1;
            const isCurrent = parseInt(session.isCurrentSession, 10) === 1;
            const statusBadge = isActive ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
            const currentCol = isCurrent
                ? '<span class="badge badge-default-flag">Current</span>'
                : '<button class="btn btn-link btn-xs set-current-session" data-id="'+session.sessionId+'">Set Current</button>';
            const toggleIcon = isActive ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>';

            const displayOrder = Number.isNaN(parseInt(session.displayOrder, 10)) ? 0 : parseInt(session.displayOrder, 10);

            sessionsTable.row.add([
                index + 1,
                escapeHtml(session.sessionName),
                escapeHtml(session.startDate),
                escapeHtml(session.endDate),
                statusBadge,
                currentCol,
                displayOrder,
                '<button class="btn btn-xs btn-info edit-session" data-session="'+encodeURIComponent(JSON.stringify(session))+'"><i class="fa fa-pencil"></i></button> '
                + '<button class="btn btn-xs btn-warning toggle-session" data-id="'+session.sessionId+'" data-active="'+(isActive ? 1 : 0)+'">'+toggleIcon+'</button>'
            ]);
        });
        sessionsTable.draw();
    }

    function renderTerms(data) {
        if (!termsTable) {
            termsTable = initDataTable('#termsTable');
        }
        termsTable.clear();
        data.forEach(function(term, index){
            const isActive = parseInt(term.isActive, 10) === 1;
            const isCurrent = parseInt(term.isDefault, 10) === 1;
            const statusBadge = isActive ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
            const currentCol = isCurrent ? '<span class="badge badge-default-flag">Current</span>' : '<button class="btn btn-link btn-xs set-current-term" data-id="'+term.termId+'">Set Current</button>';
            const toggleIcon = isActive ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>';

            const displayOrder = Number.isNaN(parseInt(term.displayOrder, 10)) ? 0 : parseInt(term.displayOrder, 10);

            termsTable.row.add([
                index + 1,
                escapeHtml(term.termName),
                statusBadge,
                currentCol,
                displayOrder,
                '<button class="btn btn-xs btn-info edit-term" data-term="'+encodeURIComponent(JSON.stringify(term))+'"><i class="fa fa-pencil"></i></button> '
                + '<button class="btn btn-xs btn-warning toggle-term" data-id="'+term.termId+'" data-active="'+(isActive ? 1 : 0)+'">'+toggleIcon+'</button>'
            ]);
        });
        termsTable.draw();
    }

    function renderExamTypes(data) {
        if (!examTypesTable) {
            examTypesTable = initDataTable('#examTypesTable');
        }
        examTypesTable.clear();
        data.forEach(function(exam, index){
            const isActive = parseInt(exam.isActive, 10) === 1;
            const isCurrent = parseInt(exam.isDefault, 10) === 1;
            const statusBadge = isActive ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
            const currentCol = isCurrent ? '<span class="badge badge-default-flag">Current</span>' : '<button class="btn btn-link btn-xs set-current-exam-type" data-id="'+exam.examTypeId+'">Set Current</button>';
            const toggleIcon = isActive ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>';

            const displayOrder = Number.isNaN(parseInt(exam.displayOrder, 10)) ? 0 : parseInt(exam.displayOrder, 10);

            examTypesTable.row.add([
                index + 1,
                escapeHtml(exam.examTypeName),
                statusBadge,
                currentCol,
                displayOrder,
                '<button class="btn btn-xs btn-info edit-exam-type" data-exam="'+encodeURIComponent(JSON.stringify(exam))+'"><i class="fa fa-pencil"></i></button> '
                + '<button class="btn btn-xs btn-warning toggle-exam-type" data-id="'+exam.examTypeId+'" data-active="'+(isActive ? 1 : 0)+'">'+toggleIcon+'</button>'
            ]);
        });
        examTypesTable.draw();
    }

    function escapeHtml(str) {
        return $('<div/>').text(str || '').html();
    }

    function loadSessions() {
        $.getJSON('get_academic_sessions.php', function(data){
            renderSessions(data);
        });
    }

    function loadTerms() {
        $.getJSON('get_academic_terms.php', function(data){
            renderTerms(data);
        });
    }

    function loadExamTypes() {
        $.getJSON('get_exam_types.php', function(data){
            renderExamTypes(data);
        });
    }

    function resetSessionForm() {
        $('#sessionForm')[0].reset();
        $('#sessionId').val('');
        $('#sessionModalTitle').text('Add Session');
    }

    function resetTermForm() {
        $('#termForm')[0].reset();
        $('#termId').val('');
        $('#termIsDefault').prop('checked', false);
        $('#termModalTitle').text('Add Term');
    }

    function resetExamTypeForm() {
        $('#examTypeForm')[0].reset();
        $('#examTypeId').val('');
        $('#examTypeIsDefault').prop('checked', false);
        $('#examTypeModalTitle').text('Add Exam Type');
    }

    function submitForm(form, payload) {
        const $btn = form.find('button[type="submit"]');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.post('manage_academic_settings.php', payload)
            .done(function(res){
                if (res.status === 'success') {
                    form.closest('.modal').modal('hide');
                    loadSessions();
                    loadTerms();
                    loadExamTypes();
                } else {
                    alert(res.message || 'Operation failed');
                }
            })
            .fail(function(){
                alert('Unable to complete the request. Please try again.');
            })
            .always(function(){
                $btn.prop('disabled', false).html(originalText);
            });
    }

    $(document).ready(function(){
        // Initial render from preloaded data
        renderSessions(window.__PRELOADED_SESSIONS__ || []);
        renderTerms(window.__PRELOADED_TERMS__ || []);
        renderExamTypes(window.__PRELOADED_EXAM_TYPES__ || []);

        // Refresh from server for latest data
        loadSessions();
        loadTerms();
        loadExamTypes();

        $('#sessionModal').on('show.bs.modal', resetSessionForm);
        $('#termModal').on('show.bs.modal', resetTermForm);
        $('#examTypeModal').on('show.bs.modal', resetExamTypeForm);

        $('#sessionForm').on('submit', function(e){
            e.preventDefault();
            const payload = {
                action: $('#sessionId').val() ? 'update_session' : 'create_session',
                sessionId: $('#sessionId').val(),
                sessionName: $('#sessionName').val(),
                startDate: $('#sessionStartDate').val(),
                endDate: $('#sessionEndDate').val(),
                displayOrder: $('#sessionDisplayOrder').val()
            };
            submitForm($('#sessionForm'), payload);
        });

        $('#termForm').on('submit', function(e){
            e.preventDefault();
            const payload = {
                action: $('#termId').val() ? 'update_term' : 'create_term',
                termId: $('#termId').val(),
                termName: $('#termName').val(),
                displayOrder: $('#termDisplayOrder').val(),
                isDefault: $('#termIsDefault').is(':checked') ? 1 : 0
            };
            submitForm($('#termForm'), payload);
        });

        $('#examTypeForm').on('submit', function(e){
            e.preventDefault();
            const payload = {
                action: $('#examTypeId').val() ? 'update_exam_type' : 'create_exam_type',
                examTypeId: $('#examTypeId').val(),
                examTypeName: $('#examTypeName').val(),
                displayOrder: $('#examTypeDisplayOrder').val(),
                isDefault: $('#examTypeIsDefault').is(':checked') ? 1 : 0
            };
            submitForm($('#examTypeForm'), payload);
        });

        $('#sessionsTable').on('click', '.edit-session', function(){
            const session = JSON.parse(decodeURIComponent($(this).attr('data-session')));
            $('#sessionModalTitle').text('Edit Session');
            $('#sessionId').val(session.sessionId);
            $('#sessionName').val(session.sessionName);
            $('#sessionStartDate').val(session.startDate);
            $('#sessionEndDate').val(session.endDate);
            $('#sessionDisplayOrder').val(session.displayOrder || 0);
            $('#sessionModal').modal('show');
        });

        $('#termsTable').on('click', '.edit-term', function(){
            const term = JSON.parse(decodeURIComponent($(this).attr('data-term')));
            $('#termModalTitle').text('Edit Term');
            $('#termId').val(term.termId);
            $('#termName').val(term.termName);
            $('#termDisplayOrder').val(term.displayOrder || 0);
            $('#termIsDefault').prop('checked', term.isDefault === 1);
            $('#termModal').modal('show');
        });

        $('#examTypesTable').on('click', '.edit-exam-type', function(){
            const exam = JSON.parse(decodeURIComponent($(this).attr('data-exam')));
            $('#examTypeModalTitle').text('Edit Exam Type');
            $('#examTypeId').val(exam.examTypeId);
            $('#examTypeName').val(exam.examTypeName);
            $('#examTypeDisplayOrder').val(exam.displayOrder || 0);
            $('#examTypeIsDefault').prop('checked', exam.isDefault === 1);
            $('#examTypeModal').modal('show');
        });

        $('#sessionsTable').on('click', '.toggle-session', function(){
            const id = $(this).data('id');
            const active = $(this).data('active');
            $.post('manage_academic_settings.php', {
                action: 'toggle_session',
                sessionId: id,
                isActive: active ? 0 : 1
            }).done(function(res){
                if (res.status === 'success') {
                    loadSessions();
                } else {
                    alert(res.message || 'Unable to update session');
                }
            });
        });

        $('#sessionsTable').on('click', '.set-current-session', function(){
            const id = $(this).data('id');
            $.post('manage_academic_settings.php', {
                action: 'set_current_session',
                sessionId: id
            }).done(function(res){
                if (res.status === 'success') {
                    loadSessions();
                } else {
                    alert(res.message || 'Unable to set current session');
                }
            });
        });

        $('#termsTable').on('click', '.toggle-term', function(){
            const id = $(this).data('id');
            const active = $(this).data('active');
            $.post('manage_academic_settings.php', {
                action: 'toggle_term',
                termId: id,
                isActive: active ? 0 : 1
            }).done(function(res){
                if (res.status === 'success') {
                    loadTerms();
                } else {
                    alert(res.message || 'Unable to update term');
                }
            });
        });

        $('#termsTable').on('click', '.set-current-term', function(){
            const id = $(this).data('id');
            $.post('manage_academic_settings.php', {
                action: 'set_current_term',
                termId: id
            }).done(function(res){
                if (res.status === 'success') {
                    loadTerms();
                } else {
                    alert(res.message || 'Unable to update current term');
                }
            });
        });

        $('#examTypesTable').on('click', '.toggle-exam-type', function(){
            const id = $(this).data('id');
            const active = $(this).data('active');
            $.post('manage_academic_settings.php', {
                action: 'toggle_exam_type',
                examTypeId: id,
                isActive: active ? 0 : 1
            }).done(function(res){
                if (res.status === 'success') {
                    loadExamTypes();
                } else {
                    alert(res.message || 'Unable to update exam type');
                }
            });
        });

        $('#examTypesTable').on('click', '.set-current-exam-type', function(){
            const id = $(this).data('id');
            $.post('manage_academic_settings.php', {
                action: 'set_current_exam_type',
                examTypeId: id
            }).done(function(res){
                if (res.status === 'success') {
                    loadExamTypes();
                } else {
                    alert(res.message || 'Unable to update current exam type');
                }
            });
        });
    });
})(jQuery);
