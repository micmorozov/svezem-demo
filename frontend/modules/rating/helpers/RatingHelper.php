<?php

namespace frontend\modules\rating\helpers;

use common\models\PageTemplates;
use Yii;

class RatingHelper
{
    /**
     * @param PageTemplates $tpl
     * @return string
     */
    public static function ratingName($tpl = null){
        $name = Yii::$app->controller->module->id.'-'.Yii::$app->controller->id.'-'.Yii::$app->controller->action->id;

        if( $tpl ){
            $name .= ":cat:".$tpl->id;
        }

        return $name;
    }
}