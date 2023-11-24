<?php
namespace console\models\companies;

use console\models\BaseCompany;

use GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;

class CargoexpressRu extends BaseCompany {
	
    public function parse(){
    	try{
	    	$client = new Client();
	    	
	    	//калькулятор
	    	$response = $client->get('http://cargo-express.ru/udata/content/calculateTransportetion', [
    					'query' => [
    							'city_from' => $this->from_city_name,
    							'city_to' => $this->to_city_name,
    							'volume' => floatval($this->width) * floatval($this->height) * floatval($this->depth),
    							'weight' => $this->weight,
    							'transform' => 'modules/ajax/calculator.ajax.xsl'
    					]
	    			]
	    	);
	    	
	    	$data = $response->getBody()->__toString();
	    	
	    	$xml = simplexml_load_string($data);
	    	$cost = false;
	    	if( isset($xml->div->span) ){
		    	foreach( $xml->div->span->attributes() as $attrName => $attrVal){
		    		if( $attrName == 'class' ){
		    			if( strpos($attrVal, 'i-ico1') != false ){
		    				$cost = $xml->ul->li[1]->span[1];
		    			}
		    		}
		    	}
	    	}
	    	
	    	$this->cost = preg_replace("/[^0-9]/", '', $cost);
	    	
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