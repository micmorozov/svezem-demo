<?php
namespace console\models\companies;

use GuzzleHttp\Client;
use Yii;
use console\models\BaseCompany;
use \GuzzleHttp\Exception\ClientException;

class RatekRu extends BaseCompany {
	
	private $auth_key = 'NNtu1Z9hnk4y52RC9gsTKCzax';
	
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
    		$data = $client->post( 'http://api.rateksib.ru/v1/calc.json', [
    				'form_params' => [
    						'key' => $this->auth_key,
    						'weight' => $this->weight,
    						'volume' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
    						'width' => $this->width,
    						'height' => $this->height,
    						'length' => $this->depth,
    						'price' => 1,
    						'marked' => false,
    						'negabarit' => false,
    						'weight_ng' => 0,
    						'volume_ng' => 0,
    						'from' => $cityFrom,
    						'to' => $cityTo
    						
    				]
    		]);
    		
    		$data = json_decode($data->getBody(), 1);
    		$this->cost = isset($data['totalAuto']) ? $data['totalAuto'] : false;
	    	
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
    	$cacheKey = get_class().":getCity";
    	 
    	if ( !$result = Yii::$app->cache->get($cacheKey) ) {
    		$client = new Client();
    		$data = $client->post( 'http://api.rateksib.ru/v2/cityList.json', [
    				'form_params' => [
    					'key' => $this->auth_key
    				]
    		]);
    		 
    		$data = json_decode($data->getBody(), 1);
    	
    		$result = [];
    		array_walk($data, function($item) use (&$result) {
    			$result[$item['name']] = $item['LocationId'];
    		});
    		
    		Yii::$app->cache->set($cacheKey, $result, 86400);
    	}
    	
    	return isset($result[$cityName]) ? $result[$cityName] : false;
    }
}