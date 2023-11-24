<?php

namespace frontend\modules\cabinet\controllers;

use backend\models\PaymentSearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * PaymentController.
 */
class PaymentController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ],
            ],
        ];
    }

    public function actionHistory()
    {
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['PaymentSearch']['created_by'] = Yii::$app->user->id;

        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('history', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
