<?php

namespace frontend\modules\account\models;

use common\models\User;
use yii\base\Model;

class EmailForm extends Model
{
    public $email;

    public function rules()
    {
        return [
            ['email', 'required'],
            ['email', 'email', 'skipOnEmpty' => false],
            ['email', 'unique', 'targetClass' => User::class, 'targetAttribute'=> 'email',
                'message'=>'Пользователь с E-Mail `{value}` уже существует']
        ];
    }
}