<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "payment_system".
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property string $logo
 * @property string $regex_mask
 * @property string $rate
 * @property string $currency_short_name
 *
 */
class PaymentSystem extends ActiveRecord
{

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    const SYS_UNITPAY = 9;
    const SYS_SBERBANK = 11;
    const SYS_JURIDICAL = 6;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'payment_system';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'name', 'logo', 'regex_mask'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'logo' => 'Logo',
            'regex_mask' => 'Regex Mask',
        ];
    }
}
