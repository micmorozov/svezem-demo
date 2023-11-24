<?php

namespace common\models;

use common\helpers\LocationHelper;
use common\validators\PhoneValidator;
use Throwable;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\validators\EmailValidator;

/**
 * This is the model class for table "contacts".
 *
 * @property integer $id
 * @property integer $city_id
 * @property array|string $email
 * @property string $address
 * @property array|string $phone
 * @property array|string $viber
 * @property array|string $whatsapp
 * @property array|string $telegram
 * @property string $skype
 *
 * @property City $city
 */
class Contacts extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contacts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['city_id'], 'required'],
            [['city_id'], 'integer'],
            [['email'], 'string', 'max' => 512],
            [['address', 'phone', 'viber', 'whatsapp', 'telegram', 'skype'], 'string', 'max' => 128],
            [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => City::className(), 'targetAttribute' => ['city_id' => 'id']],
            [['address','skype'],'filter','filter'=>'\yii\helpers\HtmlPurifier::process']
        ];
    }

    public function init()
    {
        parent::init();
        $this->email = $this->phone = $this->viber = [];
        $this->whatsapp = $this->telegram = $this->skype = [];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД',
            'city_id' => 'ИД города',
            'email' => 'email адреса',
            'address' => 'адрес',
            'phone' => 'телефоны',
            'viber' => 'viber',
            'whatsapp' => 'whatsapp',
            'telegram' => 'telegram',
            'skype' => 'skype',
        ];
    }

    public function beforeValidate(){
        if( !parent::beforeValidate() )
            return false;

        if( !is_array($this->email) ){
            $this->email = explode(',', $this->email);
        }
        if( !is_array($this->phone) ){
            $this->phone = explode(',', $this->phone);
        }
        if( !is_array($this->viber) ){
            $this->viber = explode(',', $this->viber);
        }
        if( !is_array($this->whatsapp) ){
            $this->whatsapp = explode(',', $this->whatsapp);
        }
        if( !is_array($this->telegram) ){
            $this->telegram = explode(',', $this->telegram);
        }
        if( !is_array($this->skype) ){
            $this->skype = explode(',', $this->skype);
        }

        if( $this->email ){
            $this->email = $this->removeEmpty($this->email);

            $validator = new EmailValidator();
            $validator->message = 'Некорректный E-Mail "{value}"';
            foreach($this->email as $email){
                if( !$validator->validate($email, $error) ){
                    $this->addError('email', $error);
                    break;
                }
            }
        }

        $this->phoneValidate('phone');
        $this->phoneValidate( 'viber');
        $this->phoneValidate('whatsapp');
        $this->phoneValidate('telegram');

        if( !$this->hasErrors() ) {
            $this->email = $this->convertToJson($this->email);
            $this->phone = $this->convertToJson($this->phone);
            $this->viber = $this->convertToJson($this->viber);
            $this->whatsapp = $this->convertToJson($this->whatsapp);
            $this->telegram = $this->convertToJson($this->telegram);
            $this->skype = $this->convertToJson($this->skype);
        }

        return !$this->hasErrors();
    }

    protected function phoneValidate($attrName){
        $phones = $this->$attrName;
        $phones = $this->removeEmpty($phones);

        $validator = new PhoneValidator();
        $validator->message = 'Некорректный телефон "{value}"';
        foreach($phones as $index => $phone){
            $phone = $this->$attrName[$index];
            $phone = preg_replace('/[^0-9]/', '', $phone);

            if( !$validator->validate($phone, $error) ) {
                $this->addError($attrName, $error);
                break;
            }

            $phones[$index] = $phone;
        }

        $this->$attrName = $phones;
    }

    public function afterFind()
    {
        parent::afterFind();

        $this->email = $this->convertToArray($this->email);
        $this->phone = $this->convertToArray($this->phone);
        $this->viber = $this->convertToArray($this->viber);
        $this->whatsapp = $this->convertToArray($this->whatsapp);
        $this->telegram = $this->convertToArray($this->telegram);
        $this->skype = $this->convertToArray($this->skype);
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::className(), ['id' => 'city_id']);
    }

    protected function convertToArray($value){
        $res = json_decode($value,1);
        return is_array($res) ? $res : [];
    }

    /**
     * @param $arr
     * @return array|string
     */
    protected function convertToJson($arr){
        $res = json_encode($arr);
        return empty($res) ? '[]' : $res;
    }

    /**
     * @param $arr
     * @return array
     */
    protected function removeEmpty($arr){
        return array_filter($arr, function($item){
            return $item != '';
        });
    }

    /**
     * Строим строку адреса одного формата
     */
    public function getAddrString(){
        return sprintf("%s, г. %s, %s", $this->city->region_ru, $this->city->title_ru, $this->address);
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    static public function getLocalContacts(){
        /** @var FastCity $domainCity */
//        $domainCity = Yii::$app->getBehavior('geo')->domainCity;
//        // Определяем ИД города домена
//        $cityId = $domainCity?$domainCity->cityid:0;

       /* $curCity = LocationHelper::getCurrentCity();
        $cityId = ($curCity) ? $curCity['city_id'] : 0;
*/
        return self::getDb()->cache(function ($db){
  //          $contacts = Contacts::findOne(['city_id'=>$cityId]);
    //        if( !$contacts )
                $contacts = Contacts::findOne(['default' => 1]);

            // Хитрая подмена флага "по умолчанию"
            // На основе этого флага указывается тэг noindex
            // Что бы для домена совпавшего с городом контактов по умолчанию не выдавать этот тэг и не дублировать контакты, убираем флаг default
           // if($contacts->city_id == $cityId) $contacts->default = 0;

            return $contacts;
        }, 86400);
    }
}