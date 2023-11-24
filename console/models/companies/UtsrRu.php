<?php
namespace console\models\companies;

use console\models\BaseCompany;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Yii;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;
use \yii\base\ErrorException;

class UtsrRu extends BaseCompany {
    
    public function parse(){
    	try{
    		/**
    		 * Сайт написан на Yii. В форме есть секретный ключ
    		 * поэтому на первом шаге парсим страницу, получаем этот ключ из формы и сохраняем куку.
    		 * Вторым шагом отправляем данные формы с ключом и кукой
    		 */
    		$client = new Client([
                'headers' => [
                    'User-Agent' => Yii::$app->params['userAgent']
                ]
            ]);

    		$cookies = new CookieJar;
    		$response = $client->request('GET', 'http://utsr.ru/calculation', [
    			'cookies' => $cookies
    		]);
    		
    		$html = $response->getBody()->__toString();
    		
    		include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
    		$html = str_get_html($html);
    		$tags = $html->find('form#w0 input[name=_csrf]');
    		
    		$_csrf = count($tags) ? $tags[0]->value : '';
    		
    		//отправка формы
    		$cities = $this->cities();

    		$form_params = [
                'CalculationForm[whence]' => isset($cities[$this->from_city_name]) ? $cities[$this->from_city_name] : '',
                'CalculationForm[whither]' => isset($cities[$this->to_city_name]) ? $cities[$this->to_city_name] : '',
                'CalculationForm[volume]' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
                'CalculationForm[weight]' => $this->weight,
                'CalculationForm[take]' => 0,
                'CalculationForm[deliver]' => 0,
                '_csrf' => $_csrf
            ];

    		$data = $client->post('http://utsr.ru/calculation', [
    				'form_params' => $form_params,
    				'cookies' => $cookies
    		]);
    		 
    		$data = $data->getBody()->__toString();
    		
    		if($data != '') {
    			$data = str_get_html($data);
    			
    			$tag = $data->find('div.resultWindow > div.price > div:eq(0) > div.text', 0);
    			
    			if( !$tag ){
    				$this->last_error_code = 1;
    				$this->last_error_msg = 'Не найден тэг для парсинга';
    				 
    				return false;
    			}
    			
    			$this->cost = preg_replace ( "/[^0-9]/" , "", $tag->plaintext);
    		}
    	
    		return $this->checkCost();
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
    	catch(ErrorException $e){
    		$this->last_error_code = 1;
    		$this->last_error_msg = $e->getMessage();
    		 
    		return false;
    	}
    }
    
    private function cities(){
    	return [
    			'Москва' => 1054,
    			'Санкт-Петербург' => 1055,
    			'Пермь' => 1057,
    			'Казань' => 1056,
    			'Екатеринбург' => 1058,
    	];
    }
}