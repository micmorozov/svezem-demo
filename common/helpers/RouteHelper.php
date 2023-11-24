<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 25.04.18
 * Time: 13:24
 */

namespace common\helpers;

use common\models\Cargo;
use common\models\CargoLocation;
use common\models\City;
use console\jobs\CargoRoute;
use Redis;
use Yii;

class RouteHelper
{
    //получаем точки растояние между которыми не менее заданной величины
    const DISTANCE_BETWEEN_POINT = 50;

    /**
     * @return Redis
     */
    static public function getRedis()
    {
        return Yii::$app->redisGeo;
    }

    static protected function getRouteKey($from, $to){
        return 'route:'.$from.'_'.$to;
    }

    /**
     * Построение маршрута
     * @param $city_from - ИД города отправки
     * @param $city_to - ИД города назначения
     * @return bool
     */
    static public function buildRoute($city_from, $city_to){
        //ключ маршрута
        $redisKey = self::getRouteKey($city_from, $city_to);

        $redis = self::getRedis();

        //если маршрут уже существует, то ничего не делаем
        if( $redis->exists($redisKey) )
            return true;

        $cityFrom = City::findOne($city_from);
        $cityTo = City::findOne($city_to);

        //полученеи маршрута по заданным координатам
        $route = Osrm::getRoute($cityFrom->longitude, $cityFrom->latitude, $cityTo->longitude, $cityTo->latitude);

        if( !$route )
            return false;

        $coordinates = $route->getRoutes()[0]['geometry']['coordinates'];

        $points[] = $coordinates[0];

        $last = $coordinates[0];
        $count = count($coordinates);

        for($i=1; $i<$count; $i++){
            $currentPoint = $coordinates[$i];

            //расчитываем расстояние между точками
            $distance = Utils::calculateTheDistance($last[1], $last[0], $currentPoint[1], $currentPoint[0]);
            $distance /= 1000;

            //если точки ближе установленного, то пропускаем
            if( $distance < self::DISTANCE_BETWEEN_POINT ) continue;

            //если расстояние превышает установленное в 2 раза,
            //то необходимо построить промежуточные
            if( $distance > self::DISTANCE_BETWEEN_POINT*2 ){
                $extraPoints = self::getPointsBetween($last, $currentPoint, $distance, self::DISTANCE_BETWEEN_POINT);

                foreach($extraPoints as $extraPoint){
                    $points[] = $extraPoint;
                }
            }

            $last = $currentPoint;
            $points[] = $currentPoint;
        }

        $points[] = $coordinates[$count-1];

        foreach($points as $index => $point){
            $redis->geoAdd($redisKey, $point[0], $point[1], $index);
        }

        return true;
    }

    /**
     * Ключ списка ИД попутных грузов по указанному маршруту и радиусу
     * @param integer $from
     * @param integer $to
     * @param integer $radius
     * @return string
     */
    static protected function getPassingCargoByRouteKey($from, $to, $radius){
        return 'passingCargoByRoute:'.$from.'_'.$to.'_'.$radius;
    }

    /**
     * Получение списка попутных грузов
     * или его построение в случае отсутствия
     * @param $from
     * @param $to
     * @param $radius
     * @param array $options
     * @return array
     */
    static public function getPassingCargo($from, $to, $radius, $options = []){
        $defaultOptions = [
            'build' => false,
            'ttl' => 60*60*6//6 часов
        ];

        $options = array_merge($defaultOptions, $options);

        $redis = self::getRedis();
        $passingKey = self::getPassingCargoByRouteKey($from, $to, $radius);

        //если нет ключа и передана опция построить
        if( !$redis->exists($passingKey) && $options['build'] ){
                self::buildPassingCargo($from, $to, $radius, $options['ttl']);
        }

        return $redis->sMembers($passingKey);
    }

    static protected function buildPassingCargo($from, $to, $radius, $ttl){
        $keyRoute = self::getRouteKey($from, $to);

        $ids = self::detectPassingCargo($keyRoute, $radius);

        //если не удалось найти попутные грузы, то записываем индекс -1
        //чтобы создать ключ, и чтобы при повторных запросах выдавать имеющийся результат
        if( empty($ids) ){
            $ids[] = -1;
        }

        $redis = self::getRedis();
        $passingKey = self::getPassingCargoByRouteKey($from, $to, $radius);
        foreach($ids as $id){
            $redis->sAdd($passingKey, $id);
        }
        $redis->expire($passingKey, $ttl);

        return $ids;
    }

    /**
     * Поиск попутных грузов
     * @param $routeKey - ключ маршрута
     * @param $radius - радиус отклонения от маршрута
     * @return array - список ид попутных грузов
     */
    static protected function detectPassingCargo($routeKey, $radius){
        $redis = self::getRedis();

        //получаем маршрут искомого груза
        $route = $redis->geoRadiusByMember($routeKey, 0, 999999, 'km', ['withcoord']);

        //отсортируем по индексу
        usort($route, function($item1, $item2){
            return $item1[0] > $item2[0];
        });

        //и преобразуем в удобный массив
        $route = array_map(function($item){
            return $item[1];
        }, $route);

        $rand = rand(1, 99999);
        $startIdsKey = 'startIds_'.$rand;
        $startIdsUnionKey = 'startIdsUnion_'.$rand;

        $finishIdsKey = 'finishIds_'.$rand;

        $interIds = 'interIds_'.$rand;
        $interIdsUnion = 'interIdsUnion_'.$rand;

        foreach($route as $indexRoute => $routePoint){
            if( $indexRoute > 0 ){
                //получаем ид грузов, у которых маршрут заканчивается в этой точке
                //$redis->geoRadius(CargoRoute::REDIS_CARGO_MAP_FINISH, $routePoint[0], $routePoint[1], $radius, 'km', ['store' => $finishIdsKey]);
                // TODO как только в библиотеке phpredis появится апдейт к georadius с возможностью сохранить результат в ключе, надо заменить эту функцию
                $redis->rawCommand('georadius', CargoRoute::REDIS_CARGO_MAP_FINISH, $routePoint[0], $routePoint[1], $radius, 'km', 'store', $finishIdsKey);

                //пересекая ид стартовавших и завершивших, получаем ид попутных грузов
                $redis->zInterStore($interIds, [$startIdsUnionKey, $finishIdsKey]);
                //объединяем с уже найденными ид
                $redis->zUnionStore($interIdsUnion, [$interIdsUnion, $interIds]);
            }

            //из точки маршрута получаем ид грузов, сохраняем в отдельный ключ
            //$redis->geoRadius(CargoRoute::REDIS_CARGO_MAP_START, $routePoint[0], $routePoint[1], $radius, 'km', ['store'=>$startIdsKey]);
            $redis->rawCommand('georadius', CargoRoute::REDIS_CARGO_MAP_START, $routePoint[0], $routePoint[1], $radius, 'km', 'store', $startIdsKey);
            //далее добавляем эти ид в общий список ид грузов
            $redis->zUnionStore($startIdsUnionKey, [$startIdsUnionKey, $startIdsKey]);
        }

        $cargoIds = $redis->zRange($interIdsUnion, 0, -1);

        $redis->del($startIdsKey, $startIdsUnionKey, $finishIdsKey);
        $redis->del($interIds, $interIdsUnion);

        return $cargoIds;
    }

    /**
     * Ф-ция проверяет входит ли конечная точка маршрута груза cargo_id в маршрут $redisKeyMainRoute
     * и возвращает его индекс
     * @param $redisKeyMainRoute
     * @param $redisKeyCargoRoute
     * @param $radius
     * @return bool
     */
    static protected function getFinishIndexInRoute($redisKeyMainRoute, $redisKeyCargoRoute, $radius){
        $redis = self::getRedis();

        /**
         * Получаем последнюю координату из маршрута.
         * т.к. мы знаем что они проиндексированы по порядку
         * достаточно получить размер ключа и найти в радиусе 0
         */
        $lastMemberInRoute = $redis->zCard($redisKeyCargoRoute);
        $finish_coord = $redis->geoRadiusByMember($redisKeyCargoRoute, $lastMemberInRoute-1, 0, 'km', ['withcoord']);

        if( !isset($finish_coord[0]) )
            return false;

        $finish_coord = $finish_coord[0][1];

        //ищим эту точку в маршруте искомого груза
        $res = $redis->geoRadius($redisKeyMainRoute, $finish_coord[0], $finish_coord[1], $radius, 'km');

        return isset($res[0])?$res[0]:false;
    }

    /**
     * Промежуточные точки
     * @param $point1
     * @param $point2
     * @param $dist
     * @param $radius
     * @return array
     */
    static protected function getPointsBetween($point1, $point2, $dist, $radius){
        //делим участок между точками на сегменты
        $segmentCount = ceil($dist/$radius);

        list($k, $b) = self::getLineCoef($point1, $point2);

        //определяем длину одного сегмента
        $delta = ($point2[0]-$point1[0])/$segmentCount;

        //по найденным коэффициентам определяем промежуточные точки
        $points = [];
        for($i=1;$i<$segmentCount;$i++){
            $newLong = $point1[0]+$delta*$i;
            $newLat = $k*$newLong+$b;
            $points[] = [$newLong, $newLat];
        }

        return $points;
    }

    /**
     * Нахождение коэффициентов прямой проходящей через 2 точки
     * f(x)=kx+b
     * @param $point1
     * @param $point2
     * @return array [$k, $b]
     */
    static protected function getLineCoef($point1, $point2){
        $k = ($point2[1]-$point1[1])/($point2[0]-$point1[0]);
        $b = $point1[1]-$k*$point1[0];

        return [$k,$b];
    }
}