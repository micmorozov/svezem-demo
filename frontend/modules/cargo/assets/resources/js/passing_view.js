function cargo_passing_init() {
    var gMap = new gMapManager('cargo-search__map');

    $('body').on('click', '.search__btn', function () {
        passingMap();
    });

    function passingMap() {
        $('#cargo-search__map').show();

        var cityFrom = $('#cargopassing-city_from option:selected').text();
        var cityTo = $('#cargopassing-city_to option:selected').text();

        //отрпавляемзапрос если указаны города
        if (cityFrom != '' && cityTo != '') {
            $.ajax({
                type: 'GET',
                url: '/cargo/map/passing/',
                data: $('#transport-search-form').serialize(),
                dataType: 'json',
                success: function (data) {
                    var route = data.route;
                    gMap.route(route.from, route.to);
                    gMap.buildCargoMarkers(data.markers);
                }
            });
        }
    }

    passingMap();
}
