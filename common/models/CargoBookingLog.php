<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "cargo_booking_log".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $created_at
 * @property integer $cargo_id
 * @property integer $state
 * @property double $price
 *
 * @property User $user
 * @property Cargo $cargo
 */
class CargoBookingLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cargo_booking_log';
    }

    public function behaviors(){
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false
            ],
           /* [
                'class' => BlameableBehavior::class,
                'updatedByAttribute' => false
            ]*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => 'Ид пользователя',
            'cargo_id'  => 'ИД груза',
            'state' => 'Статус',
            'created_at' => 'Дата',
            'price' => 'Цена'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getUser(){
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCargo(){
        return $this->hasOne(Cargo::class, ['id' => 'cargo_id']);
    }

    /**
     * @param bool $colored
     * @return string
     */
    public function getStateLabel($colored = false){
        $labels = Cargo::getStatusLabels($colored);

        return $labels[$this->state] ?? 'Неизвестный статус';
    }
}
