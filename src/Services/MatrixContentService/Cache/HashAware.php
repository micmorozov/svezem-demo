<?php

namespace Svezem\Services\MatrixContentService\Cache;

trait HashAware
{
    /** @var Hash */
    protected $hash;

    /**
     * @return Hash
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * @param Hash $hash
     */
    public function setHash(Hash $hash) {
        $this->hash = $hash;
    }
}