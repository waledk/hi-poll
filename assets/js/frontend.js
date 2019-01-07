jQuery(function ($) {

    $('.hipoll-frontend-poll .poll-single-option input[type="radio"]').on('change', function () {
        var pollDiv = $(this).parents('.hipoll-frontend-poll'),
            pollID = pollDiv.attr('data-poll-id'),
            optionID = $(this).attr('data-option-id');
        $.ajax({
            url: hipoll.ajax_url,
            type: 'post',
            data: {
                action: 'hipoll_voting',
                pollID: pollID,
                optionID: optionID,
                hipollCookie: getCookie('hipoll_voted_polls')
            },
            beforeSend: function () {
                pollDiv.animate({'opacity': '0.5'}, 300);
            },
            success: function (result) {
                pollDiv.css('opacity', '');
                result = $.parseJSON(result);
                if ( result['status']['code'] == '200' ) {
                    $.each(result['poll_options'], function (key, val) {
                        var optionDiv = pollDiv.find('input[data-option-id="'+ val['id'] +'"]').parents('.poll-single-option');
                        pollDiv.addClass('voted');
                        optionDiv.find('label').attr('data-percent', val['percent'] + '%');
                        optionDiv.find('.progress').css('width', val['percent'] + '%');
                    });
                }
            }
        });
    });

    function getCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for(var i = 0; i <ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return '';
    }

});
