<?php

namespace console\jobs;

use common\helpers\GoogleMapsResponse;
use common\models\Cargo;
use common\models\City;
use common\models\Transport;
use console\jobs\jobData\CalcDistanceData;
use GearmanJob;
use Redis;
use Yii;
use yii\helpers\Json;

class CalcDistance extends BaseQueueJob
{
    //средняя скорость км/ч
    const AVG_SPEED = 80;
    //часов в пути в течении дня
    const HOURS_IN_DAY = 8;

    const BEHAVIOR_OBJECT = 'object';
    const BEHAVIOR_PUT_TO_SOCKET = 'putToSocket';

    /**
     * @param CalcDistanceData $job
     * @return bool|mixed
     */
    protected function run($job)
    {
        if ($job->behavior == self::BEHAVIOR_OBJECT) {
            $objectClass = $job->objectClass;

            /** @var $model Cargo|Transport */
            $model = $objectClass::findOne($job->objectId);

            if ( !$model) {
                Yii::error("Не удалось найти объект ".print_r($job->objectId, 1), 'CalcDistance');
                return false;
            }

            $from = $model->city_from;
            $to = $model->city_to;
        } elseif ($job->behavior == self::BEHAVIOR_PUT_TO_SOCKET) {
            $from = $job->city_from;
            $to = $job->city_to;
        }

        $distance = $this->getDistance($from, $to);

        if ($job->behavior == self::BEHAVIOR_OBJECT) {
            $this->saveObject($distance, $model);
            return true;
        } elseif ($job->behavior == self::BEHAVIOR_PUT_TO_SOCKET) {
            $this->putToSocket($distance, $job->socket_id);
            return true;
        }
    }

    protected function getDistance($from, $to){
        if( $from == $to ){
            return 0;
        }

        //Проверяем в редисе
        $redis = $this->getRedis();
        $distance = (int)$redis->get($this->getRedisKey($from, $to));

        if( $distance ){
            return $distance;
        }

        $cityFrom = City::findOne($from);
        $cityTo = City::findOne($to);

        $fromText = $cityFrom->title_ru.", ".$cityFrom->region_ru;
        $toText = $cityTo->title_ru.", ".$cityTo->region_ru;

        /* @var $map GoogleMapsResponse */
        $map = Yii::$app->googleMaps->distancematrix($fromText, $toText);

        $distance = $map->getDistance();

        //Сохраняем в редис
        $redis->set($this->getRedisKey($from, $to), $distance);

        return $distance;
    }

    /**
     * @return Redis
     */
    protected function getRedis(){
        return Yii::$app->redisGeo;
    }

    protected function getRedisKey($from, $to){
        return "distance:{$from}_{$to}";
    }

    /**
     * @param int $distance
     * @param Cargo|Transport $obj
     */
    protected function saveObject($distance, $obj)
    {
        //кол-во часов на прохождение маршрута со скоростью 80км/ч
        $h = round($distance/1000/self::AVG_SPEED);
        //полных дней в пути, считая что водитель за рулем 8 часов в день
        $d = floor($h/self::HOURS_IN_DAY);
        //оставшиеся часы
        $mod_h = $h%self::HOURS_IN_DAY;

        //переводим данные в секунды
        $total_h = $d*24*60*60 + $mod_h*60*60;

        //сохраняем в секундах
        $obj->distance = $distance;
        $obj->duration = $total_h;

        if ( !$obj->save()) {
            Yii::error("Не удалось сохранить расстояние для объекта ".print_r($obj->getErrors(), 1), 'CalcDistance');
        }
    }

    /**
     * @param int $distance
     * @param string $socket_id
     */
    protected function putToSocket($distance, $socket_id)
    {
        $distance = $distance/1000;
        $h = round($distance/80);
        $d = round($h/8);

        Yii::$app->redis->executeCommand('PUBLISH', [
            'channel' => 'pubsub',
            'message' => Json::encode([
                'distance' => $distance,
                'time' => $d,
                'socket_id' => $socket_id
            ])
        ]);
    }
}
