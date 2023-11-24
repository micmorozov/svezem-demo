<?php
namespace console\models\companies;

use console\models\BaseCompany;
use Exception;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;

class BaikalsrRu extends BaseCompany {
	
    public function parse(){
    	try{
    		$cityFrom = $this->getCityCode($this->from_city_name);
    		
    		if( !$cityFrom ){
    			$this->last_error_msg = "Не известен код города отправки '{$this->from_city_name}'";
    			 
    			return false;
    		}
    		$cityTo = $this->getCityCode($this->to_city_name);
    		
    		if( !$cityTo ){
    			$this->last_error_msg = "Не известен код города получения '{$this->to_city_name}'";
    		
    			return false;
    		}
    		
    		$client = new Client();
    		
    		//получаем коды городов
    		$response = $client->post('https://www.baikalsr.ru/json/api_calculator.json', [
    				'form_params' => [
    						'cargo[0][volume]' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
    						'cargo[0][weight]' => $this->weight,
    						'from[delivery]' => 0,
    						'from[guid]' => $cityFrom,
    						'insurance' => 1,
    						'to[delivery]' => 0,
    						'to[guid]' => $cityTo,
    				]
    			]
			);
    		
    		$data = json_decode($response->getBody(), 1);
    		
    		if( isset($data['total']) ){
    			$this->cost = $data['total']['int'];
    		}
	    	
	    	return $this->checkCost();
    	}
    	catch(ClientException $e){
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
    
    protected function getCityCode($cityName){
    	$client = new Client();
    	
    	$response = $client->post('https://www.baikalsr.ru/json/api_fias_cities.json', [
    			'query' => [
    				'text' => $cityName
    			]
    	]);
    	
    	$data = json_decode($response->getBody(), 1);
    	
    	return isset($data[0]) ? $data[0]['guid'] : false;
    }
}