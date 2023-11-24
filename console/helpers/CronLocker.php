<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 15.06.18
 * Time: 11:15
 */

namespace console\helpers;


class CronLocker
{
    /**
     * Инстанс редиса в котором хранится блокировка
     *
     * @var null
     */
    private $_redis = null;

    /**
     * Наименование блокировки
     *
     * @var string
     */
    private $_lockName = '';

    /**
     * Значение ключа блокировки. Будет хранить случайное значение,
     * что бы при удалении каждый поток удалял точно свою блокировку, а не чужую
     *
     * @var string
     */
    private $_lockValue = '';

    /**
     * LockHelper constructor.
     * @param $redis - Инстанс редиса
     * @param $lockName - Наименование блокировки
     */
    public function __construct($redis, $lockName){
        $this->_redis = $redis;

        $this->_lockName = 'locks:'.$lockName;
        // Генерим случайное значение ключа блокировки
        $this->_lockValue = openssl_random_pseudo_bytes(32);
    }

    /**
     * Получаем блокировку. Если удалось в ответ вернется 1, иначе 0
     *
     * @param $lockTimeMS - Время(в милисекундах) блокировки. Через указанное время ключ блокировки умирает и другие потоки
     * могут завладеть блокировкой
     * @return mixed
     */
    public function acquire($lockTimeMS = 3000){
        return $this->_redis->set($this->_lockName, $this->_lockValue, ['nx', 'px'=>$lockTimeMS]);
    }

    /**
     * Освобождение блокировки
     * При освобождении проверяется, что блокировку установил именно этот поток, что бы не удалить блокировку другого потока
     * @return mixed
     */
    public function release(){
        $script=<<<LUA
        if redis.call("get",ARGV[1]) == ARGV[2] then
            return redis.call("del",ARGV[1])
        else
            return 0
        end			
LUA;

        return $this->_redis->eval($script, [$this->_lockName, $this->_lockValue]);
    }
}