<?php
/**
 * Класс, описывающий данные приходящие в callback от сбербанка
 */
namespace Svezem\Services\PaymentService\Gates\Sberbank\Input;

use Svezem\Services\PaymentService\Classes\PaymentStatus;
use Svezem\Services\PaymentService\Classes\CallbackDataInterface;

class CallbackData implements CallbackDataInterface
{
    /** @var int|mixed  */
    protected $mdOrder;

    /** @var int|mixed  */
    private $orderNumber;

    /** @var mixed|string  */
    private $checksum;

    /** @var mixed|string  */
    private $operation;

    /** @var int|mixed  */
    private $status;

    public function __construct(array $inputData)
    {
        $this->mdOrder = $inputData['mdOrder'] ?? 0;
        $this->orderNumber = $inputData['orderNumber'] ?? 0;
        $this->checksum = $inputData['checksum'] ?? '';
        $this->operation = $inputData['operation'] ?? '';
        $this->status = $inputData['status'] ?? 0;
    }

    public function getId(): string
    {
        return $this->orderNumber;
    }

    public function getOrderId(): string
    {
        return $this->mdOrder;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getStatus(): PaymentStatus
    {
        if($this->status && $this->operation == 'deposited')
            $paymentStatus = new PaymentStatus(PaymentStatus::STATUS_SUCCESS);
        else
            $paymentStatus = new PaymentStatus(PaymentStatus::STATUS_FAIL);

        return $paymentStatus;
    }

    public function getDataForSignature(): array
    {
        return [
            'mdOrder' => $this->mdOrder,
            'orderNumber' => $this->orderNumber,
            'operation' => $this->operation,
            'status' => $this->status
        ];
    }

}