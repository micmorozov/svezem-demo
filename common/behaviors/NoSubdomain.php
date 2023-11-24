<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.05.17
 * Time: 13:00
 */

namespace common\behaviors;

use common\helpers\LocationHelper;
use yii\base\ActionFilter;

class NoSubdomain extends ActionFilter
{
    public function beforeAction($action)
    {
        if ( !parent::beforeAction($action)) {
            return false;
        }

        //урл содержит поддомен
        if (LocationHelper::getCityFromDomain()) {
            LocationHelper::toNoCityUrl();
        }

        return true;
    }
}
