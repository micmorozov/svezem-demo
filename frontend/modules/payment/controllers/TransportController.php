<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 22.08.18
 * Time: 15:50
 */

namespace frontend\modules\payment\controllers;

use common\models\PaymentSystem;
use common\models\Service;
use common\models\ServiceRate;
use common\models\Transport;
use Exception;
use frontend\modules\payment\helpers\PaymentHelper;
use Svezem\Services\PaymentService\Gates\Sberbank\Input\RegisterResponse;
use Svezem\Services\PaymentService\Gates\Sberbank\SberbankGate;
use Svezem\Services\PaymentService\PaymentService;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\ContentNegotiator;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class TransportController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['pay'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON
                ]
            ]
        ];
    }

    public function actionIndex($item_id)
    {
        //$service_id= Yii::$app->request->queryParams['service_id'];
        $service_id = Yii::$app->request->get('service_id', []);

        $service_id = is_array($service_id) ? $service_id : [$service_id];

        //Проверяем что переданные услуги относятся к услугам транспорта
        $diff = array_diff($service_id, Yii::$app->params['transportServices']);
        if ( !empty($diff)) {
            throw new InvalidArgumentException('service_id указан неверно');
        }

        $services = null;
        if ($service_id) {
            //Проверяем что переданные услуги относятся к услугам транспорта
            $diff = array_diff($service_id, Yii::$app->params['transportServices']);
            if ( !empty($diff)) {
                throw new InvalidArgumentException('service_id указан неверно');
            }

            $services = Service::findAll($service_id);
        }

        $transport = Transport::find()
            ->where([
                'id' => $item_id,
                'created_by' => Yii::$app->user->id
            ])
            ->one();

        if ( !$transport) {
            throw new InvalidArgumentException();
        }

        $remain_services_ids = array_diff(Yii::$app->params['transportServices'], $service_id);

        $remain_services = Service::findAll($remain_services_ids);

        return $this->render('index', [
            'item_id' => $item_id,
            'services' => $services,
            'transport' => $transport,
            'remain_services' => $remain_services
        ]);
    }

    public function actionPay()
    {
        $ratesId = Yii::$app->request->post('rates');
        $item_id = Yii::$app->request->post('item_id');
        $payType = Yii::$app->request->post('payType', 'card');

        $transport = Transport::findOne([
            'id' => $item_id,
            'created_by' => Yii::$app->user->id
        ]);

        if ( !$transport) {
            Yii::$app->response->statusCode = 400;
            return [
                'msg' => 'Неверно указан транспорт'
            ];
        }

        $rates = ServiceRate::findAll($ratesId);

        $servicesId = array_map(function ($rate){
            /** @var ServiceRate $rate */
            return $rate->service_id;
        }, $rates);
        $servicesId = array_unique($servicesId);

        //Из каждой услуги можно получить только один тариф
        //поэтому их кол-во должно совпадать
        if (count($rates) != count($servicesId)) {
            Yii::$app->response->statusCode = 400;
            return [
                'msg' => 'Неверно указаны тарифы'
            ];
        }

        //проверяем что переданные ИД сервисов входят в список разрешенных
        $diff_arr = array_diff($servicesId, Yii::$app->params['transportServices']);

        if ( !empty($diff_arr)) {
            Yii::$app->response->statusCode = 400;
            return [
                'msg' => 'Неверно указаны услуги'
            ];
        }

        $details = [];
        foreach ($rates as $rate) {
            $details[] = [
                'service_rate_id' => $rate->id,
                'object_id' => $transport->id
            ];
        }

        if ($payType == 'card') {
            $payment = PaymentHelper::createPayment(PaymentSystem::SYS_SBERBANK, $details);
        } elseif ($payType == 'juridical') {
            $payment = PaymentHelper::createPayment(PaymentSystem::SYS_JURIDICAL, $details);
        }

        if ($payment->payment_system_id == PaymentSystem::SYS_SBERBANK) {
            $paymentGate = Yii::$container->get(PaymentService::class, [Yii::$container->get(SberbankGate::class)]);
            /** @var RegisterResponse $registerResponse */
            $registerResponse = $paymentGate->registerPayment([
                'orderNumber' => $payment->id,
                'amount' => $payment->amount*100, // Цена в копейках
                'returnUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/transport/mine/']),
                'failUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/transport/mine/']),
                'description' => "[{$payment->id}] Оплата услуг сервиса Svezem.ru"
            ]);
            if(!$registerResponse->isOk()) {
                Yii::$app->response->statusCode = 400;
                return [
                    'msg' => $registerResponse->getErrorMessage()
                ];
            }
            return [
                'redirect' => $registerResponse->getFormUrl()
            ];
        } elseif ($payment->payment_system_id == PaymentSystem::SYS_JURIDICAL) {
            return [
                'redirect' => Url::toRoute(['/payment/juridical/requisites', 'payment' => $payment->id])
            ];
        }
    }
}
