<?php
namespace console\models\companies;

use console\models\BaseCompany;
use Exception;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;
use Yii;

class TransVektorRu extends BaseCompany {
    
    public function parse(){
    	try{
    		$client = new Client();
    		$data = $client->post('http://www.trans-vektor.ru/cabinet/calculate.php', [
    				'form_params' => [
    					'out_city' => $this->from_city_name,
			            'in_city' => $this->to_city_name,
			            'obem' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
			            'ves' => $this->weight
    				]
    		]);	
    	
    		$data = $data->getBody()->__toString();
    		
	    	include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');
	        
	        if($data != '') {
	            $data = str_get_html($data);
	            $data = $data->find('#cost');
	            $this->cost = $data[0]->plaintext;
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
}