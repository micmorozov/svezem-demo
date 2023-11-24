<?php
namespace console\models\companies;

use console\models\BaseCompany;
use Exception;
use Yii;
use GuzzleHttp\Client;

class TkKitRu extends BaseCompany {

    const URL = "https://tk-kit.ru/API.1.1";

    protected $token = 'Mj4ddUVuH_-8lv3UKGn42vG6HcjvW3hU';

    public function exec($f, $params = [], $method = 'GET'){
        $opt = [
            'query' => array_merge([
                'token' => $this->token,
                'f' => $f
            ], $params)
        ];

        try {
            $client = new Client();
            return $client->request($method, self::URL, $opt);
        }
        catch(Exception $e){
            $this->last_error_code = 1;
            $this->last_error_msg = $e->getMessage();

            return false;
        }
    }

    public function parse() {
        $city_from = $this->getCity($this->from_city_name);
        $city_to = $this->getCity($this->to_city_name);

        $params = [
            'WEIGHT' => $this->weight,
            'VOLUME' => $this->volume,
            'SZONE' => $city_from['TZONEID'],
            'SCODE' => $city_from['ID'],
            'RZONE' => $city_to['TZONEID'],
            'RCODE' => $city_to['ID'],
            'PRICE' => '1'
        ];

        $data = $this->exec('price_order', $params);
        $result = json_decode($data->getBody()->__toString(), 1);

        if( isset($result['PRICE']['TOTAL']) ){
            $this->cost = $result['PRICE']['TOTAL'];

            return $this->checkCost();
        }
        else{
            return false;
        }
    }

    public function getCity($name){
        $cacheKey = get_class()."CityList";

        if( !$cityList = Yii::$app->cache->get($cacheKey) ){
            $data = $this->exec('get_city_list');
            $list = json_decode($data->getBody()->__toString(),1 );

            $cityList = [];
            foreach($list['CITY'] as $item){
                $cityList[$item['NAME']] = $item;
            }

            Yii::$app->cache->set($cacheKey, $cityList, 86400);
        }

        return isset($cityList[$name])?$cityList[$name]:false;
    }
}