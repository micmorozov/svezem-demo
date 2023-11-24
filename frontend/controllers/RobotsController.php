<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 06.02.19
 * Time: 15:21
 */

namespace frontend\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\LocationHelper;
use common\models\City;
use common\models\FastCity;
use console\controllers\SitemapController;
use yii\helpers\Url;
use yii\web\Controller;
use Yii;
use yii\web\Response;

class RobotsController extends Controller
{
    public function actionIndex()
    {
        $subdomain = '';

        /// Обработка старой схемы
        /** @var City $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;
        if($domainCity) {
            $subdomain = $domainCity->getCode();
        }
        /////////////////////////

        Yii::$app->response->format = Response::FORMAT_RAW;
        $headers = Yii::$app->response->headers;
        $headers->add('Content-Type', 'text/plain');

        return $this->renderPartial('index', [
            'subdomain' => $subdomain
        ]);
    }
}
