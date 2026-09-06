function initializeJS() {
    function hasUnifiedSidebar() {
        return jQuery('body').hasClass('dlhs-has-unified-sidebar') || jQuery('.dlhs-unified-sidebar').length > 0;
    }

    //tool tips
    jQuery('.tooltips').tooltip();

    //popovers
    jQuery('.popovers').popover();

    // Use native scrolling so pages keep a single professional scrollbar.
    if (jQuery.fn.dataTable) {
        jQuery.extend(true, jQuery.fn.dataTable.defaults, {
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            pageLength: 10
        });
    }
    
    //sidebar dropdown menu
    jQuery('#sidebar .sub-menu > a').click(function () {
        var last = jQuery('.sub-menu.open', jQuery('#sidebar'));        
        jQuery('.menu-arrow').removeClass('arrow_carrot-right');
        jQuery('.sub', last).slideUp(200);
        var sub = jQuery(this).next();
        if (sub.is(":visible")) {
            jQuery('.menu-arrow').addClass('arrow_carrot-right');            
            sub.slideUp(200);
        } else {
            jQuery('.menu-arrow').addClass('arrow_carrot-down');            
            sub.slideDown(200);
        }
        var o = (jQuery(this).offset());
        diff = 200 - o.top;
        if(diff>0)
            jQuery("#sidebar").scrollTo("-="+Math.abs(diff),500);
        else
            jQuery("#sidebar").scrollTo("+="+Math.abs(diff),500);
    });

    // sidebar menu toggle
    jQuery(function() {
        function responsiveView() {
            if (hasUnifiedSidebar()) {
                jQuery('#container').removeClass('sidebar-close sidebar-closed');
                jQuery('#sidebar > ul').show();
                jQuery('#sidebar').css({
                    'margin-left': '0'
                });
                jQuery('#main-content').css({
                    'margin-left': ''
                });
                return;
            }

            var wSize = jQuery(window).width();
            if (wSize <= 768) {
                jQuery('#container').addClass('sidebar-close');
                jQuery('#sidebar > ul').hide();
            }

            if (wSize > 768) {
                jQuery('#container').removeClass('sidebar-close');
                jQuery('#sidebar > ul').show();
            }
        }
        jQuery(window).on('load', responsiveView);
        jQuery(window).on('resize', responsiveView);
    });

    jQuery('.toggle-nav').click(function () {
        if (hasUnifiedSidebar()) {
            if (typeof window.dlhsToggleSidebar === 'function') {
                window.dlhsToggleSidebar();
            }
            return false;
        }

        var container = jQuery("#container");
        if (container.hasClass("sidebar-closed")) {
            container.removeClass("sidebar-closed");
            if (jQuery(window).width() > 768) {
                jQuery('#main-content').css('margin-left', '180px');
            }
            jQuery('#sidebar > ul').show();
        } else {
            container.addClass("sidebar-closed");
            jQuery('#main-content').css('margin-left', '0px');
            jQuery('#sidebar > ul').hide();
        }
    });

    //bar chart
    if (jQuery(".custom-custom-bar-chart")) {
        jQuery(".bar").each(function () {
            var i = jQuery(this).find(".value").html();
            jQuery(this).find(".value").html("");
            jQuery(this).find(".value").animate({
                height: i
            }, 2000)
        })
    }

}

jQuery(document).ready(function(){
    initializeJS();
});
