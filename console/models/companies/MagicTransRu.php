<?php
namespace console\models\companies;

use console\models\BaseCompany;
use Exception;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;

class MagicTransRu extends BaseCompany {

    public function parse(){

        try{
            $client = new Client();

            $data = $client->get('http://magic-trans.ru/include/mt-calculation.php', [
                'query' => [
                    'chCityFrom' => false,
                    'chCityTo' => false,
                    'dataCube' => $this->volume,
                    'dataMass' => $this->weight,
                    'strCityFrom' => $this->from_city_name,
                    'strCityTo' => $this->to_city_name
                ]
            ]);

            $data = json_decode($data->getBody()->__toString(), 1);

            if( $data['ERROR'] == 0 ){
                $this->cost = $data['COST'];
                $this->checkCost();
                return true;
            }
            else{
                $this->last_error_code = 1;
                $this->last_error_msg = "Сервер вернул ошибку\n".print_r($data,1);

                return false;
            }
        }
        catch(Exception $e){
            $this->last_error_code = 1;
            $this->last_error_msg = $e->getMessage();

            return false;
        }


    	/*try{
    		$client = new \GuzzleHttp\Client();
    		$data = $client->post('http://magic-trans.ru/', [
    				'form_params' => [
    					'city_from'	=> $this->from_city_name,
						'city_to' => $this->to_city_name,
						'volume'=> floatval($this->width) * floatval($this->height) * floatval($this->depth),
						'weight' => $this->weight
    				]
    		]);
    		
    		$data = $data->getBody()->__toString();
    		
    		include_once(\Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
    		$data = str_get_html($data);
    		
    		$tag = $data->find('div.mwContent > table > td', -1);
    		
    		if( !$tag ){
    			$this->last_error_code = 1;
    			$this->last_error_msg = 'Не найден тэг для парсинга';
    			 
    			return false;
    		}
    		
    		$this->cost = $tag->plaintext;
    		
    		return true;
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
    	}*/
    }
    
    protected function cities(){
    	return [
            "Москва" => "121",
            "Санкт-Петербург" => "127",
            "Дербент" => "116",
            "Екатеринбург" =>"117",
            "Когалым" =>"118",
            "Краснодар" =>"119",
            "Махачкала" =>"120",
            "Нефтеюганск" =>"122",
            "Нижневартовск" =>"123",
            "Новосибирск" =>"399",
            "Новый Уренгой" =>"124",
            "Ноябрьск" =>"125",
            "Ростов-на-Дону" =>"126",
            "Сургут" =>"128",
            "Тюмень" =>"216",
            "Челябинск" =>"464",
        ];
    }
}