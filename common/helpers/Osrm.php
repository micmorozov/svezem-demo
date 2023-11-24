<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 19.04.18
 * Time: 10:51
 */

namespace common\helpers;

use Exception;
use GuzzleHttp\Client;
use Yii;

class Osrm
{
    const URL = 'http://router.project-osrm.org';
    const VERSION = 'v1';
    const PARAMS = [
        'geometries' => 'geojson',
        'alternatives' => 'true',
        'steps' => 'false',
        'generate_hints' => 'false'
    ];

    /**
     * @param $service
     * @param $profile
     * @param $coordinates
     * @param array $params
     * @return bool|string
     */
    static public function query($service, $profile, $coordinates, $params = [])
    {
        $params = array_merge(self::PARAMS, $params);

        $query = sprintf('%s/%s/%s/%s/%s', self::URL, $service, self::VERSION, $profile,
                $coordinates).'?'.http_build_query($params);

        $client = new Client();
        try{
            $response = $client->get($query);
        } catch (Exception $e){
            // TODO Почему-то скрипт зависает на выводе ошибки в лог
            //Yii::error('Не удалось выполнить запрос '.$query.' '.$e->getMessage(), 'Osrm.query');
            return false;
        }

        return $response->getBody();
    }

    /**
     * @param $lon1
     * @param $lat1
     * @param $lon2
     * @param $lat2
     * @param array $params
     * @return bool|OsrmRoute
     */
    static public function getRoute($lon1, $lat1, $lon2, $lat2, $params = [])
    {
        $coordinates = "$lon1,$lat1;$lon2,$lat2";

        $response = self::query('route', 'driving', $coordinates, $params);

        if ( !$response) {
            return false;
        }

        try{
            return new OsrmRoute($response);
        } catch (Exception $e){
            return false;
        }
    }
}

class OsrmRoute
{

    protected $routes = [];

    /**
     * OsrmRoute constructor.
     * @param $response
     * @throws Exception
     */
    public function __construct($response)
    {
        if ( !$response = json_decode($response, 1)) {
            throw new Exception('Не удалось декодировать JSON');
        }

        if ($response['code'] != 'Ok') {
            throw new Exception('Ответ содержит ошибку');
        }

        if (isset($response['routes'])) {
            $this->routes = $response['routes'];
        }
    }

    public function getRoutes()
    {
        return $this->routes;
    }

}
