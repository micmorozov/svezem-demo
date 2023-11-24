<?php

namespace frontend\modules\subscribe\models;

use common\helpers\StringHelper;
use common\models\Setting;
use common\models\User;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "subscribe".
 *
 * @property int $id ИД
 * @property int $userid Пользователь
 * @property string $type Тип услуги
 * @property string $phone Телефон
 * @property string $email E-mail
 * @property int $msg_count Количество сообщений
 * @property int $remain_msg_count Сообщений осталось
 * @property int $free Количество бесплатных сообщений
 * @property int $created_at Дата создания
 * @property string $last_update Время последнего обновления
 *
 * @property User $user
 * @property SubscribeRules[] $subscribeRules
 */
class Subscribe extends ActiveRecord
{
    public $addMessage;

    const SCENARIO_DIRECT_CREATE = 'DirectCreate';
    const SCENARIO_EDIT_CONTACT = 'editphone';

    const TYPE_FREE = 'free';
    const TYPE_PAID = 'paid';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscribe';
    }

    public function behaviors()
    {
        return [
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'userid',
                'updatedByAttribute' => false
            ],
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userid', 'remain_msg_count', 'free', 'created_at'], 'integer'],
            [['type'], 'in', 'range' => [self::TYPE_FREE, self::TYPE_PAID]],
            ['phone', 'common\validators\PhoneValidator'],
            [['phone'],
                'string',
                'when' => function ($model) {return $model->type == Subscribe::TYPE_PAID;},
                'whenClient' => 'function () {return false}',
                'length' => [3,16],
                'skipOnEmpty' => false,
            ],

            //////////////////////////
            /// email может состоять из нескольких email адресов, для их валидации используем валидатор each
            /// используются фильтры что бы из строки сделат массив для each валидатора и после его работы собрать массив обратно в строку
            ['email', 'filter', 'skipOnArray' => true, 'filter' => function ($value) {
                return explode(' ', trim(preg_replace('/[\s,;]+/',' ',$value)));
            }],
            ['email', 'each', 'rule' => ['email'], 'message' => 'Некорректный E-mail',
                'when' => function ($model) {return $model->type == Subscribe::TYPE_FREE;},
                'whenClient' => 'function () {return false}',
                'skipOnEmpty' => false
            ],
            [['email'], 'filter', 'skipOnArray' => false, 'filter' => function ($value) { return implode(";", $value);}],
            //////////////////////////

            [['userid'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userid' => 'id']],
            //addMessage используется только для пополнения пользователем
            //при прямом создании (обычно для бесплатных) проверка не требуется
            ['addMessage', 'number', 'integerOnly'=>true, 'skipOnEmpty'=>false, 'min'=>1, 'except'=>[self::SCENARIO_DIRECT_CREATE, self::SCENARIO_EDIT_CONTACT]],
            [['last_update'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД',
            'userid' => 'Пользователь',
            'type' => 'Тип услуги',
            'phone' => 'Телефон',
            'email' => 'E-mail',
            'remain_msg_count' => 'Сообщений осталось',
            'addMessage' => 'Пополнить баланс на ',
            'free' => 'Количество бесплатных сообщений',
            'created_at' => 'Дата создания',
            'last_update' => 'Время последнего обновления'
        ];
    }

    static public function typeLabels(){
        return [
            self::TYPE_FREE => 'Бесплатно',
            self::TYPE_PAID => 'Платно'
        ];
    }

    static public function getTypeLable($type){
        $list = self::typeLabels();
        return $list[$type] ?? null;
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userid']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSubscribeRules()
    {
        return $this->hasMany(SubscribeRules::class, ['subscribe_id' => 'id']);
    }

    /**
     * @param array $opt
     * @return bool|Subscribe
     */
    static public function createFree($opt = []){
        //кол-во бесплатных сообщений
        // Не даем бесплатных подписок
        $msgCount = 0;//Setting::getValueByCode('free_subscribe_count', 30);

        $subscribe = new Subscribe();
        $subscribe->scenario = Subscribe::SCENARIO_DIRECT_CREATE;
        $subscribe->remain_msg_count = $msgCount;
        $subscribe->free = $msgCount;

        if( isset($opt['userid']) ){
            /** @var BlameableBehavior $bevior */
            $bevior = $subscribe->getBehavior('BlameableBehavior');
            $bevior->value = $opt['userid'];
        }

        if( isset($opt['phone']) ){
            $subscribe->phone = $opt['phone'];
        }

        if( isset($opt['email']) ){
            $subscribe->type = self::TYPE_FREE;
            $subscribe->email = $opt['email'];
        }

        if( !$subscribe->save() ){
            return false;
        }
        else{
            return $subscribe;
        }
    }
}