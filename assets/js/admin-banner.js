/**
 * TMW Admin Banner - Focal point editor
 */
(function($) {
    'use strict';
    if (typeof $ === 'undefined') return;

    $(document).ready(function() {
        var $slider = $('#tmw_banner_focal_y');
        var $preview = $('#tmw_banner_preview_img');
        var $value = $('#tmw_focal_value');

        if (!$slider.length) return;

        $slider.on('input change', function() {
            var val = $(this).val();
            $value.text(val + '%');
            if ($preview.length) {
                $preview.css('object-position', '50% ' + val + '%');
            }
        });
    });
})(jQuery);
