<?php

namespace common\models;


use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "service_rate".
 *
 * @property int $id
 * @property int $service_id
 * @property int $price
 * @property double $amount
 *
 * @property Service $service
 */
class ServiceRate extends ActiveRecord
{
    const DAY = 86400;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service_rate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['service_id', 'required'],
            ['service_id', 'exist', 'targetClass' => Service::class, 'targetAttribute' => 'id'],

            ['amount', 'number'],
            ['price', 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_id' => 'Service ID',
            'price' => 'Price',
            'amount' => 'Amount',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getService()
    {
        return $this->hasOne(Service::class, ['id' => 'service_id']);
    }

    /**
     * @return string
     */
    public function getRateString() {
        switch ($this->service->code){
            case 'top' :
                return "Закрепить на " . $this->amount . " дней. Стоимость: " . number_format($this->price, 2, '.', ' ') . " руб.";
                break;
            case 'sms-notify' :
                return $this->amount . " смс сообщений. Стоимость: " . number_format($this->price, 2, '.', ' ') . " руб.";
                break;
            case 'additional-answers' :
                return "+" . $this->amount . " откликов. Стоимость: " . number_format($this->price, 2, '.', ' ') . " руб.";
                break;
            case 'infinite-answers' :
                return "Неограниченные отклики на " . $this->amount . " дней. Стоимость: " . number_format($this->price, 2, '.', ' ') . " руб.";
                break;
            case 'transporter-verify' :
                return "";
                break;
            default:
                return "";
        }
    }
}
