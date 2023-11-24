<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 14.08.18
 * Time: 9:11
 */

namespace frontend\modules\info\controllers;

use common\behaviors\NoSubdomain;
use yii\web\Controller;

class PaymentMethodsController extends Controller
{
    public function behaviors()
    {
        return [
            NoSubdomain::class
        ];
    }

    public function actionIndex(){
        return $this->render('index');
    }
}