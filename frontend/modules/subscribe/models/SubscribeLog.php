<?php

namespace frontend\modules\subscribe\models;

use common\models\Cargo;
use common\models\User;
use Monolog\Logger;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "subscribe_log".
 *
 * @property int $userid ИД пользователя
 * @property int $moment время отправки
 * @property string $phone телефон
 * @property string $email E-Mail
 * @property string $type Тип
 * @property string $text текст сообщения
 * @property int $rule_id ИД правила
 * @property int $cargo_id ИД груза
 *
 * @property SubscribeRules $rule
 * @property User $user
 * @property Cargo $cargo
 */
class SubscribeLog extends ActiveRecord
{
    const TYPE_PHONE = 0;
    const TYPE_EMAIL = 1;
    const TYPE_TELEGRAM = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscribe_log';
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'moment',
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
            /*[['userid'], 'required'],
            [['userid', 'moment'], 'integer'],
            [['phone'], 'string', 'max' => 16],
            [['text'], 'string', 'max' => 256],
            [['userid'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userid' => 'id']],*/
            [['userid', 'phone', 'text', 'moment', 'rule_id', 'cargo_id', 'type'], 'safe']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'userid' => 'ИД пользователя',
            'moment' => 'время отправки',
            'phone' => 'телефон',
            'email' => 'E-Mail',
            'type' => 'Тип',
            'text' => 'текст сообщения',
            'rule_id' => 'ИД правила',
            'cargo_id' => 'ИД груза'
        ];
    }

    /**
     * @return array
     */
    static public function typeLabels()
    {
        return [
            self::TYPE_PHONE => 'Телефон',
            self::TYPE_EMAIL => 'E-Mail',
            self::TYPE_TELEGRAM => 'Telegram',
        ];
    }

    /**
     * @param $type
     * @return mixed|null
     */
    static public function getTypeLabel($type)
    {
        $list = self::typeLabels();
        return $list[$type]??null;
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
    public function getRule()
    {
        return $this->hasOne(SubscribeRules::class, ['id' => 'rule_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCargo()
    {
        return $this->hasOne(Cargo::class, ['id' => 'cargo_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            /** @var Logger $logger */
            $logger = Yii::$container->get(Logger::class);

            $logger->withName('subscribe-log')
                ->info(
                    $this->type,
                    array_merge(
                        [
                            'timestamp' => date('c', $this->moment)
                        ],
                        $this->attributes
                    )
                );
        }
    }
}
