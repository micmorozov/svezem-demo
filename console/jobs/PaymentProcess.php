<?php
/**
 * Обработка платежа
 * После получения подтверждения оплаты от платежной системы
 * необходимо определить какая услуга была оплачена и внести соответствующие изменения
 *
 * Created by PhpStorm.
 * User: ferrum
 * Date: 31.07.18
 * Time: 9:54
 */

namespace console\jobs;

use common\components\bookingService\Service as BookingService;
use common\models\Payment;
use common\models\PaymentDetails;
use common\models\Service;
use common\models\ServiceRate;
use common\models\Transport;
use Exception;
use frontend\modules\subscribe\models\Subscribe;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\di\NotInstantiableException;

class PaymentProcess extends JobBase
{
    protected $workload;

    public function execute(GearmanJob $job = null)
    {
        $this->workload = $workload = $this->getWorkload($job);
        if ( !$workload) {
            return;
        }

        $payment = Payment::findOne($workload['payment_id']);

        if ( !$payment) {
            Yii::error('Не удалось найти платеж '.print_r($workload, 1), 'PaymentProcess');
            return;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $count = Payment::updateAll([
            'status' => Payment::STATUS_PROCESSING
        ], [
            'id' => $payment->id,
            'status' => Payment::STATUS_PAID
        ]);

        if ( !$count) {
            $transaction->rollBack();
            Yii::error("Не удалось обработать платеж ID: {$payment->id}\n".print_r($payment->attributes, 1),
                'PaymentProcess');
            return;
        }

        // Требуется ли перестроение кэша страниц после оплаты
        $needBuildPageCache = true;

        //один платеж может быть за несколько услуг
        //поэтому получаем информацию по каждой услуге
        foreach ($payment->paymentDetails as $detail) {
            switch ($detail->service_id) {
                //СМС уведомление перевозчиков
                case Service::SMS_NOTIFY:
                    $result = $this->smsNotify($detail);
                    $needBuildPageCache = false;
                    break;
                case Service::SEARCH:
                    $result = $this->updateTransportTimestamp($detail, 'top');
                    //При оплате TOP пересчитать позицию в поиске
                    Transport::calculatePosition($detail->object_id);
                    break;
                case Service::COLORED:
                    $result = $this->updateTransportTimestamp($detail, 'colored');
                    break;
                case Service::MAIN_PAGE:
                    $result = $this->updateTransportTimestamp($detail, 'show_main_page');
                    break;
                case Service::RECOMMENDATIONS:
                    $result = $this->updateTransportTimestamp($detail, 'recommendation');
                    break;
                case Service::BOOKING_BUSINESS:
                case Service::BOOKING_START:
                case Service::BOOKING_PROFI:
                    $result = $this->booking($detail);
                    break;

                default:
                    $result = false;
            }

            //если хоть один из платежей не прошел,
            //останавливаем обработку
            if ( !$result) {
                break;
            }
        }

        if ($result) {
            //меняем статус на завершенный
            Payment::updateAll([
                'status' => Payment::STATUS_SUCCESS
            ], [
                'id' => $payment->id,
                'status' => Payment::STATUS_PROCESSING
            ]);

            $transaction->commit();

            // После завершения коммита обновляем кэш
            if ($needBuildPageCache) {
                // Строим кэш страниц для транспорта
                Yii::$app->gearman->getDispatcher()->background("buildPageCache", [
                    'transport_id' => $detail->object_id
                ]);

                //очищаем кэш поиска
                TagDependency::invalidate(Yii::$app->cache, 'transportSearchCache');
            }
        } else {
            $transaction->rollBack();
        }
    }

    /**
     * @param $detail PaymentDetails
     * @return bool
     */
    protected function smsNotify($detail)
    {
        $count = Subscribe::updateAllCounters([
            'remain_msg_count' => $detail->count
        ], [
            'id' => $detail->object_id
        ]);

        if ( !$count) {
            Yii::error("Не удалось изменить статус подписки ID: {$detail->object_id}".print_r($this->workload, 1),
                'PaymentProcess');

            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $detail PaymentDetails
     * @param $attr string
     * @return bool
     * @throws \yii\db\Exception
     */
    private function updateTransportTimestamp($detail, $attr)
    {
        $days = $detail->count;

        /**
         * Устанавливаем время истечения услуги. Если значение меньше текущего времени или еще не задано,
         * то к текущему добавляем $days дней
         * Если значение еще не истекло, то увеличиваем его еще на $days дней
         */
        $count = Transport::updateAll([
            $attr => new Expression("IF($attr<UNIX_TIMESTAMP(NOW()) OR $attr IS NULL, UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL :day DAY)), UNIX_TIMESTAMP(DATE_ADD(FROM_UNIXTIME($attr), INTERVAL :day DAY)))",
                [':day' => $days]),
            $attr."_payed" => time()
        ], [
            'id' => $detail->object_id
        ]);

        if ( !$count) {
            Yii::error("Не удалось обновить колонку '$attr' транспорта ID: {$detail->object_id}".print_r($this->workload,
                    1), 'PaymentProcess');

            return false;
        } else {
            //Обновляем данные об оплате в Sphinx
            $transport = Transport::findOne($detail->object_id);

            //Соединение со Sphinx может падать
            try{
                Transport::sphinxUpdate($transport);
            } catch (Exception $e){
                Yii::error("Не удалось внести изменения о транспорте ({$transport->id}) в Sphinx. ".$e->getMessage(),
                    'PaymentProcess');
            }

            Transport::updateElk($transport->id);

            return true;
        }
    }

    /**
     * @param PaymentDetails $detail
     * @return bool
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    protected function booking($detail)
    {
        $bookingService = new BookingService($detail->payment->created_by);

        //Определяем кол-во бронирований и дневной лимит,
        //исходя из услуги
        switch ($detail->service_id) {
            case Service::BOOKING_START:
                $count = 1;
                $dayLimit = 3;
                break;
            case Service::BOOKING_BUSINESS:
                $count = 10;
                $dayLimit = 10;
                break;
            case Service::BOOKING_PROFI:
                $count = 30;
                $dayLimit = 30;
                break;
        }

        //При смене тарифа необходимо пересчитать
        //срок бронирования
        $currentRate = ServiceRate::find()
            ->where(['id' => $bookingService->getTariffId()])
            ->one();

        //Новый тариф
        $newRate = $detail->serviceRate;

        $expireTime = $bookingService->getExpire();
        $time = time();
        //Срок действия бронирования
        //Если истек, то считаем текущий момент времени
        $expireTime = $expireTime > $time ? $expireTime : $time;

        //Если меняется тариф
        if ($currentRate && $currentRate->id != $newRate->id) {
            //Цена за секунду по старому тарифу
            $currentSecondCost = $currentRate->price/($currentRate->amount*86400);

            //Цена за секунду по новому тарифу
            $newSecondCost = $newRate->price/($newRate->amount*86400);

            //остаток
            $balance = ($expireTime - $time)*$currentSecondCost;

            //Сумма новой оплаты и остатка
            $summa = $newRate->price + $balance;

            $TTL = $summa/$newSecondCost;

            //При смене тарифа expire устанавливается от текущего времени
            $expire = $time + $TTL;
        } else {
            $TTL = $newRate->amount*86400;

            //При продлении тарифа expire устанавливается от существующего
            $expire = $expireTime + $TTL;
        }

        $res = $bookingService->setBooking(
            $expire,
            $count,
            $dayLimit,
            $newRate->id
        );

        if ( !$res) {
            Yii::error("Не удалось установить услугу бронирования ".print_r($this->workload, 1), 'PaymentProcess');

            return false;
        }

        return true;
    }
}
