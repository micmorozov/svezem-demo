<?php

namespace console\jobs\jobData;

class CargoNameData extends BaseJobData
{
    protected $_jobName = 'CargoName';

    /** @var int */
    public $cargo_id;
}
