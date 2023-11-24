<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 05.12.16
 * Time: 10:28
 */

namespace common\models;

use Yii;
use frontend\modules\account\models\SignupForm;
use yii\base\Model;
use yii\helpers\Html;

/**
 * Class LoginSignup
 * @property SignupForm $signup
 * @property LoginForm $login
 * @property string $signupScenario
 *
 * @package common\models
 */
class LoginSignup extends Model {

    const TYPE_SIGNUP = 0;
    const TYPE_LOGIN = 10;

    private $login;
    private $signup;

    public $type;
    public $profile_type;

    public function init(){
        parent::init();

        $this->login = new LoginForm();
        $this->signup = new SignupForm();
    }

    public function setSignupScenario($s){
        $this->signup->scenario = $s;
    }

    public function rules(){
        return [
            ['type', 'in', 'range' => [self::TYPE_SIGNUP, self::TYPE_LOGIN]],
        ];
    }

    public function getLogin(){
        return $this->login;
    }

    public function getSignup(){
        return $this->signup;
    }

    public function setProfileType($type){
        $this->profile_type = $type;
    }

    public function load($data, $formName = null){
        //данные могут не содержать параметры для модели LoginSignup
        //поэтому родительская ф-ция вернет false. Т.к. данные содержат атрибуты
        //других моделей, мы применияем load на них
        parent::load($data);

        if( $this->type == self::TYPE_SIGNUP ){
            $this->signup->types = $this->profile_type;
            return $this->signup->load($data);
        }

        if( $this->type == self::TYPE_LOGIN ){
            return $this->login->load($data);
        }
    }

    public function validate($attributeNames = null, $clearErrors = true){
        if( $this->type == self::TYPE_SIGNUP ){
            return $this->signup->validate($attributeNames, $clearErrors);
        }

        if( $this->type == self::TYPE_LOGIN ){
            return $this->login->validate($attributeNames, $clearErrors);
        }
    }

    public function getErrors($attribute = null){
        if( $this->type == self::TYPE_SIGNUP ){
            return $this->signup->getErrors($attribute);
        }

        if( $this->type == self::TYPE_LOGIN ){
            return $this->login->getErrors($attribute);
        }
    }

    public function hasErrors($attribute = null){
        if( $this->type == self::TYPE_SIGNUP ){
            return $this->signup->hasErrors($attribute);
        }

        if( $this->type == self::TYPE_LOGIN ){
            return $this->login->hasErrors($attribute);
        }
    }

    public function loginSignup(){
        if( $this->type == self::TYPE_SIGNUP ){
            if( !$user = $this->signup->signup() )
                return false;

            return Yii::$app->user->login($user, 3600 * 24 * 30);
        }

        if( $this->type == self::TYPE_LOGIN ){
            return $this->login->login();
        }

        return false;
    }

    /**
     * Creates Profile after user signed up.
     */
    public function createProfile($opt = []){
        $user = Yii::$app->user->identity;

        $user_form = $this->type == self::TYPE_SIGNUP ? $this->signup : $this->login;

        $profile =  $this->profile_type == Profile::TYPE_SENDER ? $user->senderProfile : $user->transporterProfile;
        if (!$profile){
            $profile = new Profile();

            if( $this->type == self::TYPE_SIGNUP ) {
                $profile->city_id = $user_form->city_id;
            } else {
                $profile->city_id = $opt['city_id'];
            }

            $profile->type = $this->profile_type;
            $profile->contact_person = isset($user_form->contact_person) ? $user_form->contact_person : null;

            $profile->contact_phone = $user->phone;

            if( !empty($user->email) )
                $profile->contact_email = $user->email;

            if ($profile->save()){
                return $profile;
            }
        }
        else {
            return $profile;
        }
        return null;
    }

    /**
     * Отправка данных валидации при AJAX запросе
     * @param null $attributes
     * @param bool $validate
     * @return array
     */
    public function ajaxValidate($attributes = null, $validate = false)
    {
        $result = [];

        if( $validate )
            $this->validate($attributes);

        $model = $this->type == self::TYPE_LOGIN ? $this->login : $this->signup;
        foreach ($this->getErrors() as $attribute => $errors) {
            $result[Html::getInputId($model, $attribute)] = $errors;
        }

        return $result;
    }
}