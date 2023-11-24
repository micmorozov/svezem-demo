<?php
namespace common\validators;

use Yii;
use yii\validators\EmailValidator;
use yii\validators\Validator;
use common\models\User;
use common\models\DisposableEmailDomain;

/**
 * Валидатор пользовательского логина.
 * Class UserLoginValidator
 * @package common\validators
 */
class UserLoginValidator extends Validator{

    const LOGIN_TYPE_UNKNOWN = 0;
    const LOGIN_TYPE_EMAIL = 1;
    const LOGIN_TYPE_PHONE = 2;

    const USER_MUST_EXIST = 1;
    const USER_MUST_NOT_EXIST = 2;

    //какому свойству будет присвоено значение типа логина
    public $Logintype;
    //проверять существование логина
    public $userExistence;

    private $_msg;

    public $enableClientValidation = false;

    /**
     * @inheritdoc
     */
    public function init(){
        parent::init();

        $this->_msg = 'Значение не является E-Mail или телефоном';

        if( $this->Logintype === null )
            $this->Logintype = 'Logintype';
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute){
        $type = self::LOGIN_TYPE_UNKNOWN;
        $login = $model->$attribute;

        //проверяем годится ли логин для email
        $email = new EmailValidator();
        if( $email->validate($login) ){
            $type = self::LOGIN_TYPE_EMAIL;

            if(Yii::$app->params['enableRestrictionOnDisposableEmails'] === true) {
                $emailPieces = explode('@', $login);
                if(DisposableEmailDomain::find()->where(['domain' => $emailPieces[1]])->count()) {
                    $this->addError($model, $attribute, 'Вы не можете использовать этот Email, потому, что он в чёрном списке.');
                    $model->{$this->Logintype} = $type;
                    return ;
                }
            }
        }

        if( !$type ){
            //проверяем годится ли логин для телефона
            $phone = new PhoneValidator();
            if( $phone->validate($login) )
                $type = self::LOGIN_TYPE_PHONE;
        }

        if( !$type ){
            $message = $this->message ? $this->message : $this->_msg;

            $this->addError($model, $attribute, $message);
        }
        else{
            if( $this->userExistence ){
                $user = User::findByLogin($login);

                $typeName = ($type == self::LOGIN_TYPE_EMAIL ? 'E-Mail' : 'телефоном');

                if( $user && $this->userExistence == self::USER_MUST_NOT_EXIST ){
                    $this->addError($model, $attribute, "Пользователь с таким {$typeName} уже существует");
                }

                if( !$user && $this->userExistence == self::USER_MUST_EXIST ){
                    $this->addError($model, $attribute, "Пользователь с таким {$typeName} не существует");
                }
            }

            //присваиваем модели тип логина
            if( property_exists($model, $this->Logintype) )
                $model->{$this->Logintype} = $type;
        }
    }

    public function clientValidateAttribute($model, $attribute, $view){
        return <<<JS
deferred.push($.get("/user/check-exist/", {email: value}).done(function(data) {
            if ('' !== data) {
                messages.push(data);
            }
        }));
JS;

    }
}