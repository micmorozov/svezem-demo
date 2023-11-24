<?php
namespace frontend\controllers;

use frontend\modules\tk\models\Tk;
use Yii;
use frontend\models\Rating;
use yii\filters\ContentNegotiator;
use yii\web\Controller;
use yii\web\Response;

class RatingController extends Controller {

    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::className(),
                'only' => ['page', 'model'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ]
        ];
    }

    public function actionPage(){
        $request = Yii::$app->request;
        $id = $request->post('id');
        $score = $request->post('score');

        $rating = Rating::find($id);
        $rating->score = $score;

        $rating->save();

        return [
            'score' => $rating->score,
            'voices' => $rating->voices
        ];
    }

    public function actionModel(){
        $request = Yii::$app->request;
        $id = $request->post('id');
        $score = $request->post('score');

        $model = Tk::findOne($id);
        if( !$model )
            return [];

        $model->saveRating($score);

        return [
            'score' => $model->rating,
            'voices' => $model->rating_voices
        ];
    }
}