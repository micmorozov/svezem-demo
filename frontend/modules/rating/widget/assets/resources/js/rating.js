function clickCallback(url, id){
    return function RatingClick(score){
        var stars = $(this);

        jQuery.ajax({
            type: 'POST',
            url: url,
            data: {
                score: score,
                id: id
            },
            success: function (data) {
                stars.raty('score', data.score);
                stars.raty('readOnly', true);

                $('#ratingScore').text(data.score);
                $('#ratingSum').text(data.sum);
            }
        });
    }
}

function getRating(selector, id){
    $.get({
        url: '/rating/default/get/',
        data: {id: id},
        success: function(resp){
            $(selector).raty('set', {
                score: resp.score,
                readOnly: resp.readOnly
            });

            $('#ratingScore').text(resp.score);
            $('#ratingSum').text(resp.sum);
        }
    });
}

