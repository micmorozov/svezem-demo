<?php

namespace console\jobs;

use console\jobs\jobData\BaseJobData;
use GearmanJob;
use micmorozov\yii2\gearman\Dispatcher;
use micmorozov\yii2\gearman\JobBase;
use Yii;

abstract class BaseQueueJob extends JobBase
{
    /**
     * @param GearmanJob|null $job
     * @return mixed
     */
    public function execute(GearmanJob $job = null)
    {
        $jobObject = $this->getWorkload($job);

        if ( !($jobObject instanceof BaseJobData)) {
            Yii::error("Необходимо передать экземпляр класса BaseJobData\n".print_r($job, 1), __CLASS__);
            return false;
        }

        $this->run($jobObject);

        // Выполняем следующий job из очереди
        if ($nextJob = $jobObject->nextJob()) {
            Yii::$app->gearman->getDispatcher()->background($nextJob->getJobName(), $nextJob, Dispatcher::HIGH);
        }
    }

    /**
     * @param BaseQueueJob $job
     * @return mixed
     */
    abstract protected function run($job);
}
