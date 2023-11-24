<?php
namespace console\models\companies;

use GuzzleHttp\Client;
use Yii;
use console\models\BaseCompany;

use \GuzzleHttp\Exception\ClientException;

class JdeRu extends BaseCompany {
	
    public function parse(){
    	try{
	    	$client = new Client();

            $cacheKey = get_class().":getCityMode1";
            if( !$arrFrom = Yii::$app->cache->get($cacheKey) ){
                //получаем коды городов отправки
                $response = $client->request('GET', 'http://apitest.jde.ru:8000/geo/search?mode=1');
                $arrFrom = json_decode($response->getBody(),1);

                Yii::$app->cache->set($cacheKey, $arrFrom, 86400);
            }

	    	$cityFrom = $this->getCityCode($arrFrom, $this->from_city_name);
	    	
	    	if( !$cityFrom ){
	    		$this->last_error_msg = "Не известен код города отправки '{$this->from_city_name}'";
	    		
	    		return false;
	    	}

            $cacheKey = get_class().":getCityMode2";
            if( !$arrTo = Yii::$app->cache->get($cacheKey) ){
                //получаем коды городов доставки
                $response = $client->request('GET', 'http://apitest.jde.ru:8000/geo/search?mode=2');
                $arrTo = json_decode($response->getBody(),1);

                Yii::$app->cache->set($cacheKey, $arrTo, 86400);
            }

	    	$cityTo = $this->getCityCode($arrTo, $this->to_city_name);
	    	
	    	if( !$cityTo ){
	    		$this->last_error_msg = "Не известен код города получения '{$this->to_city_name}'";
	    	
	    		return false;
	    	}
	    	
	    	//калькулятор
	    	$response = $client->get('http://apitest.jde.ru:8000/calculator/price', [
    					'query' => [
    							'from' => $cityFrom,
    							'to' => $cityTo,
    							'weight' => $this->weight, 
    							'volume' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
    							'length' => $this->depth,
    							'width' => $this->width,
    							'height' => $this->height
    					]
	    			]
	    	);
	    	
	    	$data = json_decode($response->getBody(), 1);
	    	
	    	$this->cost = $data['price'];
	    	
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
    
    protected function getCityCode($arr, $cityName){
    	$res = array_filter(
    			$arr,
    			function ($item) use ($cityName){
    				return $item['city'] == $cityName;
    			}
    	);
    	
    	if( !count($res) )
    		return false;
    	
    	return array_shift($res)['code'];
    }
}