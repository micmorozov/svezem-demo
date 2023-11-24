<?php


namespace common\models;


use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class Mailing extends ActiveRecord
{
    const TARGET_ALL = 0; // Рассылка всем

    public static function tableName(){
        return '{{mailing}}';
    }

    public function behaviors(){
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => null
            ],
            [
              'class' => BlameableBehavior::class,
              'updatedByAttribute' => null
            ]
        ];
    }

    public function rules(){
        return [
            ['body', 'trim'],
            ['body', 'required']
        ];
    }

    public function attributeLabels()
    {
        return [
            'body' => 'Сообщение',
            'created_at' => 'Дата создания',
            'finish_at' => 'Дата завершения'
        ];
    }

}