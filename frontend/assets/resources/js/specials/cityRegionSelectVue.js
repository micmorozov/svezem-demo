Vue.component('city-region-select', {
    template:
        '<div :class="classBlock">'+
            '<label class="form-label" style="min-width: 60px;">{{label}}:</label>'+
            '<select style="width: 100%"></select>'+
        '</div>',
    props: ['classBlock', 'options', 'city', 'type', 'label', 'errorMsg'],
    watch: {
        //Для отображения красной рамочки в случае ошибки
        errorMsg: function (val) {
            var selectEl = $(this.$el).find('select');
            if (val) {
                selectEl.next().addClass('has-error');
            } else {
                selectEl.next().removeClass('has-error');
            }
        }
    },
    mounted: function () {
        var vm = this;

        var selectEl = $(this.$el).find('select');

        selectEl.select2({
                data: this.options,
                allowClear: true,
                theme: 'bootstrap',
                minimumInputLength: 3,
                language: "ru",
                ajax: {
                    url: '/city/search-list/',
                    dataType: 'json',
                    data: function (params) {
                        return {query: params.term};
                    },
                    processResults: function (data) {
                        return {results: data};
                    },
                    delay: 250,
                    cache: true
                },
                templateResult: function(data) {
                    if (data.region === undefined) return data.text;
                    return $('<b>').text(data.text);
                },
                placeholder: 'Выберите город'
            })
            .val(this.city)
            // emit event on change.
            .on('change', function () {
                vm.$emit('update:city', this.value);

                var data = selectEl.select2('data')[0];
                if( data == undefined ) return;

                vm.$emit('update:type', data.type || 'city');
            });


        //Все города
        selectEl.on('select2:open', function (event) {
            $('.select2-search--dropdown .all').remove();

            var selectAll = $('<a>')
                .attr('href', '#')
                .addClass('all btn-sm btn-primary')
                .css('display', 'inline-block')
                .css('background-color', 'rgb(62, 153, 221)')
                .css('margin-top', '4px')
                .text('Все города');

            selectAll.click(function (e) {
                if (!selectEl.find('option[value=all]').length) {
                    var newOption = $('<option>').val('all').prop('selected', false).text('Все города');

                    selectEl.append(newOption);
                }
                selectEl.val('all').trigger('change');
                selectEl.select2("close");
            });

            $('.select2-search--dropdown').prepend(selectAll);
            selectAll.siblings('input').css('margin-top', '10px');
        });
    },
    methods: {
        clear: function() {
            var selectEl = $(this.$el).find('select');
            selectEl.empty().trigger("change");
        }
    }
});
