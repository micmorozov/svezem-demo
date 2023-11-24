<?php

namespace Svezem\Services\RateLimiter\Storage;

interface StorageInterface
{
    /**
     * Увеличивает значение по ключу и устанавливает время жизни ключа
     *
     * @param string $key
     * @param int $ttl
     * @return int новое значение ключа
     */
    public function incr(string $key, int $ttl):int;
}
