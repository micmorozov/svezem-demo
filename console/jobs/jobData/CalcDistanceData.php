<?php

namespace console\jobs\jobData;

class CalcDistanceData extends BaseJobData
{
    protected $_jobName = 'CalcDistance';

    /** @var int */
    public $behavior;

    /** @var  string */
    public $objectClass;

    /** @var int */
    public $objectId;

    /** @var string */
    public $city_from;

    /** @var string */
    public $city_to;

    /** @var string */
    public $socket_id;
}
