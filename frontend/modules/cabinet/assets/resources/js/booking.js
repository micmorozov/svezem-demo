$('body').on('click', '.booking', function(){
    let bookingBtn = $(this);
    let cargo_id = bookingBtn.data('cargo_id');

    $.post({
        url: '/cabinet/cargo-booking/booking/',
        data: {
            cargo_id: cargo_id,
            web: true
        },
        success: function(resp){
            if(resp.error){
                Swal.fire({
                    type: 'error',
                    title: 'Ошибка',
                    html: resp.error
                });
                return;
            }

            $('#booking_block_'+cargo_id).html(resp.html);
        }
    });
});

$('body').on('click', '.save_booking', function(){
    let btn = $(this);
    let cargo_id = btn.data('cargo_id');

    $.post({
        url: '/cabinet/cargo-booking/save/',
        data: {
            cargo_id: cargo_id,
            price: $('#price_'+cargo_id).val(),
            web: true
        },
        success: function(resp){
            if(resp.error){
                Swal.fire({
                    type: 'error',
                    title: 'Ошибка',
                    text: resp.error
                });
                return;
            }

            $('#booking_block_'+cargo_id).html(resp.html);
        }
    });
});

$('body').on('click', '.cancel_booking', function(){
    let btn = $(this);
    let cargo_id = btn.data('cargo_id');

    $.post({
        url: '/cabinet/cargo-booking/cancel/',
        data: {
            cargo_id: cargo_id,
            web: true
        },
        success: function(resp){
            if(resp.error){
                Swal.fire({
                    type: 'error',
                    title: 'Ошибка',
                    text: resp.error
                });
                return;
            }

            $('#booking_block_'+cargo_id).html(resp.html);

            //timer($('#timer_'+cargo_id));
        }
    });
});

/*function startTimers(){
    $('.timer').each(function(){
        timer($(this));
    });
}

function timer(tag){
    let ttl = tag.data('time');

    if( ttl <= 0 ) return ;

    var time = Date.now();

    var timeToExpired = parseInt(time/1000)+ttl;

    let timer_id = setInterval(function(){
        var time = Date.now();
        time = parseInt(time/1000);

        var diff = timeToExpired-time;

        if( diff < 1 ){
            clearInterval(timer_id);
        }

        var date = new Date(null);
        date.setSeconds(diff);
        var result = date.toISOString().substr(14, 5);
        tag.text(result);

    }, 1000);
}*/

//обновление списка 5 мин
setInterval(function () {
    $.pjax.reload({container : '#cargoBooking'});
}, 1000*60*5);

$('body').on('click', '.changePrice', function(){
    let cargo_id = $(this).data('cargo_id');

    $('#booking_info_'+cargo_id).hide();
    $('#booking_info_edit_'+cargo_id).show();
});

$('body').on('click', '.cancel_edit', function(){
    let cargo_id = $(this).data('cargo_id');

    $('#booking_info_'+cargo_id).show();
    $('#booking_info_edit_'+cargo_id).hide();
});

$('body').on('click', '.booking_edit', function(){
    let cargo_id = $(this).data('cargo_id');

    $.post({
        url: '/cabinet/cargo-booking/edit/',
        data: {
            cargo_id: cargo_id,
            price: $('#price_'+cargo_id).val(),
            web: true
        },
        success: function(resp){
            if(resp.error){
                Swal.fire({
                    type: 'error',
                    title: 'Ошибка',
                    text: resp.error
                });
                return;
            }

            $('#booking_block_'+cargo_id).html(resp.html);
        }
    });
});

// Управление заметками к заказу
$('body').on('click', '.changeComment', function(){
    let cargo_id = $(this).data('cargo_id');

    $('#cargo_comment_'+cargo_id).hide();
    $('#cargo_comment_edit_'+cargo_id).show();
});
// Отмена
$('body').on('click', '.comment_cancel', function(){
    let cargo_id = $(this).data('cargo_id');

    $('#cargo_comment_'+cargo_id).show();
    $('#cargo_comment_edit_'+cargo_id).hide();
});

// Сохранить
$('body').on('click', '.comment_save', function(){
    let cargo_id = $(this).data('cargo_id');

    $.post({
        url: '/cabinet/cargo-booking/comment-save/',
        data: {
            cargo_id: cargo_id,
            comment: $('#comment_'+cargo_id).val(),
            web: true
        },
        success: function(resp){
            if(resp.error){
                Swal.fire({
                    type: 'error',
                    title: 'Ошибка',
                    text: resp.error
                });
                return;
            }

            $('#comment_block_'+cargo_id).html(resp.html);
        }
    });
});

///////////////////////////////////
