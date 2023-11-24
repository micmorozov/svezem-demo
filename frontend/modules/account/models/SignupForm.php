<?php
namespace frontend\modules\account\models;

use common\models\City;
use common\models\Profile;
use common\models\User;
use common\validators\ContactPersonValidator;
use yii\base\Model;
use common\validators\UserLoginValidator;
use Yii;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $types;
    public $city_id;
    public $contact_person;
    public $contact_phone;
    public $email;

    //тип введенного логина (email, телефон и .т.д.)
    public $Logintype;

    const SCENARIO_MODER = 'moder';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        //необходимость проверки поля на клиенте
        $whenClientCondition = "function(){return $(attribute.input).attr('needValid') === 'false' ? false : true;}";

        return [
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required', 'whenClient'=>$whenClientCondition],
            ['email', 'string', 'max' => 255],
            ['email', UserLoginValidator::class, 'Logintype'=>'Logintype', 'userExistence'=>UserLoginValidator::USER_MUST_NOT_EXIST, 'on'=>[self::SCENARIO_DEFAULT, 'OnlyUserCreate']],
            ['email', UserLoginValidator::class, 'Logintype'=>'Logintype', 'on'=>self::SCENARIO_MODER],
        		
        	['city_id', 'required', 'message' => 'Необходимо выбрать «{attribute}»', 'whenClient'=>$whenClientCondition, 'except'=>'OnlyUserCreate'],
        	['city_id', 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id'],
        		
        	['contact_person', 'required', 'message' => 'Необходимо указать «{attribute}»', 'whenClient'=>$whenClientCondition],
        	['contact_person', 'string', 'max' => 255, 'min'=>5],
            ['contact_person', ContactPersonValidator::class],
        		
        	['types', 'required', 'message' => 'Необходимо выбрать «{attribute}»', 'except'=>'OnlyUserCreate'],
            ['types', 'in', 'range' => [Profile::TYPE_SENDER, Profile::TYPE_TRANSPORTER_NOT_SPECIFIED, Profile::TYPE_TRANSPORTER_PRIVATE, Profile::TYPE_TRANSPORTER_JURIDICAL], 'allowArray' => true]
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels() {
        return [
            'types' => 'Вид деятельности',
            'contact_person' => 'Контактное лицо',
            'contact_phone' => 'Телефон',
            'city_id' => 'Город',
            'email' => 'E-mail или телефон'
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate()) {
            if( $user = User::findByLogin($this->email) ){
                return $user;
            }

            $user = new User();

            $user->username = $this->contact_person;

            if( $this->Logintype == UserLoginValidator::LOGIN_TYPE_EMAIL )
                $user->email = $this->email;

            if( $this->Logintype == UserLoginValidator::LOGIN_TYPE_PHONE )
                $user->phone = $this->email;

            if($user->save()){
                if( $this->getScenario() == self::SCENARIO_MODER )
                $message = '';

                if( $this->Logintype == UserLoginValidator::LOGIN_TYPE_EMAIL )
                    $message = 'На Ваш почтовый адрес было выслано письмо для подтверждения регистрации. Пожалуйста, проверьте электронную почту.';

                if( $this->Logintype == UserLoginValidator::LOGIN_TYPE_PHONE )
                    $message = 'На Ваш телефон было выслано SMS с паролем. Пожалуйста, проверьте входящие сообщения.';

                if( property_exists(Yii::$app, 'session') )
                    Yii::$app->session->setFlash('success', $message);
                return $user;
            }
            else{
                if( property_exists(Yii::$app, 'session') )
                    Yii::$app->session->setFlash('error', "Что-то пошло не так");
            }
        }

        return null;
    }

    /**
     * Creates Profiles after user signed up.
     */
    public function createProfiles($user){
        if (!empty($this->types)){
            foreach ($this->types as $type){
                if( $type == Profile::TYPE_SENDER ){
                    if( $user->senderProfile !== null )
                        continue;
                }
                else{
                    if( $user->transporterProfile !== null )
                        continue;
                }

                $profile = new Profile();
                $profile->city_id = isset($this->city_id) ? $this->city_id : null;
                $profile->type = $type;
                $profile->contact_person = isset($this->contact_person) ? $this->contact_person : null;
                
                //телефон
                $profile->contact_phone = $user->phone;
                $profile->phone_country = $user->phone_country;
                
                $profile->contact_email = $user->email;
                /* 05.07.2019 нигде не используется
                if ($profile->type > Profile::TYPE_SENDER){
                    $profile->transporterTypeIds = array_column(TransporterType::find()->asArray()->all(), 'id');
                }*/
                $profile->save();
            }

            //т.к. в ф-ции создается профиль, то нужно очистить результаты предыдущих запросов
            unset($user->senderProfile);
            unset($user->transporterProfile);
        }
    }
}
