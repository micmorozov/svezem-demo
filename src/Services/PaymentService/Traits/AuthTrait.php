<?php

namespace Svezem\Services\PaymentService\Traits;

use Svezem\Services\PaymentService\Gates\PaymentGateInterface;

trait AuthTrait
{
    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /** @var string */
    private $secretKey;

    public function setLogin(string $login): PaymentGateInterface
    {
        $this->login = $login;

        return $this;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setPassword(string $password): PaymentGateInterface
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setSecretKey(string $secretKey): PaymentGateInterface
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }
}