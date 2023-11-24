<?php

namespace Svezem\Services\LockerService\Locker;

class DummyLocker implements LockerInterface
{
    public function acquire(string $lockName, int $lockTimeMS): bool
    {
        return true;
    }

    public function release(string $lockName = null)
    {
    }
}
