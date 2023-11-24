<?php

namespace Svezem\Services\PaymentService\Gates\Sberbank\Output;

use Svezem\Services\PaymentService\Traits\ErrorTrait;

class RegisterPaymentResponse
{
    use ErrorTrait;

    /** @var string  */
    private $orderId;

    /** @var string */
    private $formUrl;

    /** @var array */
    private $externalParams;

    public function __construct(array $response)
    {
        $this->orderId = $response['orderId'] ?? '';
        $this->formUrl = $response['formUrl'] ?? '';
        $this->externalParams = $response['externalParams'] ?? [];

        $this->setErrorCode($response['errorCode'] ?? 0);
        $this->setErrorMessage($response['errorMessage'] ?? '');
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getFormUrl() : string
    {
        return $this->formUrl;
    }

    public function getExternalParams(): array
    {
        return $this->externalParams;
    }
}