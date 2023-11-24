<?php
namespace frontend\modules\cargo\widgets\models;

use common\helpers\UserHelper;
use common\models\Cargo;
use common\models\City;
use common\models\Profile;
use common\models\User;
use yii\base\Model;

use Yii;

/**
 * Signup form
 */
class CargoCarriageModel extends Model
{
    public $cityFrom;
    public $cityTo;
    public $phone;
    public $description;
    public $category_id;

    static protected $_instance;

    static public function getInstance(){
        if( !self::$_instance ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['description', 'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],
			[['phone', 'description'], 'required', 'message' => 'Заполните указанное поле'],
            ['cityFrom', 'required', 'message' => 'Укажите город отправки'],
            [['cityFrom', 'cityTo'], 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id'],
            [['phone'], '\common\validators\PhoneValidator'],
            [['phone'], '\frontend\modules\cargo\widgets\validators\intlTelInputValidator', 'message'=>'Номер телефона указан некорректно'],
            [['category_id'], 'safe']
        ];
    }

    public function attributeLabels(){
        return [
            'cityFrom' => 'Откуда',
            'cityTo' => 'Куда',
            'phone' => 'Телефон',
            'description' => 'Описание заказа'
        ];
    }

    public function beforeValidate(){
        if( !parent::beforeValidate() )
            return false;

        if( !$this->cityTo ){
            $this->cityTo = $this->cityFrom;
        }

        return true;
    }

    /**
     * Создание груза
     * @return bool|int
     */
    public function createCargo(){
        if( !$this->validate() )
            return false;

        //получаем профиль пользователя для создания груза
        //Если пользователь не создан, то createProfile создаст его, и при отправке сообщения будет использован шаблон
        //Устанавливаем шаблон отправки сообщения регистрации пользователя

        User::$createUserTemplate = ['tpl'=>"newCargo"];
        $profileRes = UserHelper::createProfile($this->phone, [
            'type'=>Profile::TYPE_SENDER,
            'city_id' => $this->cityFrom
        ]);

        if( $profileRes['error'] ){
            return false;
        }

        $profile = $profileRes['profile'];

        $cargo = new Cargo();

        //поле created_by устанавливается при помощи Behavior
        //чтобы задать его, не авторизуя пользователя,
        //задаем значение ИД пользователя
        $Behavior = $cargo->getBehavior('BlameableBehavior');
        if( $Behavior )
            $Behavior->value = $profile->created_by;

        $cargo->profile_id = $profile->id;

        $cargo->city_from = $this->cityFrom;
        $cargo->city_to = $this->cityTo;

        $cargo->status = Cargo::STATUS_ACTIVE;
        $cargo->description = $this->description;

        $cargo->categories = [
            'list'=>[$this->category_id],
            'main'=>$this->category_id
        ];

        //если задана категория, значит она выбрана автоматически
        if( $this->category_id ){
            $cargo->auto_category = 1;
        }

        if(!$cargo->save()){
            Yii::error("Ошибка создания груза ".print_r($cargo->getErrors(),1), 'application.CargoCarriageModel.createCargo');
            $this->addErrors($cargo->getErrors());
            return false;
        }else
            return $cargo->id;
    }

    /**
     * @param $direction 'From' | 'To'
     * @return array
     */
    public function getCityString($direction) {
        $result = [];
        if (null !== $this->{"city$direction"}) {
            /** @var City $city */
            $city = $this->{"city$direction"};
            $text = $city->title_ru;
            if (!empty($city->region_ru)) {
                $text .= ', ' . $city->region_ru;
            }
            $text .= ', ' . $city->country->title_ru;
            $result = [$city->id => $text];
        }
        return $result;
    }
}
