<?php

namespace frontend\modules\info\controllers;

use common\models\Transport;
use yii\filters\AccessControl;
use yii\web\Controller;
use Yii;

class PaidServicesController extends Controller{

    public function behaviors()
    {
        return [
            'common\behaviors\NoSubdomain',
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ]
                ],
            ],
        ];
    }

    public function actionIndex($id = null){
        $transports = Transport::findAll([
            'created_by' => Yii::$app->user->id,
            'status' => Transport::STATUS_ACTIVE
        ]);

        return $this->render('index', [
            'transports' => $transports,
            'selectedId' => $id
        ]);
    }
}