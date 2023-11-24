Vue.component('rule', {
    template: '#rule-component',
    props: ['id', 'cityFrom', 'cityTo', 'categoriesId', 'categoriesText', 'msgCount'],
    data: function () {
        return {
            show: false,
            catIds: this.categoriesId,
            cityFromErrorMsg: false,
            cityToErrorMsg: false,
            catErrMsg: false
        };
    },
    computed: {
        optionsFrom: function () {
            return [
                {
                    id: this.cityFrom.id,
                    text: this.cityFrom.select,
                    type: this.cityFrom.type
                }
            ];
        },
        optionsTo: function () {
            return [
                {
                    id: this.cityTo.id,
                    text: this.cityTo.select,
                    type: this.cityTo.type
                }
            ];
        },
        formErr: function () {
            return (this.cityFromErrorMsg || this.cityToErrorMsg || this.catErrMsg) ? 'Исправьте ошибки в форме' : false;
        }
    },
    methods: {
        save: function () {
            console.log('here');
            var data = {
                locationFrom: this.cityFrom.id,
                locationTo: this.cityTo.id,
                locationFromType: this.cityFrom.type,
                locationToType: this.cityTo.type,
                categoriesId: this.catIds
            };

            if (this.validate()) {
                axios.patch('/sub/default/rule/?id=' + this.id, data)
                    .then((resp) => {
                        var data = resp.data;

                        this.changeObj('cityFrom', data.cityFrom);
                        this.changeObj('cityTo', data.cityTo);

                        this.catIds = data.categoriesId;
                        this.categoriesText = data.categoriesText;
                        this.msgCount = data.msgCount;

                        this.show = false;
                    });
            }
        },
        changeObj: function (prop, obj) {
            for (let attr in obj) {
                this[prop][attr] = obj[attr];
            }
        },
        validate: function () {
            var res1 = this.validateCityFrom();
            var res2 = this.validateCityTo();
            var res3 = this.validateCat();

            return res1 && res2 && res3;
        },
        validateCityFrom: function () {
            this.cityFromErrorMsg = !this.cityFrom.id ? 'Необходимо заполнить «Место отправки».' : false;
            return !this.cityFromErrorMsg;
        },
        validateCityTo: function () {
            this.cityToErrorMsg = !this.cityTo.id ? 'Необходимо заполнить «Место доставки».' : false;
            return !this.cityToErrorMsg;
        },
        validateCat: function () {
            this.catErrMsg = !this.catIds.length ? 'Необходимо заполнить «Виды перевозки».' : false;
            return !this.catErrMsg;
        }
    }
});

var ruleBlock = null;
function init_vue() {
    ruleBlock = new Vue({
        el: '#subscribe_block',
        data: {
            list: [],
            fetched: false,
            form: {
                from: formFrom,
                to: formTo,
                catIds: catIds,
                catErrMsg: false
            },
            msgCount: 1
        },
        computed: {
            totalMsgCount: function () {
                return this.list.reduce((mem, rule) => mem + rule.msgCount, 0);
            },
            //Эти переменные нужны для watch
            cityFrom: function () {
                return this.form.from.id;
            },
            cityTo: function () {
                return this.form.to.id;
            },
            catIds: function () {
                return this.form.catIds;
            },
            formErr: function () {
                return (this.form.from.errorMsg || this.form.to.errorMsg || this.form.catErrMsg) ? 'Исправьте ошибки в форме' : false;
            }
        },
        created: function () {
            axios.get('/sub/default/rules/')
                .then((resp) => {
                        this.list = resp.data;
                        this.fetched = true;
                    },
                    () => {
                        alert('Не удалось загрузить правила');
                    });
        },
        methods: {
            copy: function (id) {
                axios.post('/sub/default/rule-copy/', {copy_id: id})
                    .then(resp => {
                        this.list.push(resp.data);
                    }, () => {
                        alert('Не удалось получить ответ сервера');
                    });
            },
            remove: function (id) {
                if (confirm("Удалить правило?")) {
                    axios.delete('/sub/default/rule/?id=' + id)
                        .then(() => {
                            this.list = this.list.filter(rule => rule.id != id);
                        }, () => {
                            alert('Не удалось получить ответ сервера');
                        })
                }
            },
            getParams: function () {
                return {
                    locationFrom: this.form.from.id,
                    locationTo: this.form.to.id,
                    locationFromType: this.form.from.type,
                    locationToType: this.form.to.type,
                    categoriesId: this.form.catIds
                };
            },
            createRule: function () {
                if (this.validate()) {
                    axios.post("/sub/default/rule/", this.getParams())
                        .then(resp => {
                            //добавляем новое правило
                            this.list.push(resp.data);

                            //очищаем форму
                            this.$refs.cityFrom.clear();
                            this.$refs.cityTo.clear();
                            this.$refs.cats.clear();
                        }, () => {
                            alert('Не удалось получить ответ сервера');
                        })
                }
            },
            validate: function () {
                var res1 = this.validateCityFrom();
                var res2 = this.validateCityTo();
                var res3 = this.validateCat();

                return res1 && res2 && res3;
            },
            validateCityFrom: function () {
                this.form.from.errorMsg = !this.form.from.id ? 'Необходимо заполнить «Место отправки».' : false;
                return !this.form.from.errorMsg;
            },
            validateCityTo: function () {
                this.form.to.errorMsg = !this.form.to.id ? 'Необходимо заполнить «Место доставки».' : false;
                return !this.form.to.errorMsg;
            },
            validateCat: function () {
                this.form.catErrMsg = !this.form.catIds.length ? 'Необходимо заполнить «Виды перевозки».' : false;
                return !this.form.catErrMsg;
            },
            getMsgCount: function () {
                axios.get('/sub/default/msg-count/', {params: this.getParams()})
                    .then(resp => {
                        this.msgCount = resp.data.count;
                    })
            }
        },
        watch: {
            cityFrom: function () {
                this.getMsgCount();
            },
            cityTo: function () {
                this.getMsgCount();
            },
            catIds: function () {
                this.getMsgCount();
            }
        }
    });
}

function getType() {
    return $('[name="Subscribe[type]"]:checked').val();
}

//Обработка смены типа
$('[name="Subscribe[type]"]').change(function () {
    $('.radio-pill').each(function (i, e) {
        $(e).removeClass('active');
    });

    $(this).parents('.radio-pill').addClass('active');

    var type = getType();

    if (type === 'free') {
        $('.calendar').hide();

        $('#payButtons').hide();
        $('#saveButtons').show();
    } else {
        $('.calendar').show();
        $('#payButtons').show();
        $('#saveButtons').hide();
        setPayBtmText();
    }
});

function getPayButtonText() {
    var price = $('#subscribe-addmessage').val() * priceForMsg;
    price = isNaN(price) ? 0 : price;
    $('.totalPrice').text(price + ' руб.');
    return +price + ' руб.';
}

$('#subscribe-addmessage').on('keyup change ', setPayBtmText);

function setPayBtmText() {
    if (getType() == 'paid') {

        $('.payBtn').each(function () {
            var tag = $(this);
            var text = tag.data('text');

            if (text == undefined) {
                text = tag.text();
                tag.data('text', text);
            }
            tag.text(text + ' ' + getPayButtonText());
        });
    }
}

//Чтобы нарисовать цены на кнопках после загрузки
$('#subscribe-addmessage').change();

//Расчет стоимости зп несколько дней
$('.fastCalendar').click(function () {
    var days = $(this).data('period');
    var msgCount = ruleBlock.totalMsgCount;
    $('#subscribe-addmessage').val(Math.ceil(days * msgCount));
    $('#subscribe-addmessage').trigger('change');

    return false;
});

//Сохранение и оплата
$('#subscribe_form').on('beforeSubmit', function () {
    //перед отправкой обновляем рекапчу, чтобы в случае ошибки
    //можно было заново ее выполнить
    if (window.grecaptcha)
        grecaptcha.reset();

    var $form = $(this);

    var submitBtn = $(".submitBtn");
    submitBtn.each(function () {
        $(this).buttonLoader('start');
    });

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        data: $(this).serialize(),
        dataType: 'json',
        success: function (resp) {
            if (resp.payForm) {
                $(resp.payForm).appendTo('body').submit();
            }

            if (resp.redirect) {
                window.location.href = resp.redirect;
            }

            if (resp.saved) {
                swal.fire('Успех', resp.saved, 'success');
            }
        },
        error: function (resp) {
            var error = resp.responseJSON;

            if (error.type == 'params') {
                //showError($('#subscribe_form'), error.params);
                $form.yiiActiveForm('updateMessages', error.params, true);
            }
            if (error.type == 'msg') {
                swal.fire('Ошибка', error.msg, 'error');
            }
        },
        complete: function (resp) {
            var res = resp.responseJSON;
            $('[name="_csrf"]').val(res.csrf);
        }
    })
        .always(function () {
            submitBtn.each(function () {
                $(this).buttonLoader('stop');
            })
        });

    return false;
});

function showError(form, errMsg) {
    for (var prop in errMsg) {
        if (!errMsg[prop].length) continue;

        var field = form.find('.field-' + prop);
        field.addClass('has-error');

        field.find('.help-block').text(errMsg[prop][0]);

        //recaptcha
        if (prop == 'loginform-recaptcha') {
            form.find('.field-loginsignup-recaptcha').show();
        }
    }
}

$('#subscribe_form').on('afterValidate', function (f, a) {
    showError($('#subscribe_form'), a);
});

$('#editPhone').click(function () {
    var btn = $(this);
    var phoneInput = $('#subscribe-phone');

    if (!btn.hasClass('save')) {
        btn.data('oldText', btn.text());
        btn.text('Сохранить');
        btn.addClass('save');
        phoneInput.prop('disabled', false);
        phoneInput.focus();

        //чтобы фокус был в конце
        var phone = phoneInput.val();
        phoneInput.val('');
        phoneInput.val(phone);
    } else {
        $.post({
            url: '/sub/default/editphone/',
            data: {phone: phoneInput.val()},
            dataType: 'json',
            success: function (resp) {
                if (!resp.error) {
                    btn.text(btn.data('oldText'));
                    phoneInput.prop('disabled', true);
                    btn.removeClass('save');

                    swal.fire('Успех', 'Новый номер успешно сохранен', 'success');
                } else
                    swal.fire('Ошибка', resp.msg, 'error');
            },
            error: function (resp) {
                swal.fire('Ошибка', resp.statusText, 'error');
            }
        });
    }

    return false;
});

$('#editEmail').click(function () {
    var btn = $(this);
    var emailInput = $('#subscribe-email');

    if (!btn.hasClass('save')) {
        btn.data('oldText', btn.text());
        btn.text('Сохранить');
        btn.addClass('save');
        emailInput.prop('disabled', false);
        emailInput.focus();

        //чтобы фокус был в конце
        var phone = emailInput.val();
        emailInput.val('');
        emailInput.val(phone);
    } else {
        $.post({
            url: '/sub/default/editemail/',
            data: {email: emailInput.val()},
            dataType: 'json',
            success: function (resp) {
                if (!resp.error) {
                    btn.text(btn.data('oldText'));
                    emailInput.prop('disabled', true);
                    btn.removeClass('save');

                    swal.fire('Успех', 'Новый E-Mail успешно сохранен', 'success');
                } else
                    swal.fire('Ошибка', resp.msg, 'error');
            },
            error: function (resp) {
                swal.fire('Ошибка', resp.statusText, 'error');
            }
        });
    }

    return false;
});

//При нажатии кнопки оплаты запоминаем тип оплаты
$('.payBtn').click(function () {
    $('input[name="payType"').val($(this).data('type'));
});
