<?php

namespace Svezem\Services\MatrixContentService\Essence;

abstract class AbstractEssence implements EssenceInterface
{
    /** @var string */
    protected $essense = '';

    public function getEssence():string
    {
        return $this->essense;
    }
}