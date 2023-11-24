<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 12.10.17
 * Time: 9:35
 */

namespace frontend\controllers;

use Yii;
use common\helpers\UserHelper;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\Response;

class GeoController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            [
                'class' => ContentNegotiator::className(),
                'only' => ['country'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON
                ]
            ]
        ];
    }

    public function actionCountry(){
        $geo = UserHelper::getGeoLocation(Yii::$app->request->getUserIP());
        return isset($geo['country']) ? $geo['country'] : [];
    }
}