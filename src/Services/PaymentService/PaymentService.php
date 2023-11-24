<?php

namespace Svezem\Services\PaymentService;

use Svezem\Services\PaymentService\Classes\ExtendedStatusRequest;
use Svezem\Services\PaymentService\Classes\CallbackDataInterface;
use Svezem\Services\PaymentService\Gates\PaymentGateInterface;
use Svezem\Services\PaymentService\Gates\Sberbank\Output\RegisterPaymentResponse;

class PaymentService
{
    /** @var PaymentGateInterface */
    private $paymentGate;

    public function __construct(PaymentGateInterface $paymentGate)
    {
        $this->paymentGate = $paymentGate;
    }

    /**
     * Получаем платежный URL
     * @param array $req
     * @return string
     */
    public function registerPayment(array $req): RegisterPaymentResponse
    {
        return $this->paymentGate->registerPayment($req);
    }

    /**
     * Проверка параметров из callback уведомления
     * @param array $query
     * @return bool
     */
    public function checkSumValidate(CallbackDataInterface $callbackData): bool
    {
        return $this->paymentGate->checkSumValidate($callbackData);
    }

    /**
     * Возвращает расширенный статус платежа
     * @param int $orderId
     * @return mixed
     */
    public function getExtendedStatus(ExtendedStatusRequest $extendedStatusRequest)
    {
        return $this->paymentGate->getExtendedStatus($extendedStatusRequest);
    }
}