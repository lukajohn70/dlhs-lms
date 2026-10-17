$(document).ready(function() {
    var $pwd1 = $('#password1');
    var $pwd2 = $('#password2');
    var $submit = $('#submit');
    var $complianceItems = $('.compliance-item');

    function checkCompliance() {
        var p1 = $pwd1.val();
        var p2 = $pwd2.val();
        var rules = {
            length: p1.length >= 8,
            uppercase: /[A-Z]/.test(p1),
            lowercase: /[a-z]/.test(p1),
            number: /[0-9]/.test(p1),
            'no-default': !/(1234|4321|password|admin)/i.test(p1),
            'no-repeat': !/(.)\1{3,}/.test(p1),
            'no-sequence': true,
            match: p1 !== '' && p1 === p2
        };

        // Sequence check (ascending/descending)
        if (p1.length >= 4) {
            var lowerP1 = p1.toLowerCase();
            for (var i = 0; i <= lowerP1.length - 4; i++) {
                var chunk = lowerP1.substr(i, 4);
                if (!/^[a-z0-9]{4}$/.test(chunk)) continue;
                var asc = true, desc = true;
                for (var j = 1; j < 4; j++) {
                    if (chunk.charCodeAt(j) !== chunk.charCodeAt(j-1) + 1) asc = false;
                    if (chunk.charCodeAt(j) !== chunk.charCodeAt(j-1) - 1) desc = false;
                }
                if (asc || desc) {
                    rules['no-sequence'] = false;
                    break;
                }
            }
        }

        var allMet = true;
        $complianceItems.each(function() {
            var rule = $(this).data('rule');
            var isMet = rules[rule];
            $(this).toggleClass('met', isMet).toggleClass('not-met', !isMet && p1 !== '');
            if (!isMet) allMet = false;
        });

        // Strength Meter
        var metCount = Object.values(rules).filter(Boolean).length;
        var percentage = (metCount / Object.keys(rules).length) * 100;
        var color = percentage < 40 ? '#dc3545' : (percentage < 70 ? '#ffc107' : '#28a745');
        $('#strengthFill').css({ 'width': percentage + '%', 'background-color': color });

        $submit.prop('disabled', !allMet);
        return allMet;
    }

    $pwd1.on('input', checkCompliance);
    $pwd2.on('input', checkCompliance);

    $('#changePasswordForm').submit(function(e) {
        e.preventDefault();
        if (!checkCompliance()) return;

        $submit.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: "changePassword1.php",
            type: "POST",
            data: {
                password1: $pwd1.val(),
                password2: $pwd2.val()
            },
            success: function(response) {
                response = $.trim(response);
                if (response == "1") {
                    $('#statusMessage').html('<div class="alert alert-success"><i class="fa fa-check"></i> Password updated successfully. Redirecting...</div>');
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 2000);
                } else if (response == "0") {
                    window.location.replace("logout.php");
                } else {
                    var errorMsg = response.indexOf('3|') === 0 ? response.substring(2) : "Update failed. Please try again.";
                    $('#statusMessage').html('<div class="alert alert-danger"><i class="fa fa-times"></i> ' + errorMsg + '</div>');
                    $submit.prop('disabled', false).html('<i class="fa fa-save"></i> Update Password');
                }
            },
            error: function() {
                $('#statusMessage').html('<div class="alert alert-danger"><i class="fa fa-times"></i> Connection error.</div>');
                $submit.prop('disabled', false).html('<i class="fa fa-save"></i> Update Password');
            }
        });
    });
});
