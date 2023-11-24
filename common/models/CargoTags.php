<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "cargo_tags".
 *
 * @property string $name
 * @property integer $cargo_id
 * @property string $url
 *
 * @property Cargo $cargo
 */
class CargoTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cargo_tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'cargo_id'], 'required'],
            [['name'],'unique', 'targetAttribute' => ['name', 'cargo_id']],
            [['cargo_id'], 'integer'],
            [['name', 'url'], 'string', 'max' => 128],
            [['cargo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cargo::className(), 'targetAttribute' => ['cargo_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Наименование',
            'cargo_id' => 'ИД груза',
            'url' => 'УРЛ',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCargo()
    {
        return $this->hasOne(Cargo::className(), ['id' => 'cargo_id']);
    }
}
