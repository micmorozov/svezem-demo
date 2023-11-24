Vue.component('gmap', {
    template: '<div :id="id"></div>',
    props: ['address', 'zoom'],
    data () {
        return {
            id: null
        }
    },
    mounted: function () {
        this.id = 'gMap'+this._uid;
        gMapMarker(this.id, this.address, this.zoom);
    }
});

new Vue({
    el: '#contactVue'
});