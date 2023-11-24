<?php

namespace Svezem\Services\LockerService;

use Svezem\Services\LockerService\Locker\DummyLocker;
use Svezem\Services\LockerService\Locker\LockerInterface;

class LockerService
{
    /** @var LockerInterface */
    private $locker;

    public function __construct(LockerInterface $locker = null)
    {
        $this->locker = is_null($locker) ? new DummyLocker() : $locker;
    }

    public function acquire(string $lockName, $lockTimeMS = 3000): bool
    {
        return $this->locker->acquire($lockName, $lockTimeMS);
    }

    public function release()
    {
        $this->locker->release();
    }

    public function setLocker(LockerInterface $locker):self
    {
        $this->locker = $locker;

        return $this;
    }
}
