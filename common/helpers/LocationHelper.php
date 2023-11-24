<?php

namespace common\helpers;

use common\models\City;
use common\models\Country;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\Region;
use Yii;
use yii\base\BaseObject;
use yii\caching\TagDependency;
use yii\web\NotFoundHttpException;

class LocationHelper
{
    private static $domainCity = null;

    //редирект на урл без города
    static public function toNoCityUrl($statusCode = 301)
    {
        Yii::$app->response->redirect("https://".Yii::getAlias('@domain') . Yii::$app->request->url,
            $statusCode, false)->send();
    }

    /**
     * Получение города из домена
     *
     * @return bool|mixed|null
     * @throws NotFoundHttpException
     */
    static public function getCityFromDomain(){
        if(self::$domainCity === null){
            $list = explode('.', Yii::$app->request->getHostName());

            // Убираем из массива ru и svezem
            array_pop($list);
            array_pop($list);

            self::$domainCity = false;
            if (count($list)) {
                self::$domainCity = implode('.', $list);
            }
        }

        return self::$domainCity;
    }

    /**
     * Сохранить в сессию город пользователя
     * @param FastCity|Region|Country $location
     */
    static function setCurrentLocation(LocationInterface $location = null)
    {
        if($location instanceof LocationInterface)
            Yii::$app->session->set('currentLocation', $location);
        else
            Yii::$app->session->remove('currentLocation');
    }

    static public function getCurrentLocation(): ?LocationInterface
    {
        $location = Yii::$app->session->get('currentLocation');
        if(!$location instanceof LocationInterface)
            $location = null;

        return $location;
    }

    static public function hasSelectedLocation(): bool
    {
        return Yii::$app->session->has('currentLocation');
    }

    /**
     * @param string $locationCode
     * @return LocationInterface
     */
    static function getLocationByCode($locationCode): ?LocationInterface
    {
        if(!$locationCode) return null;

        $location = City::findOne(['code' => $locationCode]);

        if(!$location)
            $location = Region::findOne(['slug' => $locationCode]);

        if(!$location)
            $location = Country::findOne(['code' => $locationCode]);


        return $location;
    }
}
