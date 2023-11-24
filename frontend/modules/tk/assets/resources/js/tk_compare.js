var total_count_tks = 0;
var timeOutId = null;
var socket = null;

var searchData = {
    distance: null,
    days: null,
    detailsCity: null,
    detailsParams: null,
    tks: []
};

Vue.component('tk-item', {
    template: '#tk-search',
    props: ['id', 'name', 'icon', 'cost'],
    computed: {
        iconStyle: function(){
            return {
                'background-image': 'url('+this.icon+')'
            };
        },
        link: function(){
            return "/tk/"+this.id;
        }
    },
    methods: {
        number_format: number_format
    }
});

var compare = new Vue({
    el: '#tkCompare',
    data: function (){
        return searchData;
    },
    computed: {
        filteredList: function(){
            return this.tks.sort((a,b) => a.cost>b.cost?1:-1);
        }
    },
    methods: {
        number_format: number_format
    }
});

function number_format(number, decimals, dec_point = '.', thousands_sep = ' ' ){
    var i, j, kw, kd, km;

    // input sanitation & defaults
    if( isNaN(decimals = Math.abs(decimals)) ){
        decimals = 2;
    }
    if( dec_point == undefined ){
        dec_point = ",";
    }
    if( thousands_sep == undefined ){
        thousands_sep = " ";
    }

    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

    if( (j = i.length) > 3 ){
        j = j % 3;
    } else{
        j = 0;
    }

    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

    return km + kw + kd;
}

//ф-ция контролирует кол-во оставшихся тк
function TotalTkControl(val) {
    val = val || 0;

    if( val > 0 ){
        total_count_tks = val;
        $("#search-btn").buttonLoader('start');
    }
    else if( val == 0){
        total_count_tks = 0;
    }
    else if(val == -1) {
        total_count_tks--;
    }

    //console.log('TotalTkControl', total_count_tks);
    if (total_count_tks <= 0) {
        $("#search-btn").buttonLoader('stop');

        if( timeOutId ) {
            clearTimeout(timeOutId);
            timeOutId = null;
        }
    }
}

function ajaxQuery(){
    $.ajax({
        type: "POST",
        url: "/tk/comparison/search/",
        data: $('#tk_search_form').serialize(),
    });
}

function socketConnect(){
    // если соединение уже установалено, то ничего не делаем
    if(socket && socket.connected) {
        ajaxQuery();
        return true;
    };

    // подключаемся к сокету после нажатия на кнопку Поиск, что бы он не висел просто так
    socket = io(document.location.host);

    //записываем ИД сокета. По нему сервер будет определять
    //в какой сокет отправлять после обработки gearman
    socket.on('set id', function (data) {
        $('#socket_id').val(data);

        ajaxQuery();
    });

    //отображаем расстояние и время
    socket.on('set distance', function (data) {
        searchData.distance = data.distance;
        searchData.days = data.time + '-' + (data.time+1);
    });

    //новый результат поиска
    socket.on('new result', function (data) {
        data = JSON.parse(data);

        //если данные неактуальны, игнорируем
        if( data.session_timestamp != $('#session_timestamp').val() )
            return false;

        searchData.tks.push(data);

        TotalTkControl(-1);
    });

    //не удалось получить данные ТК
    //сигнал нужен для декремента
    socket.on('tk_fail', function (data) {
        TotalTkControl(-1);
    });

    socket.on('total_tk', function (count) {
        TotalTkControl(count);
        //этот сигнал извещает о начале поиска и кол-ве ТК, участвующих в поиске.
        //Может так произойти, что данные поступят не ото всех ТК.
        //Пэтому чтобы разблокировать кнопку поиска устанавливаем таймаут 30 с
        timeOutId = setTimeout(TotalTkControl, 30*1000, 0);
    });
}

setTimeout(function () {
    //обработка кнопки поиска
    $("#search-btn").click(function(){
        //"Костыль" чтобы Yii сделал клиентскую проверку формы
        var $form = $('#tk_search_form'),
            data = $form.data("yiiActiveForm");
        $.each(data.attributes, function() {
            this.status = 3;
        });
        $form.yiiActiveForm("validate");

        //если найдена ошибка, то ничего не делаем
        if( $form.find(".has-error").length > 0 )
            return false;

        //Очищаем список найденых ТК
        searchData.tks = [];

        //показываем блок с параметрами поиска
        var city_from = $('#tkcomparesearch-city_from option:selected').text();
        var city_to = $('#tkcomparesearch-city_to option:selected').text();
        var weight = $('#tkcomparesearch-weight').val();
        var depth = $('#tkcomparesearch-depth').val();
        var height = $('#tkcomparesearch-height').val();
        var width = $('#tkcomparesearch-width').val();
        $('.transportation__head-desc .details').html();
        searchData.detailsCity = city_from+' - '+city_to;
        searchData.detailsParams = 'Масса(кг): '+weight+'. Габариты(м): '+depth+' x '+height+' x '+width;

        //сохраняем время отправки на сервер.
        //Gearman может долго отрабатывать и присланные данные
        //могут быть неактуальными
        var session_timestamp = new Date().getTime();
        $('#session_timestamp').val(session_timestamp);

        socketConnect();

        return false;
    });
}, 500);