<?php

namespace Svezem\Services\PaymentService\Gates;

use Svezem\Services\PaymentService\Classes\CallbackDataInterface;
use Svezem\Services\PaymentService\Classes\ExtendedStatusRequest;
use Svezem\Services\PaymentService\Gates\Sberbank\Output\ExtendedStatusResponse;
use Svezem\Services\PaymentService\Gates\Sberbank\Output\RegisterPaymentResponse;

interface PaymentGateInterface
{
    /**
     * Устанавливаем Логин мерчанта
     * @param string $login
     * @return $this
     */
    public function setLogin(string $login): self;

    /**
     * Устанавливаем Пароль мерчанта
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self;

    /**
     * Установка серетного ключа для проверки подписи
     * @param string $secretKey
     * @return $this
     */
    public function setSecretKey(string $secretKey): self;

    /**
     * Установка режима работы
     * @return $this
     */
    public function setTestMode(bool $testMode = true): self;

    /**
     * Регистрация платежа в плтаежной системе
     * @return string
     */
    public function registerPayment(array $req): RegisterPaymentResponse;

    /**
     * Проверка параметров callback вызова
     * @param array $query
     * @return bool
     */
    public function checkSumValidate(CallbackDataInterface $callbackData): bool;

    /**
     * Возвращает расширенный статус платежа
     * @param ExtendedStatusRequest $request
     * @return ExtendedStatusResponse
     */
    public function getExtendedStatus(ExtendedStatusRequest $request): ExtendedStatusResponse;
}