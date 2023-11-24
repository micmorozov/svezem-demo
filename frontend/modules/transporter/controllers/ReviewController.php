<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 16.10.17
 * Time: 13:54
 */

namespace frontend\modules\transporter\controllers;

use Yii;

use common\models\TransporterReviews;
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
        $model = new TransporterReviews();
        $model->sender_id = Yii::$app->user->identity->id;

        if( $model->load(Yii::$app->request->post()) && $model->save() )
            Yii::$app->session->setFlash('success', 'Ваш отзыв успешно добавлен!');
        else {
            Yii::$app->session->setFlash('error', 'Произошла ошибка при добавлении отзыва');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}