<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 22.06.18
 * Time: 16:06
 */

namespace frontend\modules\payment\helpers;

use common\models\Payment;
use common\models\PaymentDetails;
use common\models\PaymentSystem;
use common\models\Service;
use common\models\ServiceRate;
use Exception;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\NotFoundHttpException;

class PaymentHelper
{
    /**
     * @param integer $sys_id - ИД платежной системы
     * @param array $params - [[
     *                            'service_rate_id' - <ИД_тарифа>
     *                            'count' - <кол-во> для услуг с открытой ценой
     *                            'object_id' - <ИД объетка услуги>
     *                          ],
     *                          ...
     *                         ]
     * @return bool|Payment
     * @throws \yii\db\Exception
     */
    static public function createPayment($sys_id, $params)
    {
        // Ищем платежную систему
        $payment_system = PaymentSystem::findOne($sys_id);
        if ($payment_system === null) {
            throw new InvalidArgumentException('Неверно указана платежная система');
        }

        //Список тарифов (в нем будут определены цены согласно указанному количеству)
        //object_id - используется в payment_details
        $serviceList = [];

        // Проверяем услугу и корректность указнного количества
        foreach ($params as $param) {
            $serviceRate = ServiceRate::findOne($param['service_rate_id']);

            if ($serviceRate === null) {
                throw new InvalidArgumentException('Неверно указан тариф');
            }

            $service = $serviceRate->service;

            //Если услуга с открытой ценой
            if ($service->open_price) {
                if ( !isset($param['count'])) {
                    throw new InvalidArgumentException('Для услуг с открытой ценой необходимо указать количество');
                }

                $count = $param['count'];
            } else {
                $count = $serviceRate->amount;
            }

            $object_id = isset($param['object_id']) ? $param['object_id'] : null;

            $serviceList[] = [
                'service_rate' => $serviceRate,
                'count' => $count,
                'price' => Service::getPriceByCount($serviceRate->service_id, $count),
                'object_id' => $object_id
            ];
        }

        //транзакция
        $transaction = Yii::$app->db->beginTransaction();
        //флаг успешного выполнения
        $success = true;

        // Создаем платеж в таблице платежей
        $payment = new Payment();
        $payment->payment_system_id = $payment_system->id;
        //суммируем цены за все услуги
        $payment->amount = array_reduce($serviceList, function ($mem, $item){
            return $mem + $item['price'];
        });

        if ($payment->save()) {
            //создаем детали платежа
            foreach ($serviceList as $item) {
                /** @var ServiceRate $serviceRate */
                $serviceRate = $item['service_rate'];

                $detail = new PaymentDetails();
                $detail->payment_id = $payment->id;
                $detail->count = $item['count'];
                $detail->amount = $item['price'];
                $detail->service_id = $serviceRate->service_id;
                $detail->service_rate_id = $serviceRate->id;
                $detail->object_id = $item['object_id'];

                if ( !$detail->save()) {
                    $success = false;
                    Yii::error('Не удалось сохранить '.PaymentDetails::tableName()." ".print_r($detail->getErrors(), 1),
                        'PaymentHelper.createPaymnet');
                    break;
                }
            }
        } else {
            $success = false;
            Yii::error('Не удалось сохранить '.Payment::tableName()." ".print_r($payment->getErrors(), 1),
                'PaymentHelper.createPaymnet');
        }

        if ($success) {
            $transaction->commit();
            return $payment;
        } else {
            $transaction->rollBack();
            return false;
        }
    }
}
