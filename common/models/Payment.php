<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "payment".
 *
 * @property int $id ИД
 * @property int $payment_system_id ИД платежной системы
 * @property double $amount сумма оплаты
 * @property int $created_by ИД плательщика
 * @property int $created_at Время создания
 * @property string $status Статус
 * @property string $paymentMethodLabel
 *
 * @property PaymentSystem $paymentSystem
 * @property PaymentDetails[] $paymentDetails
 * @property User $createdBy
 */
class Payment extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_REFUSED = 'refused';
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';

    /**
     * {@inheritdoc}
     */
    public static function tableName(){
        return 'payment';
    }

    public function behaviors(){
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false
            ],
            [
                'class' => BlameableBehavior::class,
                'updatedByAttribute' => false
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(){
        return [
            [['payment_system_id'], 'required'],
            [['payment_system_id', 'created_at'], 'integer'],
            [['amount'], 'number'],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['status'], 'string', 'max' => 16],
            [['payment_system_id'], 'exist', 'skipOnError' => true, 'targetClass' => PaymentSystem::class, 'targetAttribute' => ['payment_system_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(){
        return [
            'id' => 'ИД',
            'payment_system_id' => 'ИД платежной системы',
            'amount' => 'сумма оплаты',
            'created_by' => 'ИД плательщика',
            'created_at' => 'Время создания',
            'status' => 'Статус',
        ];
    }

    /**
     * @param bool $colored
     * @return array
     */
    static public function statusLabels($colored = false){
        return [
            self::STATUS_SUCCESS => ($colored ? '<span style="color: #3ab845">Обработан</span>' : 'Обработан'),
            self::STATUS_PAID => ($colored ? '<span style="color: #76ac59">Оплачен</span>' : 'Оплачен'),
            self::STATUS_PENDING => ($colored ? '<span style="color: #e0d31b">Ожидание</span>' : 'Ожидание'),
            self::STATUS_PROCESSING => ($colored ? '<span style="color: #1b43bd">Обрабатывается</span>' : 'Обрабатывается'),
            self::STATUS_REFUSED => ($colored ? '<span style="color: #ac4137">Отказ</span>' : 'Отказ')
        ];
    }

    /**
     * @param $status
     * @param bool $colored
     * @return bool|mixed
     */
    static public function getStatusLabel($status, $colored = false){
        $list = self::statusLabels($colored);
        return isset($list[$status]) ? $list[$status] : false;
    }

    /**
     * @return array
     */
    public static function paymentMethodLabels(){
        return [
            PaymentSystem::SYS_SBERBANK => 'Карта',
            PaymentSystem::SYS_JURIDICAL => 'Юр. лицо'
        ];
    }

    /**
     * @param $method
     * @return mixed|string
     */
    public function getPaymentMethodLabel(){
        return self::paymentMethodLabels()[$this->payment_system_id] ?? 'Неизвестен';
    }

    /**
     * @return ActiveQuery
     */
    public function getPaymentSystem(){
        return $this->hasOne(PaymentSystem::class, ['id' => 'payment_system_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getPaymentDetails(){
        return $this->hasMany(PaymentDetails::class, ['payment_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCreatedBy(){
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        //очищаем кэш
        TagDependency::invalidate(Yii::$app->cache, [
            self::tableName()
        ]);

        parent::afterSave($insert, $changedAttributes);
    }
}
