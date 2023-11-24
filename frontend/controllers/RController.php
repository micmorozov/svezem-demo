<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 22.10.18
 * Time: 9:47
 */

namespace frontend\controllers;

use common\helpers\Utils;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class RController extends Controller
{
    public function actionIndex($code){
        $url = Utils::getUrlByShortenCode($code);

        if( !$url )
            throw new NotFoundHttpException();

        return $this->redirect($url);
    }
}