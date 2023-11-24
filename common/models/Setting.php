<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "setting".
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property string $value
 */
class Setting extends ActiveRecord
{
    //Время блокировки груза после добавления
    const CARGO_BOOKING_BLOCK = 'cargo_booking_block';

    /**
     * Дней, до истечения груза
     */
    const CARGO_EXPIRE_DAYS = 'cargo_expire_days';

    /**
     * Интервал(минут), не позволяющий добавить точно груз по тому же направлению от того же пользователя
     */
    const CARGO_UNIQ_INTERVAL = 'cargo_uniq_interval';

    //массив полученных значений
    static private $getedValue = [];

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return 'setting';
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['code', 'name', 'value'], 'required'],
            [['code'], 'unique', 'message' => 'Код "{value}" уже существует'],
            [['code', 'name', 'value'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'code' => 'Код',
            'name' => 'Наименование',
            'value' => 'Значение',
        ];
    }

    /**
     * @param $code
     * @param null $default
     * @return mixed
     */
    public static function getValueByCode($code, $default = null){
        if( !isset(self::$getedValue[$code])){
            $setting = Setting::findOne(['code' => $code]);
            self::$getedValue[$code] = $setting ? $setting->value : $default;
        }

        return self::$getedValue[$code];
    }
}
