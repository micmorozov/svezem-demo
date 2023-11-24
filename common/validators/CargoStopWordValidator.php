<?php
namespace common\validators;

use yii\validators\Validator;
use common\models\CargoStopWord;
use Exception;

class CargoStopWordValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $text = str_replace('(', '-', $model->$attribute);
        $text = str_replace(')', '-', $text);

        $cnt = CargoStopWord::find()->where("LOWER(:text) REGEXP LOWER(`stopword`)", [':text' => $text])->count();
        if($cnt) $this->addError($model, $attribute, 'Текст содержит стоп слова');
    }
}