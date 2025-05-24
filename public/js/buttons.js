(function ($) {

    function doAjaxPost(target) {
        const targetURL = target.getAttribute('data-target-url');
        const successMessage = target.getAttribute('data-message-success');
        const errorMessage = target.getAttribute('data-message-error');

        return $.ajax({
            method: 'POST',
            url: targetURL
        })
            .done(function () {
                deactivateButtonIfExecuteOnce(target, successMessage || 'L\'action est effectuée');
            })
            .fail(function (data) {
                console.error(`Failed action at '${targetURL}'.`, JSON.parse(data.responseText));
                deactivateButtonIfExecuteOnce(target, errorMessage|| 'Une erreur est intervenue');
            });
    }

    function deactivateButtonIfExecuteOnce(target, message) {
        if (target.className.indexOf('js-lib-btn--execute-once') > -1) {
            $(target)
                .prop('disabled', true)
                .text(message);
        }
    }

    $(document).on('click', '.js-lib-btn--requires-confirmation', function (event) {
        const button = this;

        const message = button.getAttribute('data-message-confirm') || 'Attention, opération dangereuse. Êtes vous sûr de vouloir procéder ?';
        if (!confirm(message)) {
            return; // noop, because no confirmation
        }

        const targetURL = this.getAttribute('data-target-url');
        if (!targetURL) {
            return; // noop, because no action specified on button
        }

        doAjaxPost(button)
            .then(function () {
                // reload page if specified
                if(button.className.indexOf('js-lib-btn--refresh') > -1) {
                    location.reload();
                }
            });

    });

})(jQuery);
