<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 31.03.2017
 * Time: 13:58
 */

namespace frontend\behaviors;

use common\models\Country;
use common\models\Region;
use Yii;
use common\helpers\LocationHelper;
use common\helpers\UserHelper;
use yii\base\Behavior;
use yii\base\Application;
use common\models\FastCity;
use yii\caching\TagDependency;
use yii\web\NotFoundHttpException;

class GeoBehavior extends Behavior
{
    // это свойство используется в фильтрах поиска
    // по умолчанию
    public $domainCity;

    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST  => 'checkCity'
        ];
    }

    /**
     * После обработки запроса определяется город из УРЛ.
     * Если город не известен, то вырезается и редирект
     * @param $event
     * @throws NotFoundHttpException
     */
    public function checkCity($event)
    {
        // Проверяем установку региона по параметру
        $params = Yii::$app->request->queryParams;
        if(isset($params['set_region'])){
            $location = LocationHelper::getLocationByCode($params['set_region']);
            LocationHelper::setCurrentLocation($location);
        }

        $subDomainCity = LocationHelper::getCityFromDomain();
        $this->domainCity = LocationHelper::getLocationByCode($subDomainCity);
        if($subDomainCity && !$this->domainCity){
            throw new NotFoundHttpException();
        }

        if (LocationHelper::hasSelectedLocation()) {
            return;
        }

        if($this->domainCity) {
            LocationHelper::setCurrentLocation($this->domainCity);
        }else{
            $country = Country::findOne(1);
            LocationHelper::setCurrentLocation($country);
        }
    }
}