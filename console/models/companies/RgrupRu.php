<?php
namespace console\models\companies;

use console\models\BaseCompany;
use GuzzleHttp\Client;

class RgrupRu extends BaseCompany {

    public function parse() {
    	try{
	        $client = new Client();
	        $data = $client->post('http://www.rgrup.ru/calc/calc.php', [
	            'query' => [
	                'departure' => $this->from_city_name,
	                'arrival' => $this->to_city_name,
	                'm' => $this->weight,
	                'v' => floatval($this->width) * floatval($this->height) * floatval($this->depth)
	            ]
	        ]);
	
	        //$data = $data->json();
	        $data = json_decode($data->getBody(), 1);
	        $this->cost = $data['car']['price'];
	        
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