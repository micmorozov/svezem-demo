$('#skipBtn').click(function(){
    Swal.fire({
        title: "Вы уверены, что не хотите получать уведомления о новых грузах?",
        html: "<span style='color:red'>Это бесплатная услуга</span>",
        type: 'warning',
        allowOutsideClick: false,
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Уверен',
        cancelButtonText: 'Назад',
        focusConfirm: false,
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            var realForm = $(this).closest('form');
            var action = realForm.attr('action');
            var form = $('<form>').attr({action: action, method: 'POST'});
            $('body').append(form);
            var hid = $('<input>').attr({type: 'hidden', name: '_csrf', value: realForm.find('[name=_csrf]').val()});
            var btn = $('<button>').attr({type: 'submit', name: 'skip', value:1});
            form.append(hid);
            form.append(btn);
            btn.click();
        }
    })
});
