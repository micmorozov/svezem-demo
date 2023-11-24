<?php
namespace console\models\companies;

use console\models\BaseCompany;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;
use Yii;

class Meridian60Ru extends BaseCompany {
    
    public function parse(){
    	$cities = $this->cities();
    	
    	try{
	    	$client = new Client();
	    	
	    	$responde = $client->post('http://www.meridian60.ru/calc/', [
	    			'form_params' => [
	    					'cfrom' => isset($cities[$this->from_city_name]) ? $cities[$this->from_city_name] : '',
	    					'cto' => isset($cities[$this->to_city_name]) ? $cities[$this->to_city_name] : '',
	    					'cves' => $this->weight,
	    					'cdef' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
	    					'cadr1' => '',
	    					'cadr2' => '',
	    					'calcul' => ''
	    			]
	    	]);
	    	
	    	$data = $responde->getBody()->__toString();
	    	//print_r($data);die;
	    	
	    	include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
	    	
	    	$data = str_get_html($data);
	    	$data = $data->find('p.tabletext font b');
	    	
	    	if(isset($data[0])) {
	    		$data = $data[0]->plaintext;
	    		$this->res_status = self::STATUS_SUCCESS;
	    		$this->cost = substr($data, 0, strpos($data, 'руб.') - 1);
	    	
	    		return $this->checkCost();
	    	}
	    	else{
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

    private function cities(){
        return [
            "Анапа" => "16",
            "Геленджик" => "20",
            "Екатеринбург" => "1",
            "Краснодар" => "17",
            "Крымск" => "19",
            "Курган" => "2",
            "Москва" => "8",
            "Новоросийск" => "18",
            "Нягань" => "10",
            "Пермь" => "3",
            "Санкт-Петербург" => "15",
            "Серов" => "11",
            "Сургут" => "4",
            "Тюмень" => "5",
            "Урай" => "12",
            "Уфа" => "6",
            "Челябинск" => "7",
            "Югорск-Советский" => "13",
        ];
    }

}