<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "user_service_booking".
 *
 * @property int $userid ИД пользователя
 * @property int $expire Время истечения услуги
 * @property int $booking_limit Лимит бронирований
 * @property int $booking_remain Оставшееся кол-во бронирований
 * @property int $day_limit Суточный лимит
 * @property int $tariff_id ИД тарифа
 *
 * @property ServiceRate $tariff
 * @property User $user
 */
class UserServiceBooking extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_service_booking';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expire'], 'required'],
            [['expire', 'booking_limit', 'booking_remain', 'day_limit', 'tariff_id'], 'integer'],
            [
                ['userid'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['userid' => 'id']
            ],
            [
                ['tariff_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => ServiceRate::class,
                'targetAttribute' => ['tariff_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'userid' => 'ИД пользователя',
            'expire' => 'Время истечения услуги',
            'booking_limit' => 'Лимит бронирований',
            'booking_remain' => 'Оставшееся кол-во бронирований',
            'day_limit' => 'Суточный лимит',
            'tariff_id' => 'ИД тарифа',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getTariff()
    {
        return $this->hasOne(ServiceRate::class, ['id' => 'tariff_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userid']);
    }
}
