<?php
namespace frontend\components\urlRules;

use common\helpers\LocationHelper;
use common\models\City;
use common\models\Country;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\Region;
use yii\web\UrlRule;

class LocationUrlRule extends UrlRule
{
    /**
     * Переменные связанные с локацией
     * @var string[]
     */
    private $locationParams = ['location', 'locationFrom', 'locationTo'];

    public function createUrl($manager, $route, $params)
    {
        foreach($this->locationParams as $locationParam){
            if(isset($params[$locationParam]) && $params[$locationParam] instanceof LocationInterface){
                $params[$locationParam] = $params[$locationParam]->getCode();
            }
        }

        return parent::createUrl($manager, $route, $params);
    }

    public function parseRequest($manager, $request)
    {
        // Нормализация урла через родительский метод
        $params = parent::parseRequest($manager, $request);
        if(!$params) return false;

        foreach($this->locationParams as $locationParam){
            if(isset($params[1][$locationParam])){
                $loc = LocationHelper::getLocationByCode($params[1][$locationParam]);
                // Если location установлен, но объект не нашли, значит нет такого объекта, правило не должно сработать
                if ($params[1][$locationParam] && !$loc)
                    return false;

                $params[1][$locationParam] = $loc;
            }
        }

        return $params;
    }
}