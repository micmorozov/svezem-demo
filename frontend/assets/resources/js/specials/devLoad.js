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

axios.get('/rest/app-data/')
    .then((resp) => {
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
    });