<?php
/**
 * Делаем запрос к ТК на получение стоимости доставки груза указанных габаритов
 */
namespace console\jobs;

use console\models\BaseCompany;
use GearmanJob;
use yii\helpers\Inflector;
use micmorozov\yii2\gearman\JobBase;

class ParseTkSite extends JobBase
{
	public function execute(GearmanJob $job = null)
	{
		$workload = $this->getWorkload($job);
		if(!$workload) return;

		$class_name = 'console\\models\\companies\\'.Inflector::camelize($workload['tk']['code']);

        if( !class_exists($class_name) ){
            return ;
        }

        /** @var $company BaseCompany */
		$company = new $class_name;
		$company->setAttributes($workload);
		$company->parse();
		$company->sendPubSub();
	}
}