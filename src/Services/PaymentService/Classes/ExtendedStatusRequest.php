<?php
/**
 * Запрос расширенного статуса заказа
 */

namespace Svezem\Services\PaymentService\Classes;

class ExtendedStatusRequest
{
    private $id;

    /**
     * @param int $id ИД заказа в систему учета магазина
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}