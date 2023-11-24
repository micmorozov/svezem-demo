<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 14.08.18
 * Time: 9:11
 */

namespace frontend\modules\info\controllers;

use common\behaviors\NoSubdomain;
use common\models\Service;
use Yii;
use yii\web\Controller;

class AboutController extends Controller
{
    public function behaviors()
    {
        return [
            NoSubdomain::class
        ];
    }

    public function actionIndex()
    {
        $services = Service::find()
            ->with('serviceRates')
            ->where(['id' => Yii::$app->params['transportServices']])
            ->all();

        return $this->render('about', [
            'services' => $services
        ]);
    }
}
