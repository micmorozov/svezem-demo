<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "fetch_phone_log".
 *
 * @property int $id
 * @property int $userid ИД пользователя
 * @property string $object Объект
 * @property int $object_id ИД объекта
 * @property string $useragent Useragent
 * @property string $ip IP адрес
 * @property int $created_at Время создания
 * @property string $status Статус
 *
 * @property User $user
 */
class FetchPhoneLog extends ActiveRecord
{
    const OBJECT_TRANSPORTER = 'transporter';
    const OBJECT_CARGO = 'cargo';
    const OBJECT_TK = 'tk';

    const STATUS_SHOW = 'show';
    const STATUS_CALL = 'call';
    const STATUS_COMPLAINT = 'complaint';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fetch_phone_log';
    }

    public function behaviors()
    {
        return [
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
            [['userid', 'created_at', 'object_id'], 'integer'],
            [['object', 'object_id'], 'required'],
            [['object'], 'in', 'range' => [self::OBJECT_TRANSPORTER, self::OBJECT_CARGO, self::OBJECT_TK]],
            [
                'status',
                'in',
                'range' => [self::STATUS_SHOW, self::STATUS_CALL, self::STATUS_COMPLAINT],
                'skipOnEmpty' => false
            ],
            [['useragent'], 'string', 'max' => 256],
            [['ip'], 'string', 'max' => 15],
            [
                ['userid'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::className(),
                'targetAttribute' => ['userid' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'ИД пользователя',
            'object' => 'Объект',
            'object_id' => 'ИД объекта',
            'useragent' => 'Useragent',
            'ip' => 'IP адрес',
            'created_at' => 'Время создания',
            'status' => 'Статус'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userid']);
    }

    /**
     * @return array
     */
    static public function objectLabels()
    {
        return [
            self::OBJECT_TRANSPORTER => 'Транспортер',
            self::OBJECT_CARGO => 'Груз',
            self::OBJECT_TK => 'ТК'
        ];
    }

    /**
     * @param $label
     * @return mixed|null
     */
    static public function getObjectLabel($label)
    {
        $list = self::objectLabels();

        return isset($list[$label]) ? $list[$label] : null;
    }

    /**
     * @return array
     */
    static public function statusLabels()
    {
        return [
            self::STATUS_SHOW => 'показать',
            self::STATUS_CALL => 'звонок',
            self::STATUS_COMPLAINT => 'жалоба'
        ];
    }

    /**
     * @param $label
     * @return mixed|null
     */
    static public function getStatusLabel($label)
    {
        $list = self::statusLabels();

        return isset($list[$label]) ? $list[$label] : null;
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ( !parent::beforeSave($insert)) {
            return false;
        }

        $this->userid = Yii::$app->user->id;
        $this->useragent = Yii::$app->request->userAgent;
        $this->ip = Yii::$app->request->remoteIP;

        return true;
    }
}
