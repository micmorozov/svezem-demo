<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 16.05.18
 * Time: 11:51
 */

namespace frontend\modules\cargo\controllers;

use common\models\Cargo;
use frontend\modules\cargo\models\CargoPassing;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class MapController extends Controller
{
    public function actionPassing()
    {
        $passing = new CargoPassing();

        $passing->load(Yii::$app->request->get());

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $passing->getMap();
    }

    public function actionPassingForCargo($id)
    {
        $cargo = Cargo::findOne($id);

        $passingModel = new CargoPassing();
        $passingModel->excludeCargo = $cargo->id;

        $passingModel->city_from = $cargo->city_from;
        $passingModel->city_to = $cargo->city_to;
        $passingModel->radius = 30;

        //ИД категорий текущего груза
        $cat_ids = array_map(function ($item){
            return $item->id;
        }, $cargo->moderCategories);

        $passingModel->cargoCategoryIds = $cat_ids;

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $passingModel->getMap();
    }
}
