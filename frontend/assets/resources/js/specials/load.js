//подгрузка скрипта
function include(file) {
    return new Promise(resolve => {
        var tag = document.createElement('script');
        tag.type = 'text/javascript';

        if(typeof(file) == "object"){
            tag.src = file.src;
            for (let key in file.data) {
                tag.dataset[key] = file.data[key];
            }
        }else
            tag.src = file;

        tag.onload = resolve;
        document.head.appendChild(tag);
    });
}

function loadJsList(scripts) {
    var p = Promise.resolve();
    scripts.forEach(file => {
        p = p.then(() => include(file));
    });
}

//Общие данные
var commonVueData = {
    isGuest: true,
    yourCity: '',
    //cargoBooking: false,
    cabinetMenu: [],
    mainDomain: '',
    selectedDomain: '',
    cargoForm: {
        id: null
    },
    version: 1
};

/**
 * Ф-ция получает данные с сервера
 * и подгружает скрипты, указанные в МЕТА-теге
 *
 * @returns {Promise<void>}
 */
function getAppData() {

    axios.get('/rest/app-data/')
        .then((resp) => {
            if(resp.data.js) loadJsList(resp.data.js);

            commonVueData.isGuest = resp.data.vue.isGuest;
            commonVueData.yourCity = resp.data.vue.yourCity;
            //commonVueData.cargoBooking = resp.data.vue.cargoBooking;
            commonVueData.cabinetMenu = resp.data.vue.cabinetMenu;
            commonVueData.mainDomain = resp.data.vue.mainDomain;
            commonVueData.selectedDomain = resp.data.vue.selectedDomain;
            //cargoForm
            commonVueData.cargoForm = resp.data.cargoForm;
            //csrf
            commonVueData.csrf = resp.data.app.csrf;
            commonVueData.version = resp.data.app.version;

            //csrf для axios
            axios.defaults.headers.common = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': resp.data.app.csrf
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': resp.data.app.csrf
                }
            });

            //Подгрузка скриптов
            var js = $("meta[name='load-list']").attr('content');
            if (!js) return;
            var scripts = js.split(",");

            //Дописываем к файлам версию
            //если он не содержит параметров
            scripts = scripts.map(file => {
                var parser = document.createElement('a');
                parser.href = file;
                if (parser.search == '') {
                    return file + '?v=' + commonVueData.version;
                } else {
                    return file;
                }

            });

            loadJsList(scripts);
        });
}

getAppData();