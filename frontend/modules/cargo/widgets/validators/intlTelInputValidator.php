<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 21.03.18
 * Time: 10:21
 */

namespace frontend\modules\cargo\widgets\validators;

use yii\validators\Validator;

class intlTelInputValidator extends Validator
{
    public function clientValidateAttribute($model, $attribute, $view)
    {
        $message = json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<JS
        var countryData = $("input[type='tel']").intlTelInput("getSelectedCountryData");
        var code = "+"+countryData.dialCode;
        
        if( value == code || value.indexOf(code) != 0 )
            messages.push($message)
JS;
    }

    protected function validateValue($value)
    {

    }
}