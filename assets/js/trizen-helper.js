
(function ($) {
    'use strict';
    var body = $('body');
    var dateRangePicker = $('input[name="daterange"]');
    var $dateRangePickerTwo = $('input.date-range');

    /*==== Daterangepicker =====*/
    if ($(dateRangePicker).length) {
        $(dateRangePicker).daterangepicker({
            opens: 'right',
        });
    }

    /*==== Daterangepicker =====*/
    if ($($dateRangePickerTwo).length) {
        $dateRangePickerTwo.daterangepicker({
            singleDatePicker: true,
            opens: 'right',
            minDate: new Date,
        });
    }

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



    /* Check IN/OUT */
    /*$('.check-in-out').daterangepicker({
        language: ts_params.locale || '',
        dateFormat: "mm/dd/yy",
        firstDay: 1,
        autoUpdateInput: true,
        singleDatePicker: true,
    });*/
    $('.form-date-search', body).each(function () {
        var parent = $(this),
            date_wrapper     = $('.date-wrapper', parent),
            check_in_input   = $('.check-in-input', parent),
            check_out_input  = $('.check-out-input', parent),
            check_in_out     = $('.check-in-out', parent),
            check_in_render  = $('.check-in-render', parent),
            check_out_render = $('.check-out-render', parent);
        var timepicker       = parent.data('timepicker');
        if (typeof timepicker == 'undefined' || timepicker == '') {
            timepicker = false;
        } else {
            timepicker = true;
        }
        var options = {
            singleDatePicker: false,
            sameDate: false,
            autoApply: true,
            disabledPast: true,
            dateFormat: 'DD/MM/YYYY',
            customClass: '',
            widthSingle: 500,
            minDate: new Date,
            onlyShowCurrentMonth: true,
            timePicker: timepicker,
            timePicker24Hour: (ts_params.time_format == '12h') ? false : true,
        };
        if (typeof locale_daterangepicker == 'object') {
            options.locale = locale_daterangepicker;
        }

        check_in_out.daterangepicker(options,
            function (start, end, label) {
                check_in_input.val(start.format(parent.data('format'))).trigger('change');
                $('#tp_hotel .form-date-search .check-in-input').val(start.format('YYYY-MM-DD')).trigger('change');
                check_in_render.html(start.format(parent.data('format'))).trigger('change');
                check_out_input.val(end.format(parent.data('format'))).trigger('change');
                $('#tp_hotel .form-date-search .check-out-input').val(end.format('YYYY-MM-DD')).trigger('change');
                check_out_render.html(end.format(parent.data('format'))).trigger('change');
                if (timepicker) {
                    check_in_input.val(start.format(parent.data('date-format'))).trigger('change');
                    $('.check-in-input-time', parent).val(start.format(parent.data('time-format'))).trigger('change');
                    check_out_input.val(end.format(parent.data('date-format'))).trigger('change');
                    $('.check-out-input-time', parent).val(end.format(parent.data('time-format'))).trigger('change');
                    $('.check-out-input-time', parent).val(end.format(parent.data('time-format'))).trigger('change');
                }
                check_in_out.trigger('daterangepicker_change', [start, end]);
                if (window.matchMedia('(max-width: 767px)').matches) {
                    $('label', parent).hide();
                    $('.render', parent).show();
                    $('.check-in-wrapper span', parent).show();
                }
            });
        date_wrapper.click(function (e) {
            check_in_out.trigger('click');
        });
    });

    /*$(".check-in-input-ts").daterangepicker({
            singleDatePicker: true,
            sameDate: false,
            autoApply: true,
            disabledPast: true,
            dateFormat: 'DD/MM/YYYY',
            customClass: '',
            widthSingle: 500,
            minDate: new Date,
            // onlyShowCurrentMonth: true,
        }
    )*/
    $(".check-out-input-ts, .check-in-input-ts").daterangepicker({
            singleDatePicker: true,
            sameDate: false,
            autoApply: true,
            disabledPast: true,
            dateFormat: 'DD/MM/YYYY',
            customClass: '',
            widthSingle: 500,
            minDate: new Date,
            // onlyShowCurrentMonth: true,
    })

    /* Searching Room Availability */
    $('.form-check-availability-hotel', body).submit(function (ev) {
        ev.preventDefault();
        var form = $(this),
            parent    = form.parent(),
            loader = $('.loader-wrapper', parent),
            message   = $('.message-wrapper', form);
        var has_fixed = form.closest('.fixed-on-mobile');
        if (has_fixed.hasClass('open')) {
            has_fixed.removeClass('open').hide();
        }
        var data = form.serializeArray();
        data.push({
            name: 'security',
            value: ts_params._s
        });
        message.html('');
        loader.show();
        $('.ts-list-rooms .loader-wrapper').show();
        $.post(ts_params.ajax_url, data, function (respon) {
            if (typeof respon == 'object') {
                if (respon.message) {
                    message.html(respon.message);
                }
                $('.ts-list-rooms .fetch').html(respon.html);
                $('html, body').animate({
                    scrollTop: $('.ts-list-rooms', body).offset().top - 150
                }, 500);
                $('[data-toggle="tooltip"]').tooltip();
            }
            $('.ts-list-rooms .loader-wrapper').hide();
            loader.hide();
        }, 'json');
    });


    var top_ajax_search = $('.ts-top-ajax-search');
    if (top_ajax_search.length) {
        top_ajax_search.typeahead({hint: !0, highlight: !0, minLength: 3, limit: 8}, {
            source: function (q, cb) {
                $('.ts-top-ajax-search').parent().addClass('loading');
                return $.ajax({
                    dataType: 'json',
                    type: 'get',
                    url: ts_params.ajax_url,
                    data: {
                        security: ts_params.ts_search_nonce,
                        action: 'ts_top_ajax_search',
                        s: q,
                        lang: top_ajax_search.data('lang')
                    },
                    cache: !0,
                    success: function (data) {
                        $('.ts-top-ajax-search').parent().removeClass('loading');
                        var result = [];
                        if (data.data) {
                            $.each(data.data, function (index, val) {
                                result.push({
                                    value: val.title,
                                    location_id: val.id,
                                    type_color: 'success',
                                    type: val.type,
                                    url: val.url
                                })
                            });
                            cb(result);
                            console.log(result)
                        }
                    },
                    error: function (e) {
                        $('.ts-top-ajax-search').parent().removeClass('loading')
                    }
                })
            },
            templates: {suggestion: Handlebars.compile('<p class="search-line-item"><label class="label label-{{type_color}}">{{type}}</label><strong> {{value}}</strong></p>')}
        });
        top_ajax_search.bind('typeahead:selected', function (obj, datum, name) {
            if (datum.url) {
                window.location.href = datum.url
            }
        })
    }


    $('.stars-list-select > li > .booking-item-rating-stars > li').each(function () {
        var list       = $(this).parent(),
            listItems  = list.children(),
            itemIndex  = $(this).index(),
            parentItem = list.parent();
        $(this).hover(function () {
            for (var i = 0; i < listItems.length; i++) {
                if (i <= itemIndex) {
                    $(listItems[i]).addClass('hovered');
                } else {
                    break;
                }
            }
            $(this).click(function () {
                for (var i = 0; i < listItems.length; i++) {
                    if (i <= itemIndex) {
                        $(listItems[i]).addClass('selected');
                    } else {
                        $(listItems[i]).removeClass('selected');
                    }
                }
                parentItem.children('.ts_review_stars').val(itemIndex + 1);

            });
        }, function () {
            listItems.removeClass('hovered');
        });
    });


    $('.review-form .review-items .rates .la').each(function () {
        var list = $(this).parent(),
            listItems  = list.children(),
            itemIndex  = $(this).index(),
            parentItem = list.parent();
        $(this).hover(function () {
            for (var i = 0; i < listItems.length; i++) {
                if (i <= itemIndex) {
                    $(listItems[i]).addClass('hovered');
                } else {
                    break;
                }
            }
            $(this).click(function () {
                for (var i = 0; i < listItems.length; i++) {
                    if (i <= itemIndex) {
                        $(listItems[i]).addClass('selected');
                    } else {
                        $(listItems[i]).removeClass('selected');
                    }
                }
                ;
                parentItem.children('.ts_review_stars').val(itemIndex + 1);
            });
        }, function () {
            listItems.removeClass('hovered');
        });
    });


    // Hotel Room Star
    $('.review-form .ts-stars i').each(function () {
        var list = $(this).parent(),
                listItems = list.children(),
                itemIndex = $(this).index(),
                parentItem = list.parent();
        $(this).hover(function () {
            for (var i = 0; i < listItems.length; i++) {
                if (i <= itemIndex) {
                    $(listItems[i]).addClass('hovered');
                } else {
                    break;
                }
            }
            $(this).click(function () {
                for (var i = 0; i < listItems.length; i++) {
                    if (i <= itemIndex) {
                        $(listItems[i]).addClass('selected');
                    } else {
                        $(listItems[i]).removeClass('selected');
                    }
                }
                parentItem.children('.ts_review_stars').val(itemIndex + 1);
            });
        }, function () {
            listItems.removeClass('hovered');
        });
    });

})(jQuery);
