<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "profile_transporter_type_assn".
 *
 * @property integer $profile_id
 * @property integer $type_id
 *
 * @property TransporterType $type
 * @property Profile $profile
 */
class ProfileTransporterTypeAssn extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'profile_transporter_type_assn';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['profile_id', 'type_id'], 'required'],
            [['profile_id', 'type_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'profile_id' => 'Profile ID',
            'type_id' => 'Type ID',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(TransporterType::className(), ['id' => 'type_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::className(), ['id' => 'profile_id']);
    }
}
