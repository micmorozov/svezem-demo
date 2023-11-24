<?php

namespace Svezem\Services\PaymentService\Classes;

interface CallbackDataInterface
{
    /**
     * Возвращает ИД в системе учета магазина
     * @return string
     */
    public function getId():string;

    /**
     * Возвращает Ид в системе учета платежной системы
     * @return int
     */
    public function getOrderId(): string;

    /**
     * Возвращает контрольную сумму из входных данных
     * @return string
     */
    public function getCheckSum(): string;

    /**
     * Возвращает набор параметров на основе которых можно рассчитывать контрольную сумму
     * @return array
     */
    public function getDataForSignature(): array;

    /**
     * Возвращает статус платежа
     * @return string
     */
    public function getStatus(): PaymentStatus;
}