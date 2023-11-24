<?php

namespace frontend\modules\account\models;

use common\models\User;
use Yii;
use yii\base\Model;
use yii\validators\EmailValidator;
use yii\validators\ExistValidator;
use common\validators\PhoneValidator;
use common\helpers\StringHelper;
use common\validators\UserLoginValidator;

/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    //В зависимости в от валидации
    //восстанавливаем на почту или телефон
    public $loginType;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            [
                'email',
                UserLoginValidator::class,
                'Logintype' => 'loginType',
                'userExistence' => UserLoginValidator::USER_MUST_EXIST
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => 'E-mail или телефон'
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return boolean whether the email was send
     */
    public function sendEmail()
    {
        /* @var $user User */
        $user = User::findByEmail($this->email);

        if ($user) {
            if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
                $user->generatePasswordResetToken();
            }

            if ($user->save(false)) {
                return Yii::$app->mailer->compose([
                    'html' => 'passwordResetToken-html',
                    'text' => 'passwordResetToken-text'
                ], ['user' => $user])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
                    ->setTo($this->email)
                    ->setSubject('Восстановление пароля на сайте ' . Yii::$app->name)
                    ->send();
            }
        }

        return false;
    }

    /**
     * Отправляем на телефон новый пароль
     */
    public function sendSMS()
    {
        /* @var $user User */
        $user = User::findByLogin($this->email);

        if ($user) {
            $passwd = StringHelper::str_rand(6, '1234567890'); // Генерим пароль для СМС
            $user->setPassword($passwd);
            if ($user->save()) {
                $smsMsg = "Добро пожаловать на Svezem.ru: Логин - {$this->email}, пароль - {$passwd}";
                return Yii::$app->sms->smsSend($this->email, $smsMsg);
            }
        }

        return false;
    }

    public function recoverPassword()
    {
        if ($this->loginType == UserLoginValidator::LOGIN_TYPE_EMAIL) {
            return $this->sendEmail();
        }
        if ($this->loginType == UserLoginValidator::LOGIN_TYPE_PHONE) {
            return $this->sendSMS();
        }
    }
}
