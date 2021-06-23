
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



/*jQuery(function($){
    var dateRangePickerTwo = $('input.date-range');
    if ($(dateRangePickerTwo).length) {
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
                /!* Info{start, end, startStr, endStr, allDay, jsEvent, view, resource } *!/
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
                /!** arg{event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view} *!/
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
});*/


jQuery(function($){

    var dateRangePickerTwo = $('input.date-range');
    if ($(dateRangePickerTwo).length) {
        $(dateRangePickerTwo).daterangepicker({
            language: ts_params.locale || '',
            dateFormat: "mm/dd/yy",
            firstDay: 1,
            autoUpdateInput: true,
            singleDatePicker: true,
        });
    }

    var HotelCalendar = function(container){
        var self = this;
        this.container = container;
        this.calendar= null;
        this.form_container = null;
        this.init = function(){
            self.container = container;
            self.calendar = $('.calendar-content', self.container);
            self.form_container = $('.calendar-form', self.container);
            setCheckInOut('', '', self.form_container);
            self.initCalendar();
        }
        this.initCalendar = function(){
            self.calendar.fullCalendar({
                firstDay: 1,
                lang: ts_params.locale,
                timezone: ts_timezone.timezone_string,
                customButtons: {
                    reloadButton: {
                        text: ts_params.text_refresh,
                        click: function() {
                            self.calendar.fullCalendar( 'refetchEvents' );
                        }
                    }
                },
                header : {
                    left:   'today,reloadButton',
                    center: 'title',
                    right:  'prev, next'
                },
                selectable: true,
                select : function(start, end, jsEvent, view){
                    var start_date = new Date(start._d).toString("MM");
                    var end_date = new Date(end._d).toString("MM");
                    var start_year = new Date(start._d).toString("yyyy");
                    var end_year = new Date(end._d).toString("yyyy");
                    var today = new Date().toString("MM");
                    var today_year = new Date().toString("yyyy");
                    if((start_date < today && start_year <= today_year) || (end_date < today && end_year <= today_year)){
                        self.calendar.fullCalendar('unselect');
                        setCheckInOut('', '', self.form_container);
                    }else{
                        var zone = moment(start._d).format('Z');
                        zone = zone.split(':');
                        zone = "" + parseInt(zone[0]) + ":00";
                        var check_in = moment(start._d).utcOffset(zone).format("MM/DD/YYYY");
                        var	check_out = moment(end._d).utcOffset(zone).subtract(1, 'day').format("MM/DD/YYYY");
                        setCheckInOut(check_in, check_out, self.form_container);
                    }
                    /*$('.fc-bg').removeClass('joverlayh');
                    $('.fc-widget-content').find('.fc-bg').removeClass('joverlay');
                    $('.fc-widget-content').each(function () {
                        if($(this).has('.fc-highlight-skeleton').length){
                            $(this).find('.fc-bg').addClass('joverlay');
                            $(this).find('.fc-bg').addClass('joverlayh');
                        }else{
                            $(this).find('.fc-bg').addClass('joverlay');
                            $(this).find('.fc-bg').addClass('joverlayhr');
                            //$(this).find('.fc-highlight-skeleton table tr td').not('.fc-highlight').addClass('joverlay');
                        }
                    });*/
                },
                events:function(start, end, timezone, callback) {
                    $.ajax({
                        url: ajaxurl,
                        dataType: 'json',
                        type:'post',
                        data: {
                            action: 'ts_get_availability_hotel',
                            post_id:self.container.data('post-id'),
                            start: start.unix(),
                            end: end.unix()
                        },
                        success: function(doc){
                            if(typeof doc == 'object'){
                                callback(doc);
                            }
                        },
                        error:function(e)
                        {
                            alert('Can not get the availability slot. Lost connect with your sever');
                        }
                    });
                },
                eventClick: function(event, element, view){
                    setCheckInOut(event.start.format('MM/DD/YYYY'),event.start.format('MM/DD/YYYY'),self.form_container);
                                        let price_by_per_person = $('.calendar-content', self.container).data('price-by-per-person') == 'on' ? true : false || false;
                                        if ( price_by_per_person ) {
                                            $('#calendar_adult_price', self.form_container).val(event.adult_price);
                                            $('#calendar_child_price', self.form_container).val(event.child_price);
                                        } else {
                                            $('#calendar_price', self.form_container).val(event.price);
                                        }
                    $('#calendar_number', self.form_container).val(event.number);
                    $('#calendar_status option[value='+event.status+']', self.form_container).prop('selected');
                },
                eventRender: function(event, element, view){
                    var html = '';
                    if(event.status == 'available'){
                        let price_by_per_person = $('.calendar-content', self.container).data('price-by-per-person') == 'on' ? true : false || false;
                        if ( price_by_per_person ) {
                            html += `<div class="price">${ts_params.text_adult} ${event.adult_price}</div>`;
                            html += `<div class="price">${ts_params.text_child} ${event.child_price}</div>`;
                        } else {
                            html += '<div class="price -0-o-">'+ ts_params.text_price +' '+event.price+'</div>';
                        }
                    }
                    if(typeof event.status == 'undefined' || event.status != 'available'){
                        html += '<div class="not_available">'+ ts_params.text_unavailable +'</div>';
                    }
                    $('.fc-content', element).html(html);
                },
                loading: function(isLoading, view){
                    if(isLoading){
                        $('.overlay', self.container).addClass('open');
                    }else{
                        $('.overlay', self.container).removeClass('open');
                    }
                },
            });
        }
    };
    function setCheckInOut(check_in, check_out, form_container){
        $('#calendar_check_in', form_container).val(check_in);
        $('#calendar_check_out', form_container).val(check_out);
    }
    function resetForm(form_container){
        $('#calendar_check_in', form_container).val('');
        $('#calendar_check_out', form_container).val('');
        $('#calendar_price', form_container).val('');
        $('#calendar_priority', form_container).val('');
        $('#calendar_number', form_container).val('');
        $('#calendar_adult_price', form_container).val('');
        $('#calendar_child_price', form_container).val('');
    }
    jQuery(document).ready(function($) {
        $('input[name=price_by_per_person]').on('change', function(e) {
            if ($(this).val() == 'on' && $(this).is(':checked')){
                $('#calendar_price', '.calendar-form').parent().addClass('hide');
                $('#calendar_adult_price', '.calendar-form').parent().parent().removeClass('hide');
            } else {
                $('#calendar_price', '.calendar-form').parent().removeClass('hide');
                $('#calendar_adult_price', '.calendar-form').parent().parent().addClass('hide');
            }
        });
        $('.calendar-wrapper').each(function(index, el) {
            var t = $(this);
            var hotel = new HotelCalendar(t);
            hotel.init();
            var flag_submit = false;
            $('#calendar_submit', t).click(function(event) {
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
                $.post(ajaxurl, data, function(respon, textStatus, xhr) {
                    if(typeof respon == 'object'){
                        if(respon.status == 1){
                            resetForm(t);
                            $('.calendar-content', t).fullCalendar('refetchEvents');
                        }else{
                            $('.form-message', t).addClass(respon.type).find('p').html(respon.message);
                            $('.overlay', self.container).removeClass('open');
                        }
                    }else{
                        $('.overlay', self.container).removeClass('open');
                    }
                    flag_submit = false;
                }, 'json');
                return false;
            });
            $(document).on('click','.tabs .tab-link[href="tab-room-availability"]',function(){
                //hotel.calendar.fullCalendar( 'refetchEvents' );
                $('.calendar-content', t).fullCalendar('today');
            });
            $('body').on('calendar.change_month', function(event, value){
                var date = hotel.calendar.fullCalendar('getDate');
                var month = date.format('M');
                date = date.add(value-month, 'M');
                hotel.calendar.fullCalendar( 'gotoDate', date.format('YYYY-MM-DD') );
            });
        });
    });
});




