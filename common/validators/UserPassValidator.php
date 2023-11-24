<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 01.12.16
 * Time: 16:29
 */

namespace common\validators;

use yii\validators\Validator;
use common\models\User;

class UserPassValidator extends Validator{

    //Название поля в модели отвечающее за логин
    //по умолчанию "email"
    public $loginField;

    /**
     * @inheritdoc
     */
    public function init(){
        parent::init();
        if ($this->message === null) {
            $this->message = 'Неверный логин или пароль';
        }

        if( $this->loginField === null )
            $this->loginField = 'email';
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute){
        $user = $this->getUser($model);
        if (!$user || !$user->validatePassword($model->$attribute)) {
            $this->addError($model, $attribute, $this->message);
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser($model){
        $login = $model->{$this->loginField};

        return User::findByLogin($login);
    }
}