<?php

namespace Svezem\Services\PaymentService\Traits;

trait ErrorTrait
{
    private $errorCode;

    private $errorMessage;

    public function setErrorCode(string $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function setErrorMessage(string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function isOk(): bool
    {
        return $this->errorCode == 0;
    }

}