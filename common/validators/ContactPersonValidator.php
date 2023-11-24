<?php
/**
 * Валидатор ФИО и имени пользователя
 */
namespace common\validators;

use yii\validators\Validator;
use common\models\CargoStopWord;
use Exception;

class ContactPersonValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $model->$attribute = trim($model->$attribute);
        $model->$attribute = trim($model->$attribute, "-");
        $model->$attribute = ltrim($model->$attribute, ".");

        $model->$attribute = preg_replace(['/\s+/','/-+/','/\.+/','/"+/','/\'+/'], [' ','-', '.','"','"'], $model->$attribute);
        if(!preg_match('/^[a-zа-яёЁ"\s\d\.-]+$/iu', $model->$attribute)) {
            $this->addError($model, $attribute, 'Имя может содержать только буквы, цифры, пробел, "-", "." и двойные кавычки');
        }
    }
}