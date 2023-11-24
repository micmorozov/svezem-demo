<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 20.09.17
 * Time: 9:01
 */

namespace frontend\modules\cargo\widgets;

use common\models\PageTemplates;
use frontend\modules\cargo\widgets\models\CargoCarriageModel;
use yii\base\Widget;

class CargoCarriageForm extends Widget
{
    public $model;
    /** @var PageTemplates $pageTpl */
    public $pageTpl;

    public function init()
    {
        parent::init();

        if( !$this->model ){
            //модель получаем через getInstance
            //потому что в нее уже может быть передана категория
            $this->model = CargoCarriageModel::getInstance();
        }

        WidgetAsset::register($this->view);
    }

    public function run()
    {
        return $this->render('form_vue', [
            'model' => $this->model,
            'pageTpl' => $this->pageTpl
        ]);
    }
}