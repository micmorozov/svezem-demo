<?php

namespace frontend\modules\tk\controllers;

use Yii;

use common\models\TkReviews;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class ReviewController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['create'],
                'rules' => [
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@']
                    ],
                ]
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'create' => ['post']
                ],
            ]
        ];
    }

    public function actionCreate()
    {
        $model = new TkReviews();
        $model->sender_id = Yii::$app->user->identity->id;

        if( $model->load(Yii::$app->request->post()) && $model->save() )
            Yii::$app->session->setFlash('success', 'Ваш отзыв успешно добавлен!');
        else {
            Yii::$app->session->setFlash('error', 'Произошла ошибка при добавлении отзыва');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}