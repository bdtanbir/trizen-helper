
jQuery(document).on('ready', function($) {
    $('ul.tabs li').click(function () {
        var tab_id = $(this).attr('href');

        $('ul.tabs li').removeClass('current');
        $('.tab-content').removeClass('current');

        $(this).addClass('current');
        $("#" + tab_id).addClass('current');
    })
})
