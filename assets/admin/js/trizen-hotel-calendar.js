
/*jQuery(function ($) {


    var HotelCalendar = function (container) {
        var self = this;
        this.container = container;
        this.calendar = null;
        this.form_container = null;
        this.fullCalendar;
        this.timeOut;
        this.fullCalendarOptions = {
            initialView: 'dayGridMonth',
            firstDay: 1,
            locale: ts_params.locale_fullcalendar,
            timeZone: ts_timezone.timezone_string,
            customButtons: {
                reloadButton: {
                    text: ts_params.text_refresh,
                    click: function() {
                        self.fullCalendar.refetchEvents();
                    }
                }
            },
            headerToolbar: {
                start: 'today,reloadButton',
                center: 'title',
                end: 'prev,next'
            },
            displayEventTime: true,
            selectable: true,
            select: function ({
                                  start,
                                  end,
                                  startStr,
                                  endStr,
                                  allDay,
                                  jsEvent,
                                  view,
                                  resource
                              }) {
                /!* Info{start, end, startStr, endStr, allDay, jsEvent, view, resource } *!/
                if(moment(start).isBefore(moment(), 'day') ||
                    moment(end).isBefore(moment(), 'day')
                ) {
                    self.fullCalendar.unselect();
                    setCheckInOut("", "", self.form_container)
                } else {
                    var zone = moment(start).format("Z");
                    zone = zone.split(":");
                    zone = "" + parseInt(zone[0]) + ":00";
                    var check_in = moment(start).utcOffset(zone).format(String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase());
                    var check_out = moment(end).utcOffset(zone).subtract(1, 'day').format(String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase());
                    setCheckInOut(check_in, check_out, self.form_container);
                }
            },
            events: function ({
                                  start,
                                  end,
                                  startStr,
                                  endStr,
                                  timeZone
                              }, successCallback, failureCallback) {
                $.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    type: "post",
                    data: {
                        action: "ts_get_availability_hotel",
                        post_id: $(self.container).data("post-id"),
                        start: moment(start).unix(),
                        end: moment(end).unix()
                    },
                    success: function (doc) {
                        if(typeof doc == "object") {
                            successCallback(doc);
                        }
                    },
                    error: function (e) {
                        alert("Can not get the availability slot. Lost connect with your server");
                    }
                });
            },
            eventContent: function (arg) {
                /!** arg{event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view} *!/
                let italicEl = document.createElement('i');
                let contentEl = document.createElement('div');
                let priceEl = document.createElement('div');
                let startTimeEl = document.createElement('div');
                contentEl.classList.add('fc-content');
                priceEl.classList.add('price');
                startTimeEl.classList.add('starttime');

                if(arg.event.extendedProps.status) {
                    // available, unavailable
                    let status = arg.event.extendedProps.status;
                    if (status === 'unavailable') {
                        contentEl.classList.remove('available');
                        contentEl.classList.add('unavailable');
                        contentEl.innerHTML = '<div class="not_available">'+ts_params.text_unavailable+'</div>';
                    } else {
                        contentEl.classList.remove('unavailable');
                        contentEl.classList.add('available');

                        let price_by_per_person = $('.calendar-content', self.container).data('price-by-per-person') == 'on' ? true : false || false;
                        if ( price_by_per_person ) {
                            if (typeof arg.event.extendedProps.adult_price != 'undefined') {
                                let adultPriceEl = document.createElement('div');
                                adultPriceEl.classList.add('price');
                                adultPriceEl.innerHTML = ts_params.text_adult + arg.event.extendedProps.adult_price;

                                contentEl.appendChild(adultPriceEl);
                            }
                            if (typeof arg.event.extendedProps.child_price != 'undefined') {
                                let childPriceEl = document.createElement('div');
                                childPriceEl.classList.add('price');
                                childPriceEl.innerHTML = ts_params.text_child + arg.event.extendedProps.child_price;

                                contentEl.appendChild(childPriceEl);
                            }
                        } else {
                            if (typeof arg.event.extendedProps.price != 'undefined') {
                                let basePriceEl = document.createElement('div');
                                basePriceEl.classList.add('price');
                                basePriceEl.innerHTML = ts_params.text_price + arg.event.extendedProps.price;

                                contentEl.appendChild(basePriceEl)
                            }
                        }
                    }
                }

                let arrayOfDomNodes = [contentEl]
                return {
                    domeNodes: arrayOfDomNodes
                }
            },
            viewDidMount: function (arg) {
                if(arg.el) {
                    let el = arg.el;
                    if (self.timeOut) { clearTimeout(self.timeOut); }
                    self.timeOut = setTimeout(function () {
                        let viewHardnessEl = $(el).closest('.fc-view-harness.fc-view-harness-active');
                        if (viewHardnessEl && viewHardnessEl.outerHeight() == 0) {
                            viewHardnessEl.css({minHeight: '250px'});
                        }
                    }, 400);
                }
            },
            eventClick: function ({
                                      event,
                                      el,
                                      jsEvent,
                                      view
                                  }) {
                let startTime = moment(event.start, String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase())
                    .format(String(ts_params.dateformat || 'MM/DD/YYYY').toUpperCase());
                let endTime;
                if (event.end) {
                    endTime = moment(event.end, String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase())
                        .format(String(ts_params.dateformat || 'MM/DD/YYYY').toUpperCase());
                } else {
                    endTime = startTime
                }
                setCheckInOut(
                    startTime,
                    endTime,
                    self.form_container
                );
                let price_by_per_person = $('.calendar-content', self.container).data('price-by-per-person') == 'on' ? true : false || false;
                if ( price_by_per_person ) {
                    if (typeof event.extendedProps.adult_price != 'undefined') {
                        $("#calendar_adult_price", self.form_container).val(event.extendedProps.adult_price);
                    }
                    if (typeof event.extendedProps.child_price != 'undefined') {
                        $("#calendar_child_price", self.form_container).val(event.extendedProps.child_price);
                    }
                } else {
                    if (typeof event.extendedProps.price != 'undefined') {
                        $("#calendar_price", self.form_container).val(event.extendedProps.price);
                    }
                }
                if(event.extendedProps.status) {
                    $(
                        "#calendar_status option[value=" + event.extendedProps.status + "]",
                        self.form_container
                    ).prop("selected", true);
                }
            },
            loading: function (isLoading) {
                if (isLoading) {
                    $(".overlay", self.container).addClass("open");
                } else {
                    $(".overlay", self.container).removeClass('open');
                }
            },
        };
        this.init = function () {
            self.container = jQuery(container);
            self.calendar = container.querySelector('.calendar-content');
            self.form_container = $('.calendar-form', self.container);
            setCheckInOut('', '', self.form_container);
            self.initCalendar();
        }
        this.initCalendar = function () {
            if (typeof FullCalendar != 'undefined') {
                self.fullCalendar = new FullCalendar.Calendar(self.calendar, self.fullCalendarOptions);
                self.fullCalendar.render();
            }
        }
    };
    function setCheckInOut(check_in, check_out, form_container) {
        $('#calendar_check_in', form_container).val(check_in);
        $('#calendar_check_out', form_container).val(check_out);
    }
    function resetForm(form_container) {
        $('#calendar_check_in', form_container).val('');
        $('#calendar_check_out', form_container).val('');
        $('#calendar_price', form_container).val('');
        $('#calendar_priority', form_container).val('');
        $('#calendar_number', form_container).val('');
        $('#calendar_adult_price', form_container).val('');
        $('#calendar_child_price', form_container).val('');
    }
    $(function () {
        $('.calendar-wrapper').each(function (index, el) {
            var t = $(this);
            var hotel = new HotelCalendar(el);
            var flag_submit = false;
            $('#calendar_submit', t).on('click', function (event) {
                var data = $('input, select', '.calendar-form').serializeArray();
                data.push({
                    name: 'action',
                    value: 'ts_add_custom_price'
                });
                $('.form-message', t).attr('class', 'form-message').find('p').html('');
                $('.overlay', self.container).addClass('open');
                if(flag_submit) return false; flag_submit = true;
                $.post(ajaxurl, data, function (respon, textStatus, xhr) {
                    if (typeof respon == 'object') {
                        if(respon.status == 1) {
                            resetForm(t);
                            if(hotel.fullCalendar) {
                                hotel.fullCalendar.refetchEvents();
                            }
                        } else {
                            $('.form-message', t).addClass(respon.type).find('p').html(respon.message);
                            $('.overlay', self.container).removeClass('open');
                        }
                    } else {
                        $('.overlay', self.container).removeClass('open');
                    }
                    flag_submit = false;
                }, 'json');
                return false;
            });
            $(document).on('click', '.tabs .tab-link[href="tab-room-availability"]', function () {
                hotel.init();
            });
            $('body').on('calendar.change_month', function (event, value) {
                if (hotel.fullCalendar) {
                    var date =  hotel.fullCalendar.getData();
                    var month = date.format('M');
                    date = date.add(value-month, 'M');
                    hotel.fullCalendar.gotoDate(date.format('YYYY-MM-DD'))
                }
            });
        });
    });
});*/



jQuery(function($){
    var dateRangePickerTwo = $('input.date-range');
    if ($(dateRangePickerTwo).length) {
        /*var options = {
            timePicker: true,
            autoUpdateInput: false,
            autoApply: true,
            disabledPast: true,
            dateFormat: dateFormat,
            timeFormat: timeFormat,
            widthSingle: 500,
            onlyShowCurrentMonth: true,
            minimumCheckin: minimum,
            classNotAvailable: ['disabled', 'off'],
            enableLoading: true,
            todayHighlight: 1,
            opens: 'left',
            timePicker24Hour: (ts_params.time_format == '12h') ? false : true,
        };*/
        $(dateRangePickerTwo).daterangepicker({
            language: ts_params.locale || '',
            dateFormat: ts_params.dateformat_convert || "mm/dd/yy",
            firstDay: 1,
            autoUpdateInput: true,
            singleDatePicker: true,
        });
    }

    var HotelCalendar = function (container) {
        var self = this;
        this.container = container;
        this.calendar = null;
        this.form_container = null;
        this.fullCalendar;
        this.timeOut;
        this.fullCalendarOptions = {
            initialView: 'dayGridMonth',
            firstDay: 1,
            locale: ts_params.locale_fullcalendar,
            timeZone: ts_timezone.timezone_string,
            customButtons: {
                reloadButton: {
                    text: ts_params.text_refresh,
                    click: function() {
                        self.fullCalendar.refetchEvents();
                    }
                }
            },
            headerToolbar: {
                start: 'today,reloadButton',
                center: 'title',
                end: 'prev,next'
            },
            displayEventTime: true,
            selectable: true,
            select: function ({
                                  start,
                                  end,
                                  startStr,
                                  endStr,
                                  allDay,
                                  jsEvent,
                                  view,
                                  resource
                              }) {
                /* Info{start, end, startStr, endStr, allDay, jsEvent, view, resource } */
                if(moment(start).isBefore(moment(), 'day') ||
                    moment(end).isBefore(moment(), 'day')
                ) {
                    self.fullCalendar.unselect();
                    setCheckInOut("", "", self.form_container)
                } else {
                    var zone = moment(start).format("Z");
                    zone          = zone.split(":");
                    zone          = "" + parseInt(zone[0]) + ":00";
                    var check_in  = moment(start).utcOffset(zone).format(String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase());
                    var check_out = moment(end).utcOffset(zone).subtract(1, 'day').format(String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase());
                    setCheckInOut(check_in, check_out, self.form_container);
                }
            },
            events: function ({
                start,
                end,
                startStr,
                endStr,
                timeZone
            }, successCallback, failureCallback) {
                $.ajax({
                    url: ajaxurl,
                    dataType: "json",
                    type: "post",
                    data: {
                        action: "ts_get_availability_hotel",
                        post_id: $(self.container).data("post-id"),
                        start: moment(start).unix(),
                        end: moment(end).unix()
                    },
                    success: function (doc) {
                        if(typeof doc == "object") {
                            successCallback(doc);
                        }
                    },
                    error: function (e) {
                        alert("Can not get the availability slot. Lost connect with your server");
                    }
                });
            },
            eventContent: function (arg) {
                /** arg{event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view} */
                let italicEl    = document.createElement('i');
                let contentEl   = document.createElement('div');
                let priceEl     = document.createElement('div');
                let startTimeEl = document.createElement('div');
                contentEl.classList.add('fc-content');
                priceEl.classList.add('price');
                startTimeEl.classList.add('starttime');

                if (arg.event.extendedProps.status) {
                    // available, unavailable
                    let status = arg.event.extendedProps.status;
                    if (status === 'unavailable') {
                        contentEl.classList.remove('available');
                        contentEl.classList.add('unavailable');
                        contentEl.innerHTML = '<div class="not_available">'+ts_params.text_unavailable+'</div>';
                    } else {
                        contentEl.classList.remove('unavailable');
                        contentEl.classList.add('available');

                        let price_by_per_person = $('.calendar-content', self.container).data('price-by-per-person') == 'on' ? true : false || false;
                        if ( price_by_per_person ) {
                            if (typeof arg.event.extendedProps.adult_price != 'undefined') {
                                let adultPriceEl = document.createElement('div');
                                adultPriceEl.classList.add('price');
                                adultPriceEl.innerHTML = ts_params.text_adult + arg.event.extendedProps.adult_price;

                                contentEl.appendChild(adultPriceEl);
                            }
                            if (typeof arg.event.extendedProps.child_price != 'undefined') {
                                let childPriceEl = document.createElement('div');
                                childPriceEl.classList.add('price');
                                childPriceEl.innerHTML = ts_params.text_child + arg.event.extendedProps.child_price;

                                contentEl.appendChild(childPriceEl);
                            }
                        } else {
                            if (typeof arg.event.extendedProps.prices != 'undefined') {
                                let basePriceEl = document.createElement('div');
                                basePriceEl.classList.add('price');
                                basePriceEl.innerHTML = ts_params.text_price + arg.event.extendedProps.price;

                                contentEl.appendChild(basePriceEl)
                            }
                        }
                    }
                }

                let arrayOfDomNodes = [contentEl]
                return {
                    domNodes: arrayOfDomNodes
                }
            },
            viewDidMount: function (arg) {
                if(arg.el) {
                    let el = arg.el;
                    if (self.timeOut) {clearTimeout(self.timeOut); }
                    self.timeOut = setTimeout(function () {
                        let viewHardnessEl = $(el).closest('.fc-view-harness.fc-view-harness-active');
                        if(viewHardnessEl && viewHardnessEl.outerHeight() == 0) {
                            viewHardnessEl.css({minHeight: '250px'});
                        }
                    }, 200);
                }
            },
            eventClick: function ({
                event,
                el,
                jsEvent,
                view
            }) {
                let startTime = moment(event.start, String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase())
                    .format(String(ts_params.dateformat || 'MM/DD/YYYY').toUpperCase());
                let endTime;
                if (event.end) {
                    endTime = moment(event.end, String(ts_params.dateformat || "MM/DD/YYYY").toUpperCase())
                        .format(String(ts_params.dateformat || 'MM/DD/YYYY').toUpperCase());
                } else {
                    endTime = startTime
                }
                setCheckInOut(
                    startTime,
                    endTime,
                    self.form_container
                );
                let price_by_per_person = $('.calendar-content', self.container).data('price-by-per-person') == 'on' ? true : false || false;
                if ( price_by_per_person ) {
                    if (typeof event.extendedProps.adult_price != 'undefined') {
                        $("#calendar_adult_price", self.form_container).val(event.extendedProps.adult_price);
                    }
                    if (typeof event.extendedProps.child_price != 'undefined') {
                        $("#calendar_child_price", self.form_container).val(event.extendedProps.child_price);
                    }
                } else {
                    if (typeof event.extendedProps.price != 'undefined') {
                        $("#calendar_price", self.form_container).val(event.extendedProps.price);
                    }
                }
                if(event.extendedProps.status) {
                    $(
                        "#calendar_status option[value=" + event.extendedProps.status + "]",
                        self.form_container
                    ).prop("selected", true);
                }
            },
            loading: function (isLoading) {
                if (isLoading) {
                    $(".overlay", self.container).addClass("open");
                } else {
                    $(".overlay", self.container).removeClass('open');
                }
            },
        };
        this.init = function () {
            self.container = jQuery(container);
            self.calendar = container.querySelector('.calendar-content');
            self.form_container = $('.calendar-form', self.container);
            setCheckInOut('', '', self.form_container);
            self.initCalendar();
        }
        this.initCalendar = function () {
            if (typeof FullCalendar != 'undefined') {
                self.fullCalendar = new FullCalendar.Calendar(self.calendar, self.fullCalendarOptions);
                self.fullCalendar.render();
            }
        }
    };
    function setCheckInOut(check_in, check_out, form_container) {
        $('#calendar_check_in', form_container).val(check_in);
        $('#calendar_check_out', form_container).val(check_out);
    }
    function resetForm(form_container) {
        $('#calendar_check_in', form_container).val('');
        $('#calendar_check_out', form_container).val('');
        $('#calendar_price', form_container).val('');
        $('#calendar_priority', form_container).val('');
        $('#calendar_number', form_container).val('');
        $('#calendar_adult_price', form_container).val('');
        $('#calendar_child_price', form_container).val('');
    }
    $(function () {
        $('input[name=price_by_per_person]').on('change', function(e) {
            if ($(this).val() == 'on' && $(this).is(':checked')){
                $('#calendar_price', '.calendar-form').parent().addClass('hide');
                $('#calendar_adult_price', '.calendar-form').parent().parent().removeClass('hide');
            } else {
                $('#calendar_price', '.calendar-form').parent().removeClass('hide');
                $('#calendar_adult_price', '.calendar-form').parent().parent().addClass('hide');
            }
        });
        $('.calendar-wrapper').each(function (index, el) {
            var t = $(this);
            var hotel = new HotelCalendar(el);
            var flag_submit = false;
            $('#calendar_submit', t).on('click', function (event) {
                var data = $('input, select', '.calendar-form').serializeArray();
                    data.push({
                        name: 'action',
                        value: 'ts_add_custom_price'
                    });
                    data.push({
                        name: 'price_by_per_person',
                        value: $('input[name=price_by_per_person]:checked').val() == 'on' ? true : false || false
                    });
                $('.form-message', t).attr('class', 'form-message').find('p').html('');
                $('.overlay', self.container).addClass('open');
                if(flag_submit) return false; flag_submit = true;
                $.post(ajaxurl, data, function (respon, textStatus, xhr) {
                    if (typeof respon == 'object') {
                        if(respon.status == 1) {
                            resetForm(t);
                            if(hotel.fullCalendar) {
                                hotel.fullCalendar.refetchEvents();
                            }
                        } else {
                            $('.form-message', t).addClass(respon.type).find('p').html(respon.message);
                            $('.overlay', self.container).removeClass('open');
                        }
                    } else {
                        $('.overlay', self.container).removeClass('open');
                    }
                    flag_submit = false;
                }, 'json');
                return false;
            });
            $(document).on('click', '.tabs .tab-link[href="tab-room-availability"]', function () {
                hotel.init();
            });
            $('body').on('calendar.change_month', function (event, value) {
                if (hotel.fullCalendar) {
                    var date =  hotel.fullCalendar.getData();
                    var month = date.format('M');
                    date = date.add(value-month, 'M');
                    hotel.fullCalendar.gotoDate(date.format('YYYY-MM-DD'))
                }
            });
        });
    });
});



