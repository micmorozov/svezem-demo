<?php

namespace common\components\limiter\interfaces;

use Redis;

class RedisLimiterStore implements BaseLimiterStore
{
    /** @var Redis */
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $key
     * @param int $ttl
     * @return int
     */
    public function incr(string $key, int $ttl):int
    {
        $this->redis->set($key, 0, ['nx', 'ex' => $ttl]);

        return $this->redis->incr($key);
    }
}
