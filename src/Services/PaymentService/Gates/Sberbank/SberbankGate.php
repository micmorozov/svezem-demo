<?php

namespace Svezem\Services\PaymentService\Gates\Sberbank;

use Svezem\Services\PaymentService\Classes\ExtendedStatusRequest;
use Svezem\Services\PaymentService\Gates\AbstractGate;
use Svezem\Services\PaymentService\Classes\CallbackDataInterface;
use Svezem\Services\PaymentService\Gates\Sberbank\Output\ExtendedStatusResponse;
use Svezem\Services\PaymentService\Gates\Sberbank\Output\RegisterPaymentResponse;

class SberbankGate extends AbstractGate
{
    /**
     * Регистрация платежа в платежной системе
     * @param array $req
     * @return string
     */
    public function registerPayment(array $req): RegisterPaymentResponse
    {
        $req['userName'] = $this->getLogin();
        $req['password'] = $this->getPassword();

        $response = $this->request->post($this->getServerUrl() . '/register.do', $req);

        return new RegisterPaymentResponse($response);
    }

    /**
     * Возвращает расширенный статус платежа
     * @param ExtendedStatusRequest $request
     * @return ExtendedStatusResponse
     */
    public function getExtendedStatus(ExtendedStatusRequest $request): ExtendedStatusResponse
    {
        $req['userName'] = $this->getLogin();
        $req['password'] = $this->getPassword();
        $req['orderNumber'] = $request->getId();

        $response = $this->request->post($this->getServerUrl() . '/getOrderStatusExtended.do', $req);

        return new ExtendedStatusResponse($response);
    }

    public function checkSumValidate(CallbackDataInterface $callbackData): bool
    {
        $query = $callbackData->getDataForSignature();

        ksort($query);

        $str = '';
        foreach ($query as $key => $val)
            $str .= "$key;$val;";

        $sign = strtoupper(hash_hmac('sha256', $str, $this->getSecretKey()));

        $valid = $sign === $callbackData->getCheckSum();

        if(!$valid)
            $this->logger->error("String for sign: {$str}, secretKey={$this->getSecretKey()} => {$sign}, input sign: {$callbackData->getCheckSum()}", $query);

        return $valid;
    }

    private function getServerUrl(): string
    {
        return $this->testMode ? 'https://3dsec.sberbank.ru/payment/rest' : 'https://securepayments.sberbank.ru/payment/rest';
    }
}