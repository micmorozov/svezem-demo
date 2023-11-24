var PromoPayment = {
    start: function () {
        $('.promo-option').each(function (i) {
            $(this).on('change', function () {
                PromoPayment.calculateTotal();
            });
        });

        $('.promo-payment__item-price-value').each(function () {
            if (this.tagName.toLowerCase() === 'select') {
                $(this).on('change', function () {
                    PromoPayment.calculateTotal();
                });
            }
        });

        PromoPayment.calculateTotal();

        $('.pay-button').click(function (event) {
            event.preventDefault();

            var form = $(this).closest('form');

            //Получить ID тарифов отмеченных галочкой
            var rates = [];
            $('.promo-payment__item').each(function (i) {
                var that = this;
                $('.promo-option', this).each(function () {
                    if ($(this).is(":checked")) {
                        rates.push($("[name='rates']", that).val());
                    }
                });
            });

            var data = {rates: rates};
            data.item_id = $('[name=item_id]').val();

            $(this).buttonLoader('start');
            var that = $(this);

            data.payType = $(this).data('pay') || 'card';

            $.post({
                url: form.attr('action'),
                data: data,
                dataType: 'json',
                success: function (resp) {
                    if (resp.form) {
                        $(resp.form).appendTo('body').submit();
                    } else if (resp.redirect) {
                        window.location.href = resp.redirect;
                    }
                },
                error: function (resp) {
                    swal.fire('Ошибка', resp.responseJSON.msg, 'error');
                }
            })
                .always(function () {
                    that.buttonLoader('stop');
                });
        });
    },

    calculateTotal: function () {
        var total = 0;
        $('.promo-payment__item').each(function (i) {
            var item = this;
            $('.promo-option', this).each(function () {
                var price = 0;
                $('.promo-payment__item-price-value', item).each(function () {

                    if (this.tagName.toLowerCase() === 'select') {
                        price = $(this).find(':selected').data('price');
                    } else {
                        price = $(this).data('price');
                    }

                    $('.price_for_service', item).each(function () {
                        $(this).html(price + '&nbsp;р.');
                    });
                });

                if ($(this).is(":checked")) {
                    total += price;
                }
            });

        });

        $('.promoTotalPrice').html(total);

        var payBtn = $('.pay-button');

        if (total === 0) {
            payBtn.attr('disabled', true);
        } else if (payBtn.attr('data-btn-text')) {
            payBtn.attr('disabled', true);
        } else {
            payBtn.attr('disabled', false);
        }
    }
};
PromoPayment.start();
