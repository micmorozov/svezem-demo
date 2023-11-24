<?php

namespace Svezem\Services\PaymentService\Gates\Sberbank\Output;

use Svezem\Services\PaymentService\Traits\ErrorTrait;

class ExtendedStatusResponse
{
    use ErrorTrait;

    /** @var string */
    private $orderNumber;

    /** @var int */
    private $orderStatus;

    public function __construct(array $response)
    {
        $this->orderNumber = $response['orderNumber'] ?? null;
        $this->orderStatus = $response['orderStatus'] ?? null;

        $this->setErrorCode($response['errorCode'] ?? 0);
        $this->setErrorMessage($response['errorMessage'] ?? '');
    }

    public function getOrderNumber():string
    {
        return $this->orderNumber;
    }

    public function grtOrderStatus(): int
    {
        return $this->orderStatus;
    }

}