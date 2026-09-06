/**
 * custom.js — DLHS Staff Panel
 * Initialises any components that depend on page-specific markup.
 * Kept as a lightweight shim so legacy script references don't 404.
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Tooltips
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"], .tooltips').tooltip();
        }

        // Popovers
        if ($.fn.popover) {
            $('[data-toggle="popover"], .popovers').popover();
        }

        // Bootstrap tabs (needed for smartboard_remote.php)
        if ($.fn.tab) {
            $('[data-toggle="tab"]').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
        }
    });

}(jQuery));
