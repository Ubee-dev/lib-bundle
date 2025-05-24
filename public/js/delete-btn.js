$(document).ready(function () {
    'use strict';
    $('table').on('click', '.js-delete-btn', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var $this = $(this);
        $.ajax({
            url: $this.data('ajax-url'),
            type: 'post',
            dataType: 'json',
            data: {
                'class': $this.data('entity-class'),
                'id': $this.data('entity-id')
            },
            success: function (data) {
                if (data.status == 'OK') {
                    $this.closest('tr').remove();
                } else {
                    onError(data.message);
                }
            },
            error: function (xhr, status, error) {
                onError('Ajax request error.');
            },
            complete: function () {
                console.log('Requested deletion of ' + $this.data('entity-class') + ':' + ($this.data('entity-id') ? $this.data('entity-id') : '[no id]' ));
            }
        });

        function onError(message) {
            console.error('Error deleting entity via ajax:\n\t' + message);
        }
    });
});