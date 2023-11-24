<?php
namespace console\models\companies;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Yii;
use console\models\BaseCompany;
use \GuzzleHttp\Exception\ClientException;

class EmckRu extends BaseCompany {
	
    public function parse(){
    	try{
    		$client = new Client();
    		
    		$cookies = new CookieJar;
    		$response = $client->get('https://emck.ru/', [
    				//'cookies' => $cookies
    		]);
    		
    		$response = $client->post('https://emck.ru/wp-content/plugins/emsk/inc/ajax.php', [
    				'form_params' => [
    						'clc' => true,
    						'emsk_auto' => 'Отдельная машина (стандарт)',
    						'emsk_from' => 'Москва',
    						'emsk_from_sel' => 'Забрать у отправителя',
    						'emsk_max_length' => 1,
    						'emsk_max_meter' => 'м',
    						'emsk_pay' => 'Оплачивает отправитель',
    						'emsk_pay_recipient' => 'Наличный расчёт',
    						'emsk_pay_sender' => 'Наличный расчёт',
    						'emsk_screen_res' => '1920x1080',
    						'emsk_sel_meter' => 'м',
    						'emsk_sel_size' => 'м',
    						'emsk_sel_weight' => 'кг',
    						'emsk_stat_sum'	=> 3900,
    						'emsk_to' => 'Омск',
    						'emsk_to_sel' => 'Самостоятельно заберут со склада',
    						'emsk_to_stock' => 'Склад - малый',
    						'emsk_train' =>	'Стандарт',
    						'emsk_type_delivery' => 'ЖД',
    						'emsk_type_size' =>	'Обьем',
    						'emsk_volume' => 0.1,
    						'emsk_weight' => 123
    				],
    				'cookies' => $cookies
    		]);
	    	
	    	return $this->checkCost();
    	}
    	catch(ClientException $e){
    		$response = $e->getResponse();
    		$responseBodyAsString = $response->getBody()->getContents();
    		
    		print_r($responseBodyAsString);die;
    		
    		$this->last_error_code = 1;
    		$this->last_error_msg = $e->getMessage();
    		
    		return false;
    	}
    	catch(Exception $e) {
    		$this->last_error_code = 1;
    		$this->last_error_msg = $e->getMessage();
    		
    		return false;
    	}
    }
}