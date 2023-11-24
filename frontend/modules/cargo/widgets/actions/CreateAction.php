<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 09.10.17
 * Time: 16:08
 */

namespace frontend\modules\cargo\widgets\actions;

use Yii;
use frontend\modules\cargo\widgets\models\CargoCarriageModel;
use yii\base\Action;
use yii\helpers\Url;
use yii\web\Response;
use yii\widgets\ActiveForm;

class CreateAction extends Action
{
    public function run(){
        $cargoForm = new CargoCarriageModel();

        if( $cargoForm->load(Yii::$app->request->post()) && $cargo_id = $cargoForm->createCargo() ){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'redirect' => Url::to('/cargo/success-create/?cargo_id='.$cargo_id)
            ];
        }else{
            if(Yii::$app->request->isAjax){
                Yii::$app->response->format = Response::FORMAT_JSON;

                //$res = ActiveForm::validate($cargoForm);
                $res = $cargoForm->getErrors();

                /*if( empty($res) ){
                    //$res = ['error' => 'Не удалось создать груз'];
                    $res = $cargoForm->getErrors();
                }*/

                return $res;
            }

            Yii::$app->session->setFlash('error', [
                'title' => 'Извините, произошла ошибка!',
                'text' => 'К сожалению, при создании груза произошла ошибка']);
            return $this->controller->redirect(Yii::$app->request->referrer);
        }
    }
}
