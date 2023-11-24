$('body').on('click', '.delete_cargo_item', function () {
    let id = $(this).data('id');

    let form = "<form style='text-align: left;'>" +
        "<input type='radio' name='deleteReason' id='reason1' value='1'> " +
        "<label for='reason1'>Нашел перевозчика на svezem.ru</label><br>" +
        "<input type='radio' name='deleteReason' id='reason2' value='2'> " +
        "<label for='reason2'>Нашел перевозчика другим способом</label><br>" +
        "<input type='radio' name='deleteReason' id='reason3' value='3'> " +
        "<label for='reason3'>Передумал перевозить</label><br>" +
        "<input type='radio' name='deleteReason' id='reason0' value='0'> " +
        "<label for='reason0'>Свой вариант</label><br>" +
        "<input type='text' class='swal2-input' id='ownVariantDeleteReason' style='display: none' placeholder='Введите свой вариант' autocomplete='off'>" +
        "</form>";

    //Окно вопроса
    let askWindow = (result) => {
        if( !result.value ) return ;

        Swal.fire({
            title: 'Почему вы решили удалить груз?',
            type: 'question',
            html: form,
            allowOutsideClick: false,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Удалить',
            cancelButtonText: 'Отмена',
            reverseButtons: true,
            focusConfirm: false,
            onBeforeOpen: formEvents,
            preConfirm: () => {
                let checked = $("[name='deleteReason']:checked");

                let variant = checked.val();
                if (variant == undefined) {
                    Swal.showValidationMessage('Выберите вариант');
                    return false;
                }

                //Значение из label
                let value = checked.next('label:first').html();

                if (variant == 0) {
                    value = $('#ownVariantDeleteReason').val();

                    if (value.trim() == '') {
                        Swal.showValidationMessage('Укажите причину');
                        return false;
                    }
                }

                return value;
            }
        }).then(deleteQuery)
    };

    //Окно удаления
    Swal.fire({
        title: 'Удалить груз?',
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
    }).then(askWindow);

    //Запрос на удаление
    let deleteQuery = (msg) => {
        if( !msg.value ) return ;

        $.post({
            url: "/cargo/delete/?id=" + id,
            data: {msg: msg.value}
        });
    }
});

function formEvents() {
    $("input[name='deleteReason']").change(function () {
        let variant = $(this).val();
        if (variant == 0) {
            $('#ownVariantDeleteReason').show();
        } else {
            $('#ownVariantDeleteReason').hide();
        }
    });
}