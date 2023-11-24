<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.09.18
 * Time: 10:51
 */

namespace frontend\modules\info\controllers;

use yii\helpers\Url;
use yii\web\Controller;
use common\models\Service;
use Yii;

class SubscribeController extends Controller
{
    public function beforeAction($action){
        if( Yii::$app->request->pathInfo == 'i/sub/' ){
            $this->redirect(Url::to('/info/subscribe'), 301);
        }
        return parent::beforeAction($action);
    }

    public function actionIndex(){
        return $this->render('index', [
            'priceForMsg' => Service::getPriceByCount(Service::SMS_NOTIFY, 1)
        ]);
    }
}