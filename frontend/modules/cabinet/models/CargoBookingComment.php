<?php

namespace frontend\modules\cabinet\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "cargo_booking_comment".
 *
 * @property integer $cargo_id
 * @property integer $created_by
 * @property integer $created_at
 * @property string $comment
 *
 */
class CargoBookingComment extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cargo_booking_comment';
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['comment', 'string', 'max' => 256]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cargo_id' => 'Cargo ID',
        ];
    }
}
