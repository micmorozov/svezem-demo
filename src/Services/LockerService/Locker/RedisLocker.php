<?php

namespace Svezem\Services\LockerService\Locker;

use frontend\modules\rating\storages\RedisStorage;

class RedisLocker implements LockerInterface
{
    /** @var \Redis */
    private $_redis;

    private $_locks = [];

    public function __construct(\Redis $redis)
    {
        $this->_redis = $redis;
    }

    /**
     * Получаем блокировку. Если удалось в ответ вернется 1, иначе 0
     *
     * @param $lockTimeMS - Время(в милисекундах) блокировки. Через указанное время ключ блокировки умирает и другие потоки
     * могут завладеть блокировкой
     * @return mixed
     */
    public function acquire(string $lockName, int $lockTimeMS):bool
    {
        if(array_key_exists($lockName, $this->_locks))
            return true;

        // Генерим случайное значение ключа блокировки
        $this->_locks[$lockName] = openssl_random_pseudo_bytes(32);

        return $this->_redis->set($this->getKey($lockName), $this->_locks[$lockName], ['nx', 'px'=>$lockTimeMS]);
    }

    /**
     * Освобождение блокировки
     * При освобождении проверяется, что блокировку установил именно этот поток, что бы не удалить блокировку другого потока
     * @return mixed
     */
    public function release(string $lockName = null)
    {

        $script=<<<LUA
        if redis.call("get",ARGV[1]) == ARGV[2] then
            return redis.call("del",ARGV[1])
        else
            return 0
        end			
LUA;

        if($lockName){
            if(array_key_exists($lockName, $this->_locks)) {
                $this->_redis->eval($script, [$this->getKey($lockName), $this->_locks[$lockName]]);
            }
        }else {
            foreach ($this->_locks as $lockName => $lockValue) {
                $this->_redis->eval($script, [$this->getKey($lockName), $lockValue]);
            }
        }
    }

    private function getKey(string $key):string
    {
        return "lockerService:{$key}";
    }
}
