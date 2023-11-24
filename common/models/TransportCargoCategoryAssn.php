<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "transport_cargo_category_assn".
 *
 * @property integer $transport_id
 * @property integer $category_id
 *
 * @property CargoCategory $category
 * @property Transport $transport
 */
class TransportCargoCategoryAssn extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transport_cargo_category_assn';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['transport_id', 'category_id'], 'required'],
            [['transport_id', 'category_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transport_id' => 'Transport ID',
            'category_id' => 'Category ID',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(CargoCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTransport()
    {
        return $this->hasOne(Transport::className(), ['id' => 'transport_id']);
    }
}
