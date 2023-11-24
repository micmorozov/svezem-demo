<?php

namespace Svezem\Services\PaymentService\Gates;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Svezem\Services\PaymentService\Classes\Request;
use Svezem\Services\PaymentService\Traits\AuthTrait;

abstract class AbstractGate implements PaymentGateInterface
{
    use AuthTrait;

    /**
     * Тестовый режим работы
     * @var bool
     */
    protected $testMode = true;

    /** @var Request */
    protected $request;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->request = new Request(new Client(), $this->logger);
    }

    public function setTestMode(bool $testMode = true): PaymentGateInterface
    {
        $this->testMode = $testMode;

        return $this;
    }
}