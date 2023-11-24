if (!window.CargoCarriage) {
    window.CargoCarriage = {
        oneCity: false,
        category_id: null
    };
}
new Vue({
    el:'#cargoCarriage',
    data: {
        cityFrom: null,
        cityTo: null,
        description: '',
        phone: '+7',
        descriptionErr: null,
        cityFromErr: null,
        sending: false,
        phoneErr: null,
        oneCity: CargoCarriage.oneCity,
        oneCitySelect: CargoCarriage.oneCity,
        category_id: CargoCarriage.category_id
    },
    computed: {
        cargoForm: function(){
            return commonVueData.cargoForm;
        },
        formErr: function () {
            return (this.cityFromErr || this.descriptionErr || this.phoneErr) ? 'Исправьте ошибки в форме' : false;
        }
    },
    watch: {
        description: function () {
            this.validatedescription();
        },
        cityFrom: function () {
            this.validateCityFrom();
        },
        phone: function () {
            this.validatePhone();
        },
        cargoForm: function () {
            this.setCityFrom();
        }
    },
    methods: {
        send: function () {
            var valid1 = this.validatedescription();
            var valid2 = this.validateCityFrom();
            var valid3 = this.validatePhone();
            if (valid1 && valid2 && valid3) {
                this.sending = true;

                var bodyFormData = new FormData();
                bodyFormData.append('CargoCarriageModel[cityFrom]', this.cityFrom);
                bodyFormData.append('CargoCarriageModel[cityTo]', this.oneCity ? this.cityFrom : (this.cityTo || ''));
                bodyFormData.append('CargoCarriageModel[description]', this.description);
                bodyFormData.append('CargoCarriageModel[phone]', this.phone);
                bodyFormData.append('CargoCarriageModel[category_id]', this.category_id || '');


                axios.post('/cargo/default/shortcreate/', bodyFormData)
                    .then((resp)=>{
                        var data = resp.data;

                        if( data.redirect ){
                            window.location.href = data.redirect;
                            return ;
                        }

                        for (let prop in data) {
                            let attr = prop.replace("cargocarriagemodel-", "");
                            this[attr + 'Err'] = data[prop][0];
                        }

                        // Если есть сообщение, которое надо показать во всплывающем окне
                        if(data['showAlert']) {
                            //Окно удаления
                            Swal.fire({
                                title: 'Повторная заявка!',
                                html: data['showAlert'].pop(),
                                type: 'warning',
                                confirmButtonText: 'Договорились!'
                            });
                        }
                    })
                    .finally(() => {
                        this.sending = false;
                    });
            }
        },
        validateCityFrom: function () {
            var res = false;
            this.cityFromErr = !this.cityFrom ? 'Укажите город отправки' : '';
            if (this.cityFromErr) {
                $('#cargocarriagemodel-cityfrom').next().addClass('has-error');
            } else {
                $('#cargocarriagemodel-cityfrom').next().removeClass('has-error');
                res = true;
            }

            return res;
        },
        validatedescription: function () {
            var res = false;
            this.descriptionErr = this.description.trim().length == 0 ? 'Заполните указанное поле' : '';

            if (!this.descriptionErr)
                res = true;

            return res;
        },
        validatePhone: function () {
            var res = false;
            if (this.phone.length < 4) {
                this.phoneErr = 'Номер телефона указан некорректно';
            } else {
                this.phoneErr = '';
                res = true;
            }

            return res;
        },
        oneCityChange: function () {
            this.oneCity = !this.oneCity;
        },
        setCityFrom: function () {
            if (this.cargoForm.id) {
                var newOption = new Option(this.cargoForm.title, this.cargoForm.id, false, false);
                $('#cargocarriagemodel-cityfrom').append(newOption);//.trigger('change');

                this.cityFrom = this.cargoForm.id;
            }
        }
    },
    mounted: function () {
        var cFrom = $('#cargocarriagemodel-cityfrom').select2({
            allowClear: true,
            minimumInputLength: 3,
            theme: 'bootstrap',
            ajax: {
                url: '/city/list/',
                dataType: "json",
                data: function (params) {
                    return {query: params.term};
                },
                processResults: function (data) {
                    return {results: data};
                },
                delay: 250,
                cache: true
            },
            "placeholder": "Выберите город"
        });

        this.setCityFrom();

        var cTo = $('#cargocarriagemodel-cityto').select2({
            allowClear: true,
            minimumInputLength: 3,
            theme: 'bootstrap',
            ajax: {
                url: '/city/list/',
                dataType: "json",
                data: function (params) {
                    return {query: params.term};
                },
                processResults: function (data) {
                    return {results: data};
                },
                delay: 250,
                cache: true
            },
            "placeholder": "Выберите город"
        });

        var selectDropDown = $(".select-drop-down").select2();

        cFrom.on('select2:select', (e) => {
            this.cityFrom = $('#cargocarriagemodel-cityfrom').val();
        });
        cFrom.on("select2:unselecting", () => {
            this.cityFrom = null;
        });

        cTo.on('select2:select', (e) => {
            this.cityTo = $('#cargocarriagemodel-cityto').val();
        });
        cTo.on("select2:unselecting", () => {
            this.cityTo = null;
        });

        //=== Phone ===
        ////// Phone flags
        $("input[type='tel']").intlTelInput({
            allowExtensions: true,
            autoFormat: false,
            autoHideDialCode: false,
            autoPlaceholder: false,
            defaultCountry: "auto",
            ipinfoToken: "yolo",
            nationalMode: false,
            numberType: "MOBILE",
            preventInvalidNumbers: true,
            //нельзя ставить auto это вызывает события на другие поля формы
            initialCountry: "ru",
            geoIpLookup: function (callback) {
                $.get('/geo/country/', function () {
                }, "json").always(function (resp) {
                    var countryCode = (resp && resp.code) ? resp.code : "ru";
                    callback(countryCode);
                });
            }
        });

        $("input[type='tel']").keydown(function (e) {
            if ($("input[type='tel']").val().length == 1) {
                if (e.keyCode == 8 || e.keyCode == 46) {
                    e.preventDefault();
                }
            }
        });

        $("input[type='tel']").keydown(function (e) {
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                // Allow: Ctrl+A, Command+A
                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                // let it happen, don't do anything
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        var self = this;
        $("input[type='tel']").on("countrychange", function () {
            self.phone = $(this).val()
        });
    }
});
