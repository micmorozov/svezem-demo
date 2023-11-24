<?php
namespace console\models\companies;

use GuzzleHttp\Client;
use Yii;
use console\models\BaseCompany;
use yii\base\Exception;
use yii\helpers\Json;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;

class SkifCargoRu extends BaseCompany {
	
    public function parse(){
        //новая версия
        /*$client = new \GuzzleHttp\Client();

        $this->getCity($this->from_city_name);

        try{
            $cookies = new \GuzzleHttp\Cookie\CookieJar;
            $client->request('GET', 'http://www.skif-cargo.ru/calc/', [
                'cookies' => $cookies
            ]);

            $response = $client->post('http://www.skif-cargo.ru/calc/', [
                    'query' => [
                        'BOX' => 1,
                        'CALC_PARAM_HASH' => false,
                        'WEIGHT' => $this->weight,
                        'CITY_FROM' => $this->from_city_name,
                        'CITY_TO' => $this->to_city_name,
                        'VOLUME' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
                    ],
                    'header' => [
                        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With' => 'XMLHttpRequest'
                    ],
                    'cookies' => $cookies
                ]
            );

            $data = $response->getBody()->__toString();
        }
        catch(\Exception $e) {
            $this->last_error_code = 1;
            $this->last_error_msg = $e->getMessage();

            return false;
        }*/

    	/*try{
    		$client = new \GuzzleHttp\Client();
    		
    		$response = $client->post('http://www.skif-cargo.ru/calc/indexXLStest.php?mode=small', [
	            'query' => [
		                'citysrc' => $this->from_city_name,
			            'citydst' => $this->to_city_name,
			            'weight' => $this->weight,
			            'value' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
			            'submit' => 'Посчитать'
	            	]
    			]
    		);
    		
    		$data = $response->getBody()->__toString();
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
    	
    	include_once(\Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
    	
    	try {
    		$data = str_get_html($data);
    		$data = $data->find('#maindiv font');
    		if(isset($data[1]) && !empty($data[1]->plaintext)) {
    			$this->res_status = self::STATUS_SUCCESS;
    			$this->cost = $data[1]->plaintext;
    			
    			return $this->checkCost();
    		}
    		else{
    			$this->last_error_code = 1;
    			$this->last_error_msg = 'Не удалось получить цену';
    			
    			return false;
    		}
    	}
    	catch(Exception $e) {
    		$this->last_error_code = 1;
    		$this->last_error_msg = $e->getMessage();
    		
    		return false;
    	}*/
    }

    protected function getCity($name){
        $cacheKey = get_class().":getCity";

        if( !$cities = Yii::$app->cache->get($cacheKey) ){
            $client = new Client([
                'headers' => [
                    'User-Agent' => Yii::$app->params['userAgent']
                ]
            ]);
            try {
                $response = $client->get('http://www.skif-cargo.ru/calc/');
            }
            catch(\Exception $e) {
                $this->last_error_code = 1;
                $this->last_error_msg = $e->getMessage();

                return false;
            }

            $html = $response->getBody()->__toString();
            $pos_start = strpos($html, "<select class='form-control required chosen' name='CITY_FROM'>");
            $pos_finish = strpos($html, "</select>");

            $html = substr($html, $pos_start, $pos_finish);

            $count = preg_match_all("/option data-id='(\d+)' >(.*)<\/opt/", $html, $match);

            $cities = [];
            for($i=0;$i<$count;$i++){
                //берем одно слово перед "\"
                if( preg_match("/([\w-]+) \\\/u", $match[2][$i], $match2)  )
                    $cities[$match[1][$i]] = $match2[1];
            }

            Yii::$app->cache->set($cacheKey, $cities, 86400);
        }

        return isset($cities[$name])?$cities[$name]:false;
    }
}