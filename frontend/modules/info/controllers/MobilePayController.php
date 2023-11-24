<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 14.08.18
 * Time: 9:11
 */

namespace frontend\modules\info\controllers;

use yii\web\Controller;

class MobilePayController extends Controller
{
    public function behaviors()
    {
        return [
            'common\behaviors\NoSubdomain'
        ];
    }

    public function actionIndex(){
        return $this->render('index');
    }
}