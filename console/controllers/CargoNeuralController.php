<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.09.18
 * Time: 16:44
 */

namespace console\controllers;

use common\models\Cargo;
use common\modules\NeuralNetwork\NeuralNetwork;
use yii\console\Controller;
use Yii;

class CargoNeuralController extends Controller
{
    public function actionBuild(){
        $nn = new NeuralNetwork();

        $nn->clear();

        //Создаем словарь для нейросети
        $query = Cargo::find()
            ->select('id, description')
            ->where(['not', ['cargo_category_id' => NULL]])
            ->orderBy('id')
            ->limit(500);

        $total = $query->count();
        NeuralNetwork::setProgress(0, 'Создаем словарь для нейросети');

        $iter = 0;
        while($cargos = $query->all()){
            $query->offset += $query->limit;

            $texts = array_map(function($cargo){
                /** @var $cargo Cargo */
                return $cargo->description;
            }, $cargos);

            $nn->createVocabular($texts);

            $iter += count($cargos);

            NeuralNetwork::setProgress(round($iter/$total*100), 'Создаем словарь нейросети');
        }

        $nn->normalizeVocabular();
        $nn->createVocabularVector();

        NeuralNetwork::setProgress(0, 'Создаем категории');
        //создаем категории
        $cargos = Cargo::find()
            ->joinWith('categories')
            ->where(['not', ['cargo_category_id' => NULL]])
            ->groupBy('cargo_category_id')
            ->all();

        $categories = array_map(function($cat){
            /** @var $cat Cargo */
            return $cat->cargoCategory->id;
        }, $cargos);


        $nn->createCategotyIndex($categories);

        NeuralNetwork::setProgress(0, 'Заполняем данными для обучения');
        //обучаем нейросеть
        $nn->createNN();

        $query = Cargo::find()
            ->select(Cargo::tableName().'.id, description, cargo_category_id')
            ->joinWith('cargoCategory')
            ->where(['not', ['cargo_category_id' => NULL]])
            ->andWhere('auto_category', 0)
            ->orderBy(Cargo::tableName().'.id')
            ->limit(500);


        $iter = 0;
        /** @var Cargo[] $cargos */
        while($cargos = $query->all()){
            $query->offset += $query->limit;

            foreach($cargos as $cargo){
                $vector = $nn->getVectorByText($cargo->description);

                $category = $nn->getCategoryByName($cargo->cargoCategory->id);

                $nn->observe($vector, [$category]);
            }

            $iter += count($cargos);
            NeuralNetwork::setProgress(round($iter/$total*100), 'Заполняем данными для обучения');
        }

        $nn->train();
        NeuralNetwork::removeProgress();
    }
}