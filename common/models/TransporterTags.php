<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "transporter_tags".
 *
 * @property integer $profile_id
 * @property string $name
 * @property string $url
 * @property int $count
 *
 * @property Profile $profile
 */
class TransporterTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transporter_tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['profile_id', 'name', 'url'], 'required'],
            [['profile_id'], 'integer'],
            [['name', 'url'], 'string', 'max' => 128],
            [['profile_id'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['profile_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'profile_id' => 'ИД профиля',
            'name' => 'Наименования',
            'url' => 'УРЛ',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }
}
