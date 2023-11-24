<?php

namespace common\validators;

use yii\validators\Validator;

class INNValidator extends Validator
{
    private $allowLength = [10,12];

    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = "{attribute} должен содержать 10 или 12 цифр";
        }
    }

    public function validateAttribute($model, $attr)
    {
        $message = $this->formatMessage($this->message, [
            'attribute' => $model->getAttributeLabel($attr),
        ]);

        $len = strlen($model->$attr);

        if( !in_array($len, $this->allowLength) ){
            $this->addError($model, $attr, $message);
        }
    }

    public function clientValidateAttribute($model, $attr, $view)
    {
        $message = $this->formatMessage($this->message, [
            'attribute' => $model->getAttributeLabel($attr),
        ]);

        $allowLength = json_encode($this->allowLength);

        $message = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return <<<JS
if ($.inArray(value.length, $allowLength) === -1;) {
    messages.push($message;)
}
JS;
    }
}
