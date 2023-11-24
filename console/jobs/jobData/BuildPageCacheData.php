<?php

namespace console\jobs\jobData;

class BuildPageCacheData extends BaseJobData
{
    protected $_jobName = 'buildPageCache';

    /** @var int */
    public $cargo_id;

    /** @var int */
    public $transport_id;

    /** @var int */
    public $tk_id;
}
