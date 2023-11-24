<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "payment_details".
 *
 * @property int $payment_id ИД платежа
 * @property int $count Количество услуг
 * @property double $amount Стоимость
 * @property int $service_id ИД услуги
 * @property int $service_rate_id ИД тарифа
 * @property int $object_id ИД объетка услуги
 * @property string $unit Единица измерения улуги
 *
 * @property Payment $payment
 * @property Service $service
 * @property ServiceRate $serviceRate
 */
class PaymentDetails extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payment_id', 'service_id', 'object_id'], 'integer'],
            [['count'], 'number'],
            [['amount'], 'number'],
            [['service_rate_id'], 'required'],
            [['payment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Payment::class, 'targetAttribute' => ['payment_id' => 'id']],
            [['service_id'], 'exist', 'skipOnError' => true, 'targetClass' => Service::class, 'targetAttribute' => ['service_id' => 'id']],
            [['service_rate_id'], 'exist', 'skipOnError' => true, 'targetClass' => ServiceRate::class, 'targetAttribute' => ['service_rate_id' => 'id']]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'payment_id' => 'ИД платежа',
            'count' => 'Количество услуг',
            'amount' => 'Стоимость',
            'service_id' => 'ИД услуги',
            'service_rate_id' => 'ИД тарифа',
            'object_id' => 'ИД объетка услуги',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getPayment()
    {
        return $this->hasOne(Payment::class, ['id' => 'payment_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getService()
    {
        return $this->hasOne(Service::class, ['id' => 'service_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getServiceRate()
    {
        return $this->hasOne(ServiceRate::class, ['id' => 'service_rate_id']);
    }

    public function getUnit()
    {
        switch ($this->service_id){
            //Услуги на количество дней
            case Service::SEARCH:
            case Service::COLORED:
            case Service::MAIN_PAGE:
            case Service::RECOMMENDATIONS:
            case Service::BOOKING_START:
            case Service::BOOKING_BUSINESS:
            case Service::BOOKING_PROFI:
                $unit = 'дн';
                break;
            case Service::SMS_NOTIFY:
                $unit = 'шт';
                break;
            default: $unit = '';
        }

        return $unit;
    }
}
