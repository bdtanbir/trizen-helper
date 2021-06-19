
(function($) {
    "use strict";

    // Save Button reacting on any changes
    /*var headerSaveBtn = $( '.trizen-setting-save' );
    $('.form-settings input[type="checkbox"], #add_hotel_review_star').on( 'click', function( e ) {
        headerSaveBtn.addClass( 'save-now' );
        headerSaveBtn.removeAttr('disabled').css({ cursor: 'pointer', opacity: '1' });
    } );
    $('.form-settings input[type="text"]').on( 'change', function( e ) {
        headerSaveBtn.addClass( 'save-now' );
        headerSaveBtn.removeAttr('disabled').css({ cursor: 'pointer', opacity: '1' });
    } );

    // Saving Data With Ajax Request
    $( '.trizen-setting-save' ).on( 'click', function(event) {
        event.preventDefault();
        var _this = $(this);
            $.ajax( {
                // url: trizen_settings_panel_param.ajaxurl,
                type: 'post',
                data: {
                    action: 'save_settings_with_ajax',
                    fields: $( 'form#trizen_admin_settings_panel_form' ).serialize(),
                },
                beforeSend: function() {
                    _this.html('<svg class="fa-spin" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><circle cx="24" cy="4" r="4" fill="#fff"/><circle cx="12.19" cy="7.86" r="3.7" fill="#fffbf2"/><circle cx="5.02" cy="17.68" r="3.4" fill="#fef7e4"/><circle cx="5.02" cy="30.32" r="3.1" fill="#fef3d7"/><circle cx="12.19" cy="40.14" r="2.8" fill="#feefc9"/><circle cx="24" cy="44" r="2.5" fill="#feebbc"/><circle cx="35.81" cy="40.14" r="2.2" fill="#fde7af"/><circle cx="42.98" cy="30.32" r="1.9" fill="#fde3a1"/><circle cx="42.98" cy="17.68" r="1.6" fill="#fddf94"/><circle cx="35.81" cy="7.86" r="1.3" fill="#fcdb86"/></svg>Saving Data..');
                },
                success: function( response ) {
                    setTimeout(function() {
                        _this.html('Save Settings');
                        Swal.fire(
                            'Settings Saved!',
                            'Click OK to continue',
                            'success'
                        );
                        headerSaveBtn.removeClass( 'save-now' ).css({ cursor: 'no-drop', opacity: '0.5' });
                        // headerSaveBtn.setAttribute('disabled', 'disabled').css({ cursor: 'no-drop', opacity: '0.5' });
                    }, 2000);
                },
                error: function() {
                    Swal.fire(
                        'Oops...',
                        'Something went wrong!',
                        'error'
                    );
                }
            } );
    } );*/


    $(function () {
        $("#add_hotel_review_star").on('click', function (e) {
            e.preventDefault();
            var template = wp.template('repeater'),
                html = template();
            $("#hotel_review_star_group").append(html);
        });
        $(document).on('click', '#remove_hotel_review_star', function (e) {
            e.preventDefault();
            $(this).parent().remove();
        });
        /*if($("#add_hotel_review_star p").length <=1) {
            $("#remove_hotel_review_star").remove()
        }*/
    });


})(jQuery);

