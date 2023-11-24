<?php
namespace console\models\companies;

use console\models\BaseCompany;
use GuzzleHttp\Client;
use Yii;
use yii\base\Exception;
use yii\helpers\VarDumper;
use \GuzzleHttp\Exception\ClientException;

class TesgroupRu extends BaseCompany {

    public function parse(){
    	try{
    		$cities = $this->cities();
    		    		
    		$city_from = isset($cities[$this->from_city_name]) ? $cities[$this->from_city_name] : '';
    		$city_to = isset($cities[$this->to_city_name]) ? $cities[$this->to_city_name] : '';
    		
    		if( $city_from == '' ){
    			$this->last_error_msg = "Не известен город отправки '{$this->from_city_name}'";
    			
    			return false;
    		}
    		
    		if( $city_to == '' ){
    			$this->last_error_msg = "Не известен город получения '{$this->to_city_name}'";
    			 
    			return false;
    		}
    		
    		$client = new Client([
                'headers'=>[
                    'X-Requested-With'=>'XMLHttpRequest'
                ]
            ]);

    		$data = $client->post('https://www.tesgroup.ru/bitrix/templates/.default/components/tes/calculator.main/page_new/calculate.php', [
    				'form_params' => [
    						'city_from' => isset($cities[$this->from_city_name]) ? $cities[$this->from_city_name] : '',
			                'city_to' => isset($cities[$this->to_city_name]) ? $cities[$this->to_city_name] : '',
                            'city_from_name' => $this->from_city_name,
                            'city_to_name' => $this->to_city_name,
			                'pp_coeff' => '1.1',
    						'pp_coeff_cargo' => '1.25',
			                'volume' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
			                'weight' => $this->weight,
    						'cargo' => $this->weight/100
    				]
    		]);
    		
    		$data = $data->getBody()->__toString();
    		
    		include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
    	 	$data = str_get_html($data);
            $data = $data->find('td.price strong', -1);

            if($data && count($data)) {
                $this->cost = str_replace(' ', '', $data->plaintext);
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
    
    private function cities()
    {
        return [
            'Абакан' => '317',
            'Барнаул' => '80',
            'Владивосток' => '98',
            'Волгоград' => '99',
            'Воронеж' => '100',
            'Екатеринбург' => '101',
            'Казань' => '329',
            'Когалым' => '332',
            'Комсомольск-на-Амуре' => '123',
            'Краснодар' => '334',
            'Красноярск' => '335',
            'Курск' => '337',
            'Москва' => '6',
            'Набережные Челны' => '348',
            'Нижний Новгород' => '352',
            'Новосибирск' => '354',
            'Омск' => '358',
            'Пермь' => '362',
            'Ростов-на-Дону' => '371',
            'Санкт-Петербург' => '373',
            'Стерлитамак' => '376',
            'Томск' => '381',
            'Тюмень' => '383',
            'Уфа' => '388',
            'Хабаровск' => '124',
            'Челябинск' => '390',
            'Ярославль' => '395',
            'Благовещенск' => '97',
            'Верхнекондинская' => '322',
            'Геологическая' => '324',
            'Иркутск' => '328',
            'Калининград' => '330',
            'Кемерово' => '331',
            'Коротчаево' => '333',
            'Лабытнанги' => '338',
            'Лангепас' => '339',
            'Мегион' => '344',
            'Находка' => '126',
            'Нижневартовск' => '351',
            'Новокузнецк' => '353',
            'Новый Уренгой' => '355',
            'Ноябрьск' => '356',
            'Нягань' => '357',
            'Орел' => '359',
            'Приобье' => '366',
            'Пурпе' => '368',
            'Пыть-Ях' => '369',
            'Сургут' => '377',
            'Тобольск' => '380',
            'Улан-Удэ' => '386',
            'Уссурийск' => '125',
            'Чита' => '392',
        ];
    }
}