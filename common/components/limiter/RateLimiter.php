<?php

namespace common\components\limiter;

use common\components\limiter\interfaces\BaseLimiterStore;

class RateLimiter
{
    /** @var BaseLimiterStore */
    private $store;

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
     * @param BaseLimiterStore $store
     */
    public function __construct(BaseLimiterStore $store)
    {
        $this->store = $store;
    }

    /**
     * Добавление лимита
     *
     * @param int $requestNumber - кол-во запросов
     * @param int $timeSec - время в секундах
     * @return $this
     */
    public function addLimit($requestNumber, $timeSec)
    {
        $this->limits[$timeSec] = $requestNumber;

        return $this;
    }

    /**
     * @param $key
     * @return bool|string
     */
    public function limitExceeded($key)
    {
        $result = false;
        foreach ($this->limits as $period => $limit) {
            // Принимаем только int значения в качестве периода и лимита
            if ($limit <= 0) {
                continue;
            }
            $period = intval($period);
            if ($period <= 0) {
                continue;
            }

            $keyName = self::_buildRateLimitKeyName($key, $period);

            $val = $this->store->incr($keyName, $period);

            // Если достигли хоть одного лимита - выходим из цикла
            if ($val > $limit) {
                $result = "{$period}=>{$limit}";
                break;
            }
        }

        return $result;
    }

    /**
     * Строим наименование ключа, хранящего ограничения
     *
     * @param $key - На что ставится ограничение
     * @param $period - Наименование периода
     *
     * @return string - Наименование ключа
     */
    private function _buildRateLimitKeyName($key, $period)
    {
        return "rateLimit:{$key}:{$period}";
    }
}
