<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "transporter_reviews".
 *
 * @property integer $id
 * @property integer $profile_id
 * @property integer $sender_id
 * @property string $message
 * @property integer $created_at
 *
 * @property Profile $profile
 * @property User $sender
 */
class TransporterReviews extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transporter_reviews';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at']
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['profile_id', 'sender_id', 'created_at'], 'integer'],
            [['message'], 'string', 'max' => 1024],
            [['message'], 'required'],
            [['message'], 'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],
            [['sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['sender_id' => 'id'],
                //при создании пользователя через номер телефона
                //у него статус PENDING
                //после реализации подтверждения раскомментировать
                //'filter'=> ['status' => User::STATUS_ACTIVE]
            ],
            [['profile_id'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::className(), 'targetAttribute' => ['profile_id' => 'id'],
                'filter'=> ['and',
                    ['not', ['type' => Profile::TYPE_SENDER]],
                    ['not', ['created_by' => $this->sender_id]]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД комментария',
            'profile_id' => 'ИД перевозчика',
            'sender_id' => 'ИД пользователя',
            'message' => 'отзыв',
            'create_at' => 'время создания',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::className(), ['id' => 'profile_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSender()
    {
        return $this->hasOne(User::className(), ['id' => 'sender_id']);
    }
}
