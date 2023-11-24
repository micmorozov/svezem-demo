function cargo_view_init() {
    var gMap = new gMapManager('route-map');

    if (gMap.isSupport) {
        $.ajax({
            type: 'GET',
            url: '/cargo/map/passing-for-cargo/',
            data: 'id=' + cargoViewID,
            dataType: 'json',
            success: function (data) {
                var route = data.route;
                gMap.route(route.from, route.to);
                gMap.buildCargoMarkers(data.markers);
            }
        });
    }
}
