<?php

namespace Svezem\Services\RateLimiter;

use Svezem\Services\RateLimiterService\Exception\WrongLimitParamException;
use Svezem\Services\RateLimiterService\Storage\StorageInterface;

class RateLimiter
{
    /** @var StorageInterface */
    private $storage;

    /**
     * @var array $limits - Массив с ограничениями
     * [
     *       секунд => количество запросов
     *       1 => 10,
     *       60 => 300,
     *       3600 => 1000,
     *       86400   => 2500
     * ]
     */
    private $limits = [];

    /**
     * RateLimiter constructor.
     * @param StorageInterface $store
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Добавление лимита
     *
     * @param int $requestNumber - количество запросов
     * @param int $timeSec - время в секундах
     * @return $this
     */
    public function addLimit(int $requestNumber, int $timeSec): self
    {
        if($requestNumber <= 0 || $timeSec <= 0)
            throw new WrongLimitParamException("'requestNumber' and 'timeSec' must be greater than 0");

        $this->limits[$timeSec] = $requestNumber;

        return $this;
    }

    /**
     * Достигнут ли лимит
     * @param $key
     * @return bool|string
     */
    public function limitExceeded($obj): bool
    {
        foreach ($this->limits as $period => $limit) {
            $keyName = self::_buildRateLimitKeyName($obj, $period);

            $val = $this->storage->incr($keyName, $period);

            // Если достигли хоть одного лимита - выходим из цикла
            if ($val > $limit) {
                $result = "{$period}=>{$limit}";
                return true;
            }
        }

        return false;
    }

    /**
     * Строим наименование ключа, хранящего ограничения
     *
     * @param $key - На что ставится ограничение
     * @param $period - Наименование периода
     *
     * @return string - Наименование ключа
     */
    private function _buildRateLimitKeyName($obj, $period): string
    {
        $key = serialize($obj);
        $className = get_called_class();
        return "{$className}:{$key}:{$period}";
    }
}
