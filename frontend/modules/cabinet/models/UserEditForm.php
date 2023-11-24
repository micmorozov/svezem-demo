<?php
namespace frontend\modules\cabinet\models;

use common\models\User;
use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class UserEditForm extends Model
{
    public $password;
    public $password_new;
    public $password_new_repeat;

    public function init(){
        parent::init();

        $this->attributes = Yii::$app->user->identity->attributes;
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

            ['password','required'],
            ['password','string', 'min' => 6, 'max' => 25],
            ['password','validatePassword'],

            ['password_new','required'],
            ['password_new', 'string', 'min' => 6, 'max' => 25],


            ['password_new_repeat','required'],
            ['password_new_repeat','compare','compareAttribute' => 'password_new'],
        ];
    }

    public function attributeLabels(){
        return [
            'password' => 'Текущий пароль',
            'password_new' => 'Новый пароль',
            'password_new_repeat' => 'Новый пароль еще раз',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = User::find()->where(['id' => Yii::$app->user->identity->id])->one();
            if(!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Пароль указан неверно.');
            }
        }
    }
    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function save()
    {
        if ($this->validate()) {
            $user = User::find()->where(['id' => Yii::$app->user->identity->id])->one();
            $user->setPassword($this->password_new);
            if ($user->save()) {
                return $user;
            }
        }

        return null;
    }
}
