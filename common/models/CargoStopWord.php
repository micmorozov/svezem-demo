<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "cargo_image".
 *
 * @property integer $id
 * @property integer $cargo_id
 * @property string $image
 * @property string $status
 * @property string $type
 *
 * @property Cargo $cargo
 */
class CargoStopWord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cargo_stop_word';
    }

    public function behaviors(){
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['stopword', 'comment'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stopword' => 'Стоп слова',
            'comment' => 'Комментарий',
        ];
    }
}
