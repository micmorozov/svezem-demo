Vue.component('multi-select', {
    template:
        '<div :class="classBlock">' +
        '<label>{{label}}:</label>' +
        '<div class="form-group"><a href="javascript:" class="btn btn-sm btn-info" @click="selectAll">Выбрать все</a>&nbsp;<a class="btn btn-sm btn-info" v-if="selected.length" href="javascript:" @click="clear">Очистить</a></div>' +
        '<select style="width:100%" multiple="multiple"><slot></slot></select>' +
        '<div class="help-block"></div>' +
        '</div>',
    props: ['selected', 'classBlock', 'label', 'errorMsg', 'placeholder'],
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

        selectEl.val(this.selected);
        selectEl.select2({
            placeholder: vm.placeholder,
            theme: 'bootstrap',
            style: 'width:100%',
            closeOnSelect: false
        });
        selectEl.on('change', () => {
            this.updateValue();
        });

        // $("#checkbox").click(() => {
        //     console.log($("#checkbox").is(':checked'), selectEl);
        //     if ($("#checkbox").is(':checked')) {
        //
        //     } else {
        //         $("option", selectEl).removeAttr("selected");
        //         this.updateValue();
        //     }
        // });

        // MultipleSel.init(selectEl, {
        //     selectAll: false,
        //     width: '100%',
        //     onClick: this.updateValue,
        //     onCheckAll: this.updateValue,
        //     onUncheckAll: this.updateValue
        // });
    },
    methods: {
        updateValue: function () {
            var selectEl = $(this.$el).find('select');
            this.$emit('update:selected', selectEl.val());
        },
        selectAll: function () {
            let selectEl = $(this.$el).find('select');
            console.log('!', selectEl);
            selectEl.find("option").prop('selected', 'selected');
            selectEl.trigger('change');
        },
        clear: function () {
            let selectEl = $(this.$el).find('select');
            console.log('!!', selectEl);
            selectEl.find("option").each((i,e) => {e.selected = false});
            selectEl.trigger('change');
            // selectEl.multipleSelect('uncheckAll');
        }
    }
});
