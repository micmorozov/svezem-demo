function gMapRoute(selectorMapId, origin, destination) {
    var directionsService = new google.maps.DirectionsService;
    var directionsDisplay = new google.maps.DirectionsRenderer;

    var map = new google.maps.Map(document.getElementById(selectorMapId));

    directionsDisplay.setMap(map);

    directionsService.route({
        origin: origin,
        destination: destination,
        travelMode: 'DRIVING'
    }, function (response, status) {
        if (status === 'OK') {
            directionsDisplay.setDirections(response);
        } else {
            //window.alert('Directions request failed due to ' + status);
        }
    });
}

function gMapMarker(selectorMapId, address, zoom) {
    zoom = zoom || 10;

    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({
        'address': address
    }, function (results, status) {
        if (status === 'OK') {
            var coord = {
                lat: results[0].geometry.location.lat(),
                lng: results[0].geometry.location.lng()
            };

            var map = new google.maps.Map(document.getElementById(selectorMapId), {
                zoom: zoom,
                center: coord
            });

            var marker = new google.maps.Marker({
                position: coord,
                map: map
            });
        }
    })
}

var cargoBlockHtml = '<a href="{{=it.link}}" target="_blank" style="color: black">'+
    '<span style="display:block;border-bottom:1px dashed grey; margin-bottom: 6px;padding-bottom: 6px">'+
        '<img style="height: 18px;" src="/img/flags/{{=it.countyTo}}.svg" alt="{{=it.countyTo}}">'+
        '<span style="line-height: 18px;font-size: 16px;"><b>{{=it.cityTo}}</b></span><br />'+
        '<span style="color: #a67c52;">{{=it.category}}</span><br />'+
    '</span>'+
'</a>';

var clusteWrapperHtml =
    '<div style="border-bottom: 1px dashed grey; margin-bottom: 6px;padding-bottom: 6px;">'+
        '<b>{{=it.mainCity}}</b> <a href="{{=it.cargoInCity}}" style="float: right;" target="_blank">Все грузы</a>'+
    '</div>'+
    '<div>'+
        '{{~it.balloons :value}}'+
        '{{=value}}'+
        '{{~}}'+
    '</div>'
;

class gMapManager {

    constructor(selector) {
        this.isSupport = false;

        try {
            this.map = new google.maps.Map(document.getElementById(selector), {
                minZoom: 2,
                maxZoom: 18,
                clickableIcons: false // Блокируем показ стандартных гугловских подсказок к разным объектам
            });
            this.isSupport = true;
        }catch(e){
            return ;
        }

        //маршрут
        this.directionsDisplay = new google.maps.DirectionsRenderer;
        this.directionsDisplay.setMap(this.map);

        //Дополняем ф-цией отображения балуна с информацией
        google.maps.Marker.prototype.cargoBalloon = function () {
            let tmpl = doT.template(cargoBlockHtml);
            return tmpl(this.info);
        };

        //загружена ли информация маркера
        google.maps.Marker.prototype.isLoadInfo = function () {
            return this.info.loaded;
        };

        this.markerCollection = [];
        var that = this;

        //загрузить информацию маркера
        google.maps.Marker.prototype.load = function () {
            return new Promise((resolve, reject) => {
                if (this.isLoadInfo()) {
                    resolve();
                } else {
                    that.loadMarkersInfo([this.info.id]).then(resolve, reject);
                }
            });
        };

        //загрузить информацию кластера
        Cluster.prototype.load = function () {
            return new Promise((resolve, reject) => {
                var markers = this.getMarkers();

                var ids = [];
                for (var i = 0; i < markers.length; i++) {
                    var marker = markers[i];
                    if (!marker.isLoadInfo())
                        ids.push(marker.info.id);
                }

                if (ids.length)
                    that.loadMarkersInfo(ids).then(resolve, reject);
                else
                    resolve();
            });
        };

        //окно вывода информации маркера/кластера
        this.infowindow = new google.maps.InfoWindow({
            maxWidth: 415
        });
    }

    route(origin, destination) {
        let directionsService = new google.maps.DirectionsService;

        directionsService.route({
            origin: origin,
            destination: destination,
            travelMode: 'DRIVING'
        }, (response, status) => {
            if (status === 'OK') {
                this.directionsDisplay.setDirections(response);
            }
        });
    }

    geodecoder(address) {
        return new Promise((resolve, reject) => {
            var geocoder = new google.maps.Geocoder();

            geocoder.geocode({
                'address': address
            }, (results, status) => {
                if (status == 'OK') {
                    resolve({
                        lat: results[0].geometry.location.lat(),
                        lng: results[0].geometry.location.lng()
                    });
                } else {
                    reject();
                }
            });
        });
    }

    createMarker(address, zoom) {
        zoom = zoom || 10;

        this.geodecoder(address).then((coord) => {
            new google.maps.Marker({
                position: coord,
                map: this.map
            });
        });
    }

    //ф-ция делает запрос на сервер для получения координат всех точек груза
    showAllCargo() {
        $.ajax({
            url: "/cargo/default/cargo-map/",
            success: (str) => {
                var arr = str.split(';');
                var markers = arr.map(function (item) {
                    var info = item.split('|');
                    return {
                        id: info[0],
                        coord: {
                            lat: parseFloat(info[1]),
                            lng: parseFloat(info[2])
                        }
                    };
                });
                this.buildCargoMarkers(markers);
            }
        });
    }

    //построение карты с точками. Кластеризация точек
    buildCargoMarkers(markers) {
        //очищаем карту от маркеров
        if (this.markerCluster)
            this.markerCluster.clearMarkers();

        this.markerCollection = [];

        let bounds = new google.maps.LatLngBounds();

        let cluster = markers.map((markerItem) => {
            let marker = new google.maps.Marker({
                position: markerItem.coord,
                info: markerItem
            });

            let that = this;
            marker.addListener('click', function () {
                marker.load().then(() => {
                    that.showClusterWrapper.call(that, [marker], marker.getPosition());
                })
            });

            bounds.extend(marker.getPosition());

            //добавление маркеров в коллекцию
            this.markerCollection[marker.info.id] = marker;

            return marker;
        });

        this.map.fitBounds(bounds);

        this.markerCluster = new MarkerClusterer(this.map, cluster,
            {
                imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
                maxZoom: 18,
                gridSize: 40
            });

        google.maps.event.addListener(this.markerCluster, 'clusterclick', (cluster) => {
            if (this.map.getZoom() >= 5)
                cluster.load();

            if (this.map.getZoom() == this.markerCluster.getMaxZoom()) {
                let markers = cluster.getMarkers();

                let copymarkers = [];
                for (let i = 0; i < markers.length; i++) {
                    copymarkers.push(markers[i]);
                }

                cluster.load().then(() => {
                    this.showClusterWrapper(copymarkers, cluster.getCenter());
                });
            }
        });
    }

    showClusterWrapper(markers, postions) {
        let balloons = markers.map(function (marker) {
            return marker.cargoBalloon();
        });

        var firstMarker = markers[0];

        let tmpl = doT.template(clusteWrapperHtml);
        let content = tmpl({
            mainCity: markers[0].info.cityFrom,
            balloons: balloons,
            cargoInCity: '/cargo/search/?CargoSearch%5BlocationFrom%5D=' + firstMarker.info.cityFromId + '&CargoSearch%5BlocationFromType%5D=city'
        });

        this.infowindow.setContent(content);
        this.infowindow.setPosition(postions);
        this.infowindow.open(this.map);

    }

    //получение информации о грузах по для указанных ИД
    loadMarkersInfo(ids) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: "/cargo/default/cargo-map-details/",
                data: 'ids=' + ids,
                dataType: "json",
                success: (markers) => {
                    for (var i = 0; i < markers.length; i++) {
                        var info = markers[i];
                        var marker = this.markerCollection[info.id];
                        marker.info = info;
                        marker.info.loaded = 1;
                    }
                    resolve();
                },
                error: reject
            });
        });
    }
}
