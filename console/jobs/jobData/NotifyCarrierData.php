<?php

namespace console\jobs\jobData;

class NotifyCarrierData extends BaseJobData
{
    protected $_jobName = 'notifyCarrier';

    /** @var int */
    public $cargo_id;

    /** @var int */
    public $booking_only;

    /** @var int */
    public $repeat_by;
}
