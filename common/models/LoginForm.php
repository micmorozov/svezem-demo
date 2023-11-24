<?php
namespace common\models;

use common\validators\UserPassValidator;
use ferrumfist\yii2\recaptcha\ReCaptchaValidator;
use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $_user;
    public $reCaptcha;

    /**
     * @inheritdoc
     */
    public function rules() {
        //необходимость проверки поля на клиенте
        //при добавлении груза используется переключатель между формами авторизации и регистрации,
        //а так же валидация на клиенте. Чтобы при выборе одной запретить валидацию другой
        $whenClientCondition = "function(){return $(attribute.input).attr('needValid') === 'false' ? false : true;}";

        return [
            [['email', 'password'], 'required', 'whenClient' => $whenClientCondition],
            ['reCaptcha', ReCaptchaValidator::class, 'when'=>function($model){
                /* @var $model LoginForm */
                return $model->needRecaptcha();
            }, 'whenClient' => $whenClientCondition],
            ['password', UserPassValidator::class, 'when'=>function($model){
                return !$model->hasErrors('reCaptcha');
            }],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels() {
        return [
            'email' => 'E-mail или телефон',
            'password' => 'Пароль',
            'reCaptcha' => 'Проверочный код'
        ];
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login($admin = false) {
        if ($this->validate()) {
            $user = User::findByLogin($this->email);

            if( $admin && !$user->adminAccess() ){
                $this->addError('email', 'Недостаточно прав');
                return false;
            }

            return Yii::$app->user->login($user, 3600 * 24 * 30);
        } else {
            return false;
        }
    }

    public function validate($attributeNames = null, $clearErrors = true){
        $loginKey = 'loginTries:'.$this->email;
        $ipKey = 'loginTries:'.Yii::$app->request->getUserIP();

        if( !parent::validate($attributeNames, $clearErrors) ){

            Yii::$app->redisTemp->incr($loginKey);
            Yii::$app->redisTemp->expire($loginKey, 3600);

            Yii::$app->redisTemp->incr($ipKey);
            Yii::$app->redisTemp->expire($ipKey, 3600);

            return false;
        }
        else{
            Yii::$app->redisTemp->del($loginKey, $ipKey);

            return true;
        }
    }

    public function needRecaptcha(){
        $loginKey = 'loginTries:'.$this->email;
        $ipKey = 'loginTries:'.Yii::$app->request->getUserIP();

        return Yii::$app->redisTemp->get($loginKey) >= Yii::$app->params['showCaptchaAfterNTries']
            || Yii::$app->redisTemp->get($ipKey) >= Yii::$app->params['showCaptchaAfterNTries'];
    }
}
