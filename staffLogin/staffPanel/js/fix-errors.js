// Fix for wysiwyg and form component issues
$(document).ready(function() {
    // Check if CKEDITOR is loaded
    if (typeof CKEDITOR !== 'undefined') {
        console.log('CKEditor loaded successfully');
    } else {
        console.error('CKEditor not loaded');
    }
    
    // Fix for colorpicker error - load before form-component.js
    if (typeof $.fn.colorpicker === 'undefined') {
        $.fn.colorpicker = function(options) {
            console.warn('Colorpicker not available, skipping');
            return this;
        };
    }
    
    // Fix for wysiwyg error
    if (typeof $.fn.wysiwyg === 'undefined') {
        $.fn.wysiwyg = function() {
            console.warn('WYSIWYG not available, skipping');
            return this;
        };
    }
    
    // Fix preventDefault error
    $(document).on('click', 'a[href="#"]', function(e) {
        e.preventDefault();
    });
    
    // Override form-component initialization to avoid errors
    window.formComponentInit = function() {
        try {
            // Skip colorpicker initialization
            if (typeof $.fn.colorpicker !== 'undefined') {
                $('.colorpicker').each(function() {
                    try {
                        $(this).colorpicker();
                    } catch(e) {
                        console.warn('Colorpicker init failed:', e);
                    }
                });
            }
        } catch(e) {
            console.warn('Form component init skipped due to missing dependencies');
        }
    };
});

// Fix for passive event listener warning
if (typeof jQuery !== 'undefined') {
    jQuery.event.special.touchstart = {
        setup: function( _, ns, handle ) {
            if (this.addEventListener) {
                this.addEventListener("touchstart", handle, { passive: !ns.includes("noPreventDefault") });
            }
        }
    };
    jQuery.event.special.touchmove = {
        setup: function( _, ns, handle ) {
            if (this.addEventListener) {
                this.addEventListener("touchmove", handle, { passive: !ns.includes("noPreventDefault") });
            }
        }
    };
}