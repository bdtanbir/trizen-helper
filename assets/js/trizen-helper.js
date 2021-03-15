
(function ($) {
    // single hotel room booking ajax
    $('form.hotel-room-booking-form').on('click', 'button.btn-book-ajax', function (e) {
        e.preventDefault();
        var form = $('form.hotel-room-booking-form');
        var data = $('form.hotel-room-booking-form').serializeArray();
        var loadingSubmit = form.find('button[name=submit]');
        $(loadingSubmit).find("i.fa-spin").removeClass("d-none");
        data.push({
            name: 'security',
            value: ts_params._s
        });
        $('.message-wrapper').html("");
        $.ajax({
            url: ts_params.ajax_url,
            method: "post",
            dataType: 'json',
            data: data,
            beforeSend: function () {
                $('.message-wrapper').html("");
            },
            success: function (res) {
                $(loadingSubmit).find('i.fa-spin').addClass("d-none");
                if (res) {
                    if (res.status) {
                        if (res.redirect) {
                            window.location = res.redirect;
                        }
                    } else {
                        if (res.message) {
                            $('.message-wrapper').html(res.message);
                        }
                    }
                }
            },
            error: function (err) {
                $('.message-wrapper').html("");
                $(loadingSubmit).find('i.fa-spin').addClass("d-none");
            }
        });
    });


})(jQuery);