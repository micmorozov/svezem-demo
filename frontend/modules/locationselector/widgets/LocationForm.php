<?php

namespace frontend\modules\locationselector\widgets;

class LocationForm extends \yii\base\Widget
{
    public function init()
    {
        parent::init();
        WidgetAsset::register($this->view);
    }

    public function run()
    {
        return $this->render('form', [
            'foo' => 'bar',
        ]);
    }
}
