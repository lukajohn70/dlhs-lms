/**
 * custom.js — DLHS Student Panel
 * Lightweight shim so legacy script references don't 404.
 */
(function ($) {
    'use strict';
    $(document).ready(function () {
        if ($.fn.tooltip) { $('[data-toggle="tooltip"], .tooltips').tooltip(); }
        if ($.fn.popover)  { $('[data-toggle="popover"], .popovers').popover(); }
        if ($.fn.tab) {
            $('[data-toggle="tab"]').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
        }
    });
}(jQuery));
