
jQuery(document).on('ready', function () {
    jQuery('ul.tabs li').click(function () {
        var tab_id = jQuery(this).attr('href');

        jQuery('ul.tabs li').removeClass('current');
        jQuery('.tab-content').removeClass('current');

        jQuery(this).addClass('current');
        jQuery("#" + tab_id).addClass('current');
    })
})


/* gallery meta box */
/*
 * A custom function that checks if element is in array, we'll need it later
 */
function in_array(el, arr) {
    for (var i in arr) {
        if (arr[i] == el) return true;
    }
    return false;
}

jQuery(function ($) {
    "use strict";
    /*
     * Sortable images
     */
    $('ul.trizen_hotel_img_gallery_mtb').sortable({
        items: 'li',
        cursor: '-webkit-grabbing', /* mouse cursor */
        scrollSensitivity: 40,
        stop: function (event, ui) {
            ui.item.removeAttr('style');

            var sort = new Array(), /* array of image IDs */
                gallery = $(this); /* ul.trizen_hotel_img_gallery_mtb */

            /* each time after dragging we resort our array */
            gallery.find('li').each(function (index) {
                sort.push($(this).attr('data-id'));
            });
            /* add the array value to the hidden input field */
            gallery.parent().next().val(sort.join());
            /* console.log(sort); */
        }
    });
    /*
     * Multiple images uploader
     */
    $('.trizen_upload_hotel_gallery_button').click(function (e) { /* on button click*/
        e.preventDefault();

        var button = $(this),
            hiddenfield = button.prev(),
            hiddenfieldvalue = hiddenfield.val().split(","), /* the array of added image IDs */
            custom_uploader = wp.media({
                title: 'Insert images', /* popup title */
                library: { type: 'image' },
                button: { text: 'Use these images' }, /* "Insert" button text */
                multiple: true
            }).on('select', function () {

                var attachments = custom_uploader.state().get('selection').map(function (a) {
                    a.toJSON();
                    return a;
                }),
                    thesamepicture = false,
                    i;

                /* loop through all the images */
                for (i = 0; i < attachments.length; ++i) {

                    /* if you don't want the same images to be added multiple time */
                    if (!in_array(attachments[i].id, hiddenfieldvalue)) {

                        /* add HTML element with an image */
                        $('ul.trizen_hotel_img_gallery_mtb').append('<li data-id="' + attachments[i].id + '"><img src="' + attachments[i].attributes.url + '" alt="Image"><a href="#" class="trizen_hotel_img_gallery_remove">+</a></li>');
                        /* add an image ID to the array of all images */
                        hiddenfieldvalue.push(attachments[i].id);
                    } else {
                        thesamepicture = true;
                    }
                }
                /* refresh sortable */
                $("ul.trizen_hotel_img_gallery_mtb").sortable("refresh");
                /* add the IDs to the hidden field value */
                hiddenfield.val(hiddenfieldvalue.join());
                /* you can print a message for users if you want to let you know about the same images */
                if (thesamepicture == true) alert('The same images are not allowed.');
            }).open();
    });

    /*
     * Remove certain images
     */
    $('body').on('click', '.trizen_hotel_img_gallery_remove', function () {
        var id = $(this).parent().attr('data-id'),
            gallery = $(this).parent().parent(),
            hiddenfield = gallery.parent().next(),
            hiddenfieldvalue = hiddenfield.val().split(","),
            i = hiddenfieldvalue.indexOf(id);

        $(this).parent().remove();

        /* remove certain array element */
        if (i != -1) {
            hiddenfieldvalue.splice(i, 1);
        }

        /* add the IDs to the hidden field value */
        hiddenfield.val(hiddenfieldvalue.join());

        /* refresh sortable */
        gallery.sortable("refresh");

        return false;
    });



    /*
     * Hotel Room Gallery start
     */
    /*
     * Sortable images
     */
    $('.trizen_hotel_room_img_gallery_mtb').sortable({
        items: 'li',
        cursor: '-webkit-grabbing', /* mouse cursor */
        scrollSensitivity: 40,
        stop: function (event, ui) {
            ui.item.removeAttr('style');

            var sort = new Array(), /* array of image IDs */
                gallery = $(this); /* ul.trizen_hotel_room_img_gallery_mtb */

            /* each time after dragging we resort our array */
            gallery.find('li').each(function (index) {
                sort.push($(this).attr('data-id'));
            });
            /* add the array value to the hidden input field */
            gallery.parent().next().val(sort.join());
            /* console.log(sort); */
        }
    });
    /*
     * Multiple images uploader
     */
    $('.trizen_upload_hotel_room_gallery_button').click(function (e) { /* on button click*/
        e.preventDefault();

        var button = $(this),
            hiddenfield = button.prev(),
            hiddenfieldvalue = hiddenfield.val().split(","), /* the array of added image IDs */
            custom_uploader = wp.media({
                title: 'Insert images', /* popup title */
                library: { type: 'image' },
                button: { text: 'Use these images' }, /* "Insert" button text */
                multiple: true
            }).on('select', function () {

                var attachments = custom_uploader.state().get('selection').map(function (a) {
                    a.toJSON();
                    return a;
                }),
                    thesamepicture = false,
                    i;

                /* loop through all the images */
                for (i = 0; i < attachments.length; ++i) {

                    /* if you don't want the same images to be added multiple time */
                    if (!in_array(attachments[i].id, hiddenfieldvalue)) {

                        /* add HTML element with an image */
                        $('.trizen_hotel_room_img_gallery_mtb').append('<li data-id="' + attachments[i].id + '"><img src="' + attachments[i].attributes.url + '" alt="Image"><a href="#" class="trizen_hotel_room_img_gallery_remove">+</a></li>');
                        /* add an image ID to the array of all images */
                        hiddenfieldvalue.push(attachments[i].id);
                    } else {
                        thesamepicture = true;
                    }
                }
                /* refresh sortable */
                $(".trizen_hotel_room_img_gallery_mtb").sortable("refresh");
                /* add the IDs to the hidden field value */
                hiddenfield.val(hiddenfieldvalue.join());
                /* you can print a message for users if you want to let you know about the same images */
                if (thesamepicture == true) alert('The same images are not allowed.');
            }).open();
    });

    /*
     * Remove certain images
     */
    $('body').on('click', '.trizen_hotel_room_img_gallery_remove', function () {
        var id = $(this).parent().attr('data-id'),
            gallery = $(this).parent().parent(),
            hiddenfield = gallery.parent().next(),
            hiddenfieldvalue = hiddenfield.val().split(","),
            i = hiddenfieldvalue.indexOf(id);

        $(this).parent().remove();

        /* remove certain array element */
        if (i != -1) {
            hiddenfieldvalue.splice(i, 1);
        }

        /* add the IDs to the hidden field value */
        hiddenfield.val(hiddenfieldvalue.join());

        /* refresh sortable */
        gallery.sortable("refresh");

        return false;
    });
    /*
    * Hotel Room Gallery End
    * */



    /* Hotel Features */
    $(function () {
        $("#trizen_hotel_features_add").on('click', function (e) {
            e.preventDefault();
            var template = wp.template('repeater'),
                html = template();
            $("#trizen_hotel_features_data").append(html);
        });
        $(document).on('click', '.hotel-accordion-item .hotel-accordion-title', function (e) {
            e.preventDefault();
            $(".hotel-accordion-item").removeClass('active')
            $(".hotel-accordion-item .accordion-content").removeClass('active')
            $(this).parent().addClass('active');
        });
        $(document).on('click', '.hotel-accordion-item.active .hotel-accordion-title', function (e) {
            e.preventDefault();
            $(this).parent().removeClass('active');
        });
        $(document).on('click', '.trizen_hotel_features_remove', function (e) {
            e.preventDefault();
            $(this).parent().remove();
        });
    });


    /* Hotel Faqs */
    $(function () {
        $("#trizen_hotel_faqs_add").on('click', function (e) {
            e.preventDefault();
            var template = wp.template('repeater2'),
                html = template();
            $("#trizen_hotel_faqs_data").append(html);
        });
        $(document).on('click', '.trizen_hotel_faq_remove', function (e) {
            e.preventDefault();
            $(this).parent().remove();
        });
    });

    /* Hotel Room Extra Services */
    $(function () {
        $("#trizen_hotel_room_extra_service_add").on('click', function (e) {
            e.preventDefault();
            var template = wp.template('repeater3'),
                html = template();
            $("#trizen_hotel_room_extra_services_data").append(html);
        });
        $(document).on('click', '.trizen_hotel_room_extra_service_remove', function (e) {
            e.preventDefault();
            $(this).parent().remove();
        });
    });

    /* Hotel Room Other Facility */
    $(function () {
        $("#trizen_room_other_facility_add").on('click', function (e) {
            e.preventDefault();
            var template = wp.template('repeater4'),
                html = template();
            $("#trizen_room_other_facility_data").append(html);
        });
        $(document).on('click', '.trizen_room_other_facility_remove', function (e) {
            e.preventDefault();
            $(this).parent().remove();
        });
    });

    /* Hotel Room Rules */
    $(function () {
        $("#trizen_room_rules_add").on('click', function (e) {
            e.preventDefault();
            var template = wp.template('repeater5'),
                html = template();
            $("#trizen_room_rules_data").append(html);
        });
        $(document).on('click', '.trizen_room_rules_remove', function (e) {
            e.preventDefault();
            $(this).parent().remove();
        });
    });


    /* Select to Select2 convert */
    $(document).ready(function () {
        $('.select-to-select2').select2();
    });

    /* Location Search */
    if ($('.ts-select-location').length) {
        $('.ts-select-location').each(function (index, el) {
            var parent = $(this);
            var input  = $('input[name="search"]', parent);
            var list   = $('.location-list-wrapper', parent);
            var timeout;
            input.keyup(function (event) {
                clearTimeout(timeout);
                var t   = $(this);
                timeout = setTimeout(function () {
                    var text = t.val().toLowerCase();
                    if (text == '') {
                        $('.location-list', list).show()
                    } else {
                        $('.location-list', list).hide();
                        $(".location-list", list).each(function () {
                            var name = $(this).data("name").toLowerCase();
                            var reg  = new RegExp(text, "g");
                            if (reg.test(name)) {
                                $(this).show()
                            }
                        })
                    }
                }, 100)
            })
        })
    }

    if($("#enable_google_map_setting .nice-checkbox input").length) {
        $("#enable_google_map_setting .nice-checkbox input").on('click', function () {
            $("#gmap_apikey_setting").toggleClass('hidden');
            $("#location_map_setting").toggleClass('hidden');
        });
    }

});



/* Accordion */
// Listen for click on the document
document.addEventListener('click', function (event) {

    //Bail if our clicked element doesn't have the class
    if (!event.target.classList.contains('accordion-toggle')) return;

    // Get the target content
    var content = document.querySelector(event.target.hash);
    if (!content) return;

    // Prevent default link behavior
    event.preventDefault();

    // If the content is already expanded, collapse it and quit
    if (content.classList.contains('active')) {
        content.classList.remove('active');
        return;
    }

    // Get all open accordion content, loop through it, and close it
    var accordions = document.querySelectorAll('.accordion-content.active');
    for (var i = 0; i < accordions.length; i++) {
        accordions[i].classList.remove('active');
    }

    // Toggle our content
    content.classList.toggle('active');
})


/* Range */
    if(jQuery(".adult_number").length) {
        const range = document.querySelector(".adult_number");
        const bubble = document.querySelector(".range-bubble");
        range.addEventListener("input", () => {
            setBubble(range, bubble);
        });
        setBubble(range, bubble);

        function setBubble(range, bubble) {
            const val = range.value;
            const min = range.min ? range.min : 0;
            const max = range.max ? range.max : 100;
            const newVal = Number(((val - min) * 100) / (max - min));
            bubble.innerHTML = val;
            // Sorta magic numbers based on size of the native UI thumb
            bubble.style.left = `calc(${newVal}% + (${8 - newVal * 0.15}px))`;
        }
    }

    if(jQuery(".bed_number").length) {
        const range2 = document.querySelector(".bed_number");
        const bubble2 = document.querySelector(".range2-bubble");
        range2.addEventListener("input", () => {
            setBubble(range2, bubble2);
        });
        setBubble(range2, bubble2);

        function setBubble(range, bubble) {
            const val = range.value;
            const min = range.min ? range.min : 0;
            const max = range.max ? range.max : 100;
            const newVal = Number(((val - min) * 100) / (max - min));
            bubble.innerHTML = val;
            // Sorta magic numbers based on size of the native UI thumb
            bubble.style.left = `calc(${newVal}% + (${11 - newVal * 0.15}px))`;
        }
    }

    if(jQuery(".children_number").length) {
        const range3 = document.querySelector(".children_number");
        const bubble3 = document.querySelector(".range3-bubble");
        range3.addEventListener("input", () => {
            setBubble(range3, bubble3);
        });
        setBubble(range3, bubble3);

        function setBubble(range, bubble) {
            const val = range.value;
            const min = range.min ? range.min : 0;
            const max = range.max ? range.max : 100;
            const newVal = Number(((val - min) * 100) / (max - min));
            bubble.innerHTML = val;
            // Sorta magic numbers based on size of the native UI thumb
            bubble.style.left = `calc(${newVal}% + (${11 - newVal * 0.15}px))`;
        }
    }

    if(jQuery(".hotel_booking_period").length) {
        const range4 = document.querySelector(".hotel_booking_period");
        const bubble4 = document.querySelector(".range4-bubble");
        range4.addEventListener("input", () => {
            setBubble(range4, bubble4);
        });
        setBubble(range4, bubble4);

        function setBubble(range, bubble) {
            const val = range.value;
            const min = range.min ? range.min : 0;
            const max = range.max ? range.max : 100;
            const newVal = Number(((val - min) * 100) / (max - min));
            bubble.innerHTML = val;
            // Sorta magic numbers based on size of the native UI thumb
            bubble.style.left = `calc(${newVal}% + (${11 - newVal * 0.15}px))`;
        }
    }

    if(jQuery(".min_book_room").length) {
        const range5 = document.querySelector(".min_book_room");
        const bubble5 = document.querySelector(".range5-bubble");
        range5.addEventListener("input", () => {
            setBubble(range5, bubble5);
        });
        setBubble(range5, bubble5);

        function setBubble(range, bubble) {
            const val = range.value;
            const min = range.min ? range.min : 0;
            const max = range.max ? range.max : 100;
            const newVal = Number(((val - min) * 100) / (max - min));
            bubble.innerHTML = val;
            // Sorta magic numbers based on size of the native UI thumb
            bubble.style.left = `calc(${newVal}% + (${11 - newVal * 0.15}px))`;
        }
    }

    if(jQuery(".hotel_star").length) {
        const range6 = document.querySelector(".hotel_star");
        const bubble6 = document.querySelector(".range6-bubble");
        range6.addEventListener("input", () => {
            setBubble(range6, bubble6);
        });
        setBubble(range6, bubble6);

        function setBubble(range, bubble) {
            const val = range.value;
            const min = range.min ? range.min : 0;
            const max = range.max ? range.max : 5;
            const newVal = Number(((val - min) * 99) / (max - min));
            bubble.innerHTML = val;
            // Sorta magic numbers based on size of the native UI thumb
            bubble.style.left = `calc(${newVal}% + (${12 - newVal * 0.15}px))`;
        }
    }


