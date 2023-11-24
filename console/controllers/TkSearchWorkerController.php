<?php
namespace console\controllers;

use GearmanJob;
use GearmanWorker;
use yii\helpers\Inflector;
use yii\console\Controller;

class TkSearchWorkerController extends Controller /*DaemonController*/
{
	/**
	 * Демон осуществляет запросы к ТК на определение стоимости груза и публикует ответ в канал редис, а оттуда через ноду клиенту
	 */
    public function actionIndex()
    {
        $worker = new GearmanWorker();
        $worker->addServer();

        $worker->addFunction("parseTkSite", [$this, 'parseSite']);

        while ($worker->work()) ;
    }

    public function parseSite($job)
    {    	
        /* @var $job GearmanJob */
        $workload = unserialize($job->workload());
        
        $class_name = 'console\\models\\companies\\'.Inflector::camelize($workload['tk']['code']);

        if( !class_exists($class_name) ){
            return ;
        }

        /* @var $company concole\models\companies\BaseCompany */
        $company = new $class_name;
        $company->setAttributes($workload);
        $company->parse();
        $company->sendPubSub();
    }

    /**
     * Для отладки без запуска воркеров
     */
    public function actionTest(){
    	$workload = [
			    'tk' => [
			       'id' => 1,
			       'name' => 'Регион Групп',
			       'code' => 'cargoexpress_ru',
			       'status' => 1,
			       'email' => 'info@rgrup.ru',
			       'phone' => 88007757456,
			       'url' => 'http://www.rgrup.ru/',
			    ],
			
			    'from_city_id' => 73,
			    'to_city_id' => 57,
			    'from_city_name' => 'Магадан',
			    'to_city_name' => 'Владивосток',
			    'weight' => 123,
			    'width' => 1,
			    'height' => 1,
			    'depth' => 0.1,
			    'socket_id' => '/#z8Wgb0UhyEkUu_ItAAAD',
			    'session_timestamp' => 1467860574634
		];
    	
    	$class_name = 'console\\models\\companies\\'.Inflector::camelize($workload['tk']['code']);
    	
    	/* @var $company concole\models\companies\BaseCompany */
    	$company = new $class_name;
    	$company->setAttributes($workload);
    	
    	if( $company->parse() ){
    		echo "cost = ".$company->cost."\n";
    	}
    	else{
    		$err = $company->getLasError();
    		echo "Error\n";
    		print_r($err);
    		echo "\n";
    	}
    	
    }
}
