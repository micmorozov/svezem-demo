<?php

namespace Svezem\Services\LockerService\Locker;

interface LockerInterface
{
    /**
     * Получаем блокировку. Если удалось в ответ вернется 1, иначе 0
     *
     * @param $lockTimeMS - Время(в милисекундах) блокировки. Через указанное время ключ блокировки умирает и другие потоки
     * могут завладеть блокировкой
     * @return bool
     */
    public function acquire(string $lockName, int $lockTimeMS):bool;

    /**
     * Освобождение блокировки
     * При освобождении проверяется, что блокировку установил именно этот поток, что бы не удалить блокировку другого потока
     * @return mixed
     */
    public function release(string $lockName = null);
}
