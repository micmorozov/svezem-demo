<?php
namespace console\models\companies;

use console\models\BaseCompany;

use Exception;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;
use Yii;

class SteilRu extends BaseCompany {
	
    public function parse(){
    	try{
	    	$client = new Client();
	    	
	    	//калькулятор
	    	$response = $client->post('http://gadns.ddns.net:4380/1/calc.php', [
    					'form_params' => [
    							'kuda' => $this->to_city_name,
    							'ot' => $this->from_city_name,
    							'volume' => $this->volume,
    							'weight' => $this->weight
    					]
	    			]
	    	);
	    	
	    	$data = $response->getBody()->__toString();
	    	include_once(Yii::getAlias('@common/libs/simplehtmldom/') . 'simple_html_dom.php');

	    	$data = str_get_html($data);
	    	$table = $data->find('table');
	    	
	    	$cost = false;
	    	
	    	if( isset($table[0]) )
	    		$tr = $table[0]->find('tr');
	    		if( isset($tr[1]) )
	    			$td = $tr[1]->find('td');
	    			if( isset( $td[3] ) )
	    				$cost = $td[3];
	    	
	    	if($cost)
	    		$this->cost = preg_replace("/[^0-9]/", "", $cost->plaintext);
	    	
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