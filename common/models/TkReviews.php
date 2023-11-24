<?php

namespace common\models;

use frontend\modules\tk\models\Tk;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tk_reviews".
 *
 * @property integer $id
 * @property integer $tk_id
 * @property integer $sender_id
 * @property string $message
 * @property integer $created_at
 *
 * @property Tk $tk
 * @property User $sender
 */
class TkReviews extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tk_reviews';
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
            [['tk_id', 'sender_id', 'created_at'], 'integer'],
            [['message'], 'string', 'max' => 1024],
            [['message'], 'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],
            [['message'], 'required'],
            [['tk_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tk::className(), 'targetAttribute' => ['tk_id' => 'id'],
                'filter' => ['status'=>Tk::STATUS_ACTIVE]],
            [['sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['sender_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД комментария',
            'tk_id' => 'ИД транс компании',
            'sender_id' => 'ИД пользователя',
            'message' => 'отзыв',
            'created_at' => 'время создания',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getTk()
    {
        return $this->hasOne(Tk::className(), ['id' => 'tk_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSender()
    {
        return $this->hasOne(User::className(), ['id' => 'sender_id']);
    }
}
