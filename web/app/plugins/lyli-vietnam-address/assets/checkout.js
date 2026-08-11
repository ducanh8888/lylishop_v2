(function ($) {
    'use strict';

    var requestSequence = {billing: 0, shipping: 0};

    function refresh(scope) {
        var state = $('#' + scope + '_state').val();
        var city = $('#' + scope + '_city');
        if (!city.length || !state || typeof lyliVietnamAddress === 'undefined') {
            return;
        }
        var selected = city.val();
        var sequence = ++requestSequence[scope];
        $.getJSON(lyliVietnamAddress.endpoint, {
            nonce: lyliVietnamAddress.nonce,
            province: state
        }).done(function (response) {
            if (sequence !== requestSequence[scope]
                || state !== $('#' + scope + '_state').val()
                || !response
                || !response.success
                || !response.data.wards
            ) {
                return;
            }
            city.empty().append($('<option>', {value: '', text: lyliVietnamAddress.placeholder}));
            $.each(response.data.wards, function (code, name) {
                city.append($('<option>', {value: code, text: name, selected: code === selected}));
            });
            city.trigger('change.select2');
        });
    }

    $(document.body).on('change', '#billing_state, #shipping_state', function () {
        refresh(this.id.indexOf('shipping_') === 0 ? 'shipping' : 'billing');
    });

    $(function () {
        refresh('billing');
        refresh('shipping');
    });
})(jQuery);
