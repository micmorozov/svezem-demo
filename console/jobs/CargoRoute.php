<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 20.04.18
 * Time: 12:35
 */

namespace console\jobs;

use common\helpers\RouteHelper;
use common\models\Cargo;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use Redis;
use Yii;

class CargoRoute extends JobBase
{
    //Ключ с координатами места отправки грузов
    const REDIS_CARGO_MAP_START = 'cargo_map_start';

    //Ключ с координатами места доставки грузов
    const REDIS_CARGO_MAP_FINISH = 'cargo_map_finish';

    //Ключ с координатами места отправки ВСЕХ грузов
    const REDIS_CARGO_LOCATION = 'cargoLocation';

    public function execute(GearmanJob $job = null)
    {
        $workload = $this->getWorkload($job);
        if ( !$workload) {
            return;
        }

        $cargo_id = $workload['cargo_id'];

        $cargo = Cargo::findOne($cargo_id);

        if ( !$cargo) {
            Yii::error('Не удалось найти груз '.print_r($workload, 1), 'CargoRoute');
            return;
        }

        /** @var Redis $redis */
        $redis = Yii::$app->redisGeo;

        $cityFrom = $cargo->cityFrom;
        $cityTo = $cargo->cityTo;

        //Нет координат
        if( !$cityFrom->latitude )
            return ;

        //груз по городу
        $sameCity = $cityFrom->id == $cityTo->id;

        //если заявка открыта и груз не по городу
        if ($cargo->status == Cargo::STATUS_ACTIVE && !$sameCity) {
            $city_from = [
                'latitude' => $cityFrom->latitude,
                'longitude' => $cityFrom->longitude
            ];

            $city_to = [
                'latitude' => $cityTo->latitude,
                'longitude' => $cityTo->longitude
            ];

            //добаляем на карту грузов
            $redis->geoAdd(self::REDIS_CARGO_MAP_START, $city_from['longitude'], $city_from['latitude'], $cargo->id);

            $redis->geoAdd(self::REDIS_CARGO_MAP_FINISH, $city_to['longitude'], $city_to['latitude'], $cargo->id);

            //строим маршрут между городами
            RouteHelper::buildRoute($cityFrom->id, $cityTo->id);
        } else {
            //удалить из карты грузов
            $redis->zRem(self::REDIS_CARGO_MAP_START, $cargo->id);
            $redis->zRem(self::REDIS_CARGO_MAP_FINISH, $cargo->id);
        }

        $redis->geoAdd(self::REDIS_CARGO_LOCATION, $cityFrom->longitude, $cityFrom->latitude, $cargo->id);
    }
}
