<?php

namespace console\jobs\jobData;

abstract class BaseJobData
{
    protected $_jobName;

    /** @var BaseJobData|null */
    protected $_nextJob = null;

    /**
     * @param $name
     */
    public function setJobName($name)
    {
        $this->_jobName = $name;
    }

    /**
     * @return mixed
     */
    public function getJobName()
    {
        return $this->_jobName;
    }

    /**
     * @param BaseJobData $job
     * @return $this
     */
    public function addJob(BaseJobData $job)
    {
        if ( !$this->_nextJob) {
            $this->_nextJob = $job;
        } else {
            $this->_nextJob->addJob($job);
        }

        return $this;
    }

    /**
     * @return BaseJobData|null
     */
    public function nextJob()
    {
        return $this->_nextJob;
    }
}
