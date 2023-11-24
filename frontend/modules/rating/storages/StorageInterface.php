<?php

namespace frontend\modules\rating\storages;

interface StorageInterface
{
    public function save($id, $score);

    public function get($id);

    public function isSet($id): bool;
}