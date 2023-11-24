<?php

namespace Svezem\Services\MatrixContentService\Storage;

use Redis;

class RedisStorage implements StorageInterface
{
    /** @var Redis */
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Стартуем транзакцию
     * @return bool
     */
    public function beginTransaction()
    {
        $this->redis->multi(Redis::PIPELINE);
    }

    /**
     * Завершаем транзакцию
     * @return bool
     */
    public function commitTransaction()
    {
        $this->redis->exec();
    }

    /**
     * Очищаем хранилище
     * @return bool
     */
    public function clearAll(string $essenceKey)
    {
        $this->redis->del($essenceKey);
    }

    /**
     * Увеличиваем счетчик в хранилище
     * @param string $key
     * @param float $value
     * @return float
     */
    public function incr(string $essenceKey, string $fieldKey, float $value)
    {
        $this->redis->hIncrBy($essenceKey, $fieldKey, $value);
    }

    public function get(string $essenceKey, string $fieldKey): string
    {
        return (string)$this->redis->hGet($essenceKey, $fieldKey);
    }
}