$('body').on('click', '.delete_transport_item', function(){
    var id = $(this).data('id');

    Swal.fire({
        title: "Удалить транспорт?",
        text: "Удалив, вы не сможете его восстановить",
        type: 'warning',
        allowOutsideClick: false,
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Удалить',
        cancelButtonText: 'Отмена',
        focusConfirm: false,
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.post({
                url: "/transport/delete/?id=" + id
            });
        }
    })
});

$('body').on('click', '.tr_full_text', function(){
    var btn = $(this);
    var target = btn.data('target');

    if( btn.hasClass('showed') ){
        btn.removeClass('showed');

        btn.find('.showBtn').show();
        btn.find('.hideBtn').hide();

        $('#' + target + "_short").show();
        $('#' + target).toggle();
    }
    else {
        btn.addClass('showed');
        btn.find('.showBtn').hide();
        btn.find('.hideBtn').show();

        $('#' + target + "_short").hide();
        $('#' + target).toggle();
    }
});

function reloadItem(blockId, trId){
    function get(){
        $.ajax({
            url: '/transport/default/item-position/',
            data: {id:trId},
            dataType: 'json',
            success: function(resp){
                if( resp.success ){
                    $('#'+blockId).html(resp.html);
                }
                else{
                    setTimeout(get, 2000);
                }
            }
        });
    }

    setTimeout(get, 2000);
}
