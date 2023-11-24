<?php

namespace console\jobs\jobData;

class AutoCategoryData extends BaseJobData
{
    protected $_jobName = 'AutoCategory';

    /** @var int */
    public $cargo_id;

    /** @var int */
    public $transport_id;

    /** @var bool */
    public $saveCategories;
}
