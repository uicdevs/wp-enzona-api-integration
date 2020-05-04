jQuery(document).ready(function ($) {

    $(".generate-access-token-saved").bind('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var ajaxurl = $('.ajax_url_value').val();


        var params = {
            "action": "ezai_get_token",
        };

        jQuery.ajax({
            url: ajaxurl,
            type: "post",
            data: params,
            success: function (response) {
               $(".generated_token").html(response);
            }
        });

    });

});