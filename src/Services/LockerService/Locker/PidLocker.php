<?php

namespace Svezem\Services\LockerService\Locker;

use frontend\modules\rating\storages\RedisStorage;

class PidLocker implements LockerInterface
{
    private $lockFilePath;

    /**
     * Получаем блокировку. Если удалось в ответ вернется 1, иначе 0
     *
     * @param $lockTimeMS - Время(в милисекундах) блокировки. Через указанное время ключ блокировки умирает и другие потоки
     * могут завладеть блокировкой
     * @return mixed
     */
    public function acquire(string $lockName, int $lockTimeMS):bool
    {
        $this->lockFilePath = sys_get_temp_dir() . "/{$lockName}.lock";
        if (file_exists($this->lockFilePath)) {
            $handle = fopen($this->lockFilePath, 'r');
            $prevPid = trim(fread($handle, filesize($this->lockFilePath)));
            fclose($handle);
            if (!empty($prevPid) && $this->psExists($prevPid)) {
                return false;
            }
        }
        $handler = fopen($this->lockFilePath, 'w+');
        fwrite($handler, getmypid());
        fclose($handler);

        return true;
    }

    /**
     * Освобождение блокировки
     * При освобождении проверяется, что блокировку установил именно этот поток, что бы не удалить блокировку другого потока
     * @return mixed
     */
    public function release(string $lockName = null)
    {
        @unlink($this->lockFilePath);
    }

    private function psExists($pid):bool
    {
        exec("ps -p $pid 2>&1", $output);
        foreach($output as $row){
            $row_array = explode(' ', trim($row));
            $check_pid = $row_array[0];
            if ($pid == $check_pid) {
                return true;
            }
        }
        return false;
    }
}
