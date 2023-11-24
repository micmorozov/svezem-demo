<?php

namespace frontend\modules\rating\controllers;

use frontend\modules\rating\models\Rating;
use frontend\modules\rating\Module;
use Yii;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\Response;

/**
 * Default controller for the `Module` module
 */
class DefaultController extends Controller
{
    public function behaviors(){
        return [
            [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ]
        ];
    }

    public function actionSave(){
        $id = Yii::$app->request->post('id');
        $score = Yii::$app->request->post('score');

        $ratingModel = Rating::find($id);
        $ratingModel->score = $score;

        //если не удалось сохранить,
        // возвращаем текущее значение
        if( !$ratingModel->save() )
            $ratingModel = Rating::find($id);

        return [
            'score' => $ratingModel->score,
            'sum' => $ratingModel->sum
        ];
    }

    public function actionGet($id){
        $rating = Rating::find($id);

        return [
            'score' => $rating->score,
            'sum' => $rating->sum,
            'readOnly' =>$rating->readOnly
        ];
    }
}
