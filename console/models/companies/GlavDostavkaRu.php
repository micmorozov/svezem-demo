<?php
namespace console\models\companies;

use Exception;
use GuzzleHttp\Client;
use Yii;
use console\models\BaseCompany;
use yii\helpers\StringHelper;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;

class GlavDostavkaRu extends BaseCompany {

    protected $client_opt = [];

    public function init(){
        parent::init();

        $this->client_opt = [
            'curl' => [ CURLOPT_SSL_VERIFYPEER => false],
            'verify' => false
        ];
    }

    public function parse(){
    	try{
    		$client = new Client($this->client_opt);
    		
    		$cityFrom = $this->getCity($this->from_city_name);
    		if( !$cityFrom ){
    			return false;
    		}
    		
    		$cityTo = $this->getCity($this->to_city_name);
    		if( !$cityTo ){
    			return false;
    		}
    	    
    		$data = $client->get('https://glav-dostavka.ru/api/calc/', [
    				'query' => [
    					'method' => 'api_calc',
    					'responseFormat' => 'json',
    					'depPoint' => $cityFrom,
    					'arrPoint' => $cityTo,
    					'cargoKg[1]' => $this->weight,
    					'cargoMest[1]' => 1,
    					'cargoL[1]' => $this->depth,
    					'cargoW[1]' => $this->width,
    					'cargoH[1]' => $this->height,
    					'cargoCalculation[1]' => 1 //Возможные значения:
												//0 - вес, длина, ширина и высота указаны для всех мест.
												//1– вес, длина, ширина и высота указаны для одного места,
												// и общие габариты рассчитываются перемножением кол-ва
												// мест на габариты одного места.
    				]
    		]);
    		
    		$result = json_decode($data->getBody(), 1);
    		
    		if( $result && isset( $result['price'] ) ){
    			$this->cost = $result['price'];
    			
    			return $this->checkCost();
    		}
    		else{
    			$this->res_status = self::STATUS_ERROR;
    			
    			$this->last_error_code = 1;
    			$this->last_error_msg = 'Не удалось получить стоимость';
    			return false;
    		}
    	}
    	catch(ClientException $e){
    		$this->last_error_code = 1;
    		$this->last_error_msg = $e->getMessage();
    		 
    		return false;
    	}
    	catch(ServerException $e) {
    		$this->last_error_code = 1;
    		$this->last_error_msg = $e->getMessage();
    		 
    		return false;
    	}
    }
    
    protected function getCity($cityName){
    	
    	$cacheKey = get_class().":getCity";
    	
    	if ( !$result = Yii::$app->cache->get($cacheKey) ) {
    		$client = new Client($this->client_opt);
    		try {
                $data = $client->get('https://glav-dostavka.ru/api/calc/', [
                    'query' => [
                        'method' => 'api_city',
                        'responseFormat' => 'json'
                    ]
                ]);
            }
            catch(Exception $e){
                $this->last_error_code = 1;
                $this->last_error_msg = $e->getMessage();

                return false;
            }
    		 
    		$data = json_decode($data->getBody(), 1);

    		$result = [];
    		array_walk($data, function($item) use (&$result) {
				$result[$item['name']] = $item['id'];
			});
    		
    		Yii::$app->cache->set($cacheKey, $result, 86400);
    	}

    	return isset($result[$cityName]) ? $result[$cityName] : false;
    }
}