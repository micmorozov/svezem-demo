<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 21.09.17
 * Time: 17:07
 */

namespace common\helpers;

use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;

class GoogleMaps extends Component
{
    //access_token
    public $key;

    // система единиц
    //metric (по умолчанию) – указывает расстояния в метрах и километрах,
    //imperial – указывает расстояния в милях и футах.
    public $units = 'metric';

    //структура ответ
    //json (рекомендуется) – задает вывод в формате JavaScript Object Notation (JSON);
    //xml – задает вывод в формате XML.
    public $dataType = 'json';

    public $language = 'ru';

    const URL = "https://maps.googleapis.com/maps/api/distancematrix/";

    public function distancematrix($origins, $destinations){

        $params = [
            'units' => $this->units,
            'language' => $this->language,
            'origins' => $origins,
            'destinations' => $destinations,
            'key' => $this->key
        ];

        $url = self::URL.$this->dataType."?".http_build_query($params);
        $client = new Client();
        $response = $client->get($url);
        return new GoogleMapsResponse($response);
    }
}

class GoogleMapsResponse {

    protected $destination_addresses;
    protected $origin_addresses;
    protected $distance;
    protected $duration;

    public function __construct(ResponseInterface $response){
        $res = $response->getBody()->__toString();
        $res = json_decode($res,1);

        if( $res === null )
            throw new Exception('Не удалось декодировать JSON');

        if( $res['status'] != 'OK' ) {
            $errmsg = isset($res['error_message'])?$res['error_message'] :$res['status'];

            throw new Exception($errmsg);
        }

        $this->destination_addresses = $res['destination_addresses'];
        $this->origin_addresses = $res['origin_addresses'];

        if( $res['rows'][0]['elements'][0]['status'] == 'OK' ) {
            $this->distance = $res['rows'][0]['elements'][0]['distance'];
            $this->duration = $res['rows'][0]['elements'][0]['duration'];
        }
        else{
            $this->distance = $this->duration = null;
        }
    }

    public function getDestinationAddresses(){
        return $this->destination_addresses[0];
    }

    public function getOriginAddresses(){
        return $this->origin_addresses[0];
    }

    /**
     * @param string $type text|value
     * @return mixed
     */
    public function getDistance($type = 'value'){
        return isset($this->distance[$type]) ? $this->distance[$type] : null;
    }

    /**
     * @param string $type text|value
     * @return null
     */
    public function getDuration($type = 'value'){
        return isset($this->duration[$type]) ? $this->duration[$type] : null;
    }
}