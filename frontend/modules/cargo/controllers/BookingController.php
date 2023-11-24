<?php

namespace frontend\modules\cargo\controllers;

use common\behaviors\NoSubdomain;
use common\components\bookingService\Service as BookingService;
use common\models\PaymentSystem;
use common\models\Service;
use common\models\ServiceRate;
use frontend\modules\payment\helpers\PaymentHelper;
use Svezem\Services\PaymentService\Gates\Sberbank\Input\RegisterResponse;
use Svezem\Services\PaymentService\Gates\Sberbank\SberbankGate;
use Svezem\Services\PaymentService\PaymentService;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class BookingController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index']
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ]
            ],
            'nosubdomain' => [
                'class' => NoSubdomain::class
            ],
            'filter' => [
                'class' => 'yii\filters\AjaxFilter',
                'only' => ['pay']
            ]
        ];
    }

    public function actionIndex()
    {
        $services = Service::find()
            ->where([
                'id' => [
                    Service::BOOKING_START,
                    Service::BOOKING_BUSINESS,
                    Service::BOOKING_PROFI
                ]
            ])
            ->all();

        return $this->render('index', [
            'services' => $services,
            'bookingService' => new BookingService(Yii::$app->user->id)
        ]);
    }

    public function actionPay()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $ratesId = Yii::$app->request->post('rates');
        $payType = Yii::$app->request->post('payType', 'card');

        if (empty($ratesId)) {
            Yii::$app->response->statusCode = 400;
            return [
                'msg' => 'Не выбран тариф'
            ];
        }

        $rate = ServiceRate::find()
            ->where(['id' => $ratesId[0]])
            ->andWhere([
                'service_id' => [
                    Service::BOOKING_START,
                    Service::BOOKING_BUSINESS,
                    Service::BOOKING_PROFI
                ]
            ])
            ->one();

        if ( !$rate) {
            Yii::$app->response->statusCode = 400;
            return [
                'msg' => 'Тариф не найден'
            ];
        }

        $details[] = [
            'service_rate_id' => $rate->id
        ];

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
                'returnUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/cargo-booking/']),
                'failUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/cargo-booking/']),
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
