<?php

namespace Svezem\Services\PaymentService\Classes;

class PaymentStatus
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    /** @var string */
    private $paymentStatus;

    public function __construct(string $paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    }

    public function isSuccess(): bool
    {
        return $this->paymentStatus == self::STATUS_SUCCESS;
    }
}