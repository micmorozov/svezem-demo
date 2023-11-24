<?php

namespace frontend\modules\tk\models;

use common\models\City;
use common\validators\PhoneValidator;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\validators\EmailValidator;
use yii\caching\TagDependency;
use common\models\Country;
use common\models\Region;

/**
 * This is the model class for table "tk_details".
 *
 * @property integer $tk_id
 * @property integer $cityid
 * @property int $region_id ИД региона
 * @property int $country_id ИД страны
 * @property array $phone
 * @property array $email
 * @property string $address
 *
 * @property City $city
 * @property Tk $tk
 * @property array $phones
 */
class TkDetails extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tk_details';
    }

    public static function primaryKey()
    {
        return ['tk_id', 'cityid'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tk_id', 'cityid', 'region_id', 'country_id'], 'integer'],
            [['phone', 'address'], 'string', 'max' => 512],
            [['email'], 'string', 'max' => 128],
            ['url', 'url', 'enableIDN'=>true],
            ['cityid', 'required'],
            [['tk_id', 'cityid'], 'unique', 'targetAttribute' => ['tk_id', 'cityid'], 'message' => 'Сочетание транспортной компании и города уже используется'],
            [['cityid'], 'exist', 'skipOnError' => true, 'skipOnEmpty'=>false, 'targetClass' => City::class, 'targetAttribute' => ['cityid' => 'id']],
            [['tk_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tk::class, 'targetAttribute' => ['tk_id' => 'id']],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'id']],
            [['region_id'], 'exist', 'skipOnError' => true, 'targetClass' => Region::class, 'targetAttribute' => ['region_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tk_id' => 'ИД транспортной компании',
            'cityid' => 'ИД города',
            'phone' => 'Номера телефонов',
            'address' => 'Адрес',
            'url' => 'УРЛ'
        ];
    }

    public function init()
    {
        parent::init();
        $this->email = $this->phone = [];
    }

    public function beforeSave($insert)
    {
        if( !parent::beforeSave($insert) )
            return false;

        $city = City::findOne($this->cityid);

        $this->region_id = $city->region_id;
        $this->country_id = $city->country_id;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        //очищаем кэш поиска
        TagDependency::invalidate(Yii::$app->cache, 'tkSearchCache');

        //Необходимо добавить город для выбора пользователя
        Yii::$app->gearman->getDispatcher()->background("addFastCity", [
            'tk_id' => $this->tk_id
        ]);
    }

    public function afterDelete(){
        parent::afterDelete();

        //очищаем кэш поиска
        TagDependency::invalidate(Yii::$app->cache, 'tkSearchCache');
    }

    public function afterFind()
    {
        parent::afterFind();

        $this->phone = $this->convertToArray($this->phone);
        $this->email = $this->convertToArray($this->email);
    }

    protected function convertToArray($value){
        $res = json_decode($value,1);
        return is_array($res) ? $res : [];
    }

    public function beforeValidate(){
        if( !parent::beforeValidate() )
            return false;

        if( !is_array($this->phone) ){
            $this->phone = explode(',', $this->phone);
        }
        $this->phone = array_unique($this->phone);

        $this->phoneValidate('phone');

        if( !is_array($this->email) ) {
            $this->email = explode(',', $this->email);
        }
        $this->email = array_unique($this->email);

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

        if( !$this->hasErrors() ){
            $this->phone = $this->convertToJson($this->phone);
            $this->email = $this->convertToJson($this->email);
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

    protected function convertToJson($arr){
        $res = json_encode($arr);
        return empty($res) ? '[]' : $res;
    }

    protected function removeEmpty($arr){
        return array_filter($arr, function($item){
            return $item != '';
        });
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'cityid']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTk()
    {
        return $this->hasOne(Tk::class, ['id' => 'tk_id']);
    }

    /**
     * @return array|mixed
     */
    public function getPhones(){
        $phones = json_decode($this->phone,1);

        if( $phones )
            return $phones;

        if( is_string($this->phone) )
            return [$this->phone];
        else
            return [];
    }

    public function afterValidate()
    {
        parent::afterValidate();

        if( $this->hasErrors() ){
            $this->phone = $this->convertToArray($this->phone);
            $this->email = $this->convertToArray($this->email);
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::class, ['id' => 'region_id']);
    }
}
