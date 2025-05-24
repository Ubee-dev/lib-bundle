(function ($) {
    'use strict';
    $(document).ready(function () {
        $('.js-admin__col_narrow').closest('td').addClass('admin__col_narrow');

        $('.js-delete-btn').each(function () {
            if ($(this).siblings().length === 0) {
                $(this).closest('td').addClass('admin__col_narrow');
            }
        });
    });
})(jQuery);