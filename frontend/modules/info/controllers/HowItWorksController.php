<?php

namespace frontend\modules\info\controllers;

use yii\web\Controller;

class HowItWorksController extends Controller{

    public function behaviors()
    {
        return [
            'common\behaviors\NoSubdomain'
        ];
    }

    public function actionClient(){
        return $this->render('client');
    }

    public function actionTransporter(){
        return $this->render('transporter');
    }
}