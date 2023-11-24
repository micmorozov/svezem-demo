<?php

namespace frontend\controllers;

use common\models\City;
use common\models\Country;
use common\models\Region;
use GuzzleHttp\Client;
use yii\filters\ContentNegotiator;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;

class LocationController extends Controller {
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::className(),
                'only' => ['tree', 'list',  'yandex-list'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * @param $query
     * @return mixed
     */
    public function actionYandexList($query, $city_id)
    {
        $client = new Client();
        $city = City::find()->with('country')->where(['id' => $city_id])->asArray()->one();
        $query = urldecode($query);
        $response = $client->get('https://geocode-maps.yandex.ru/1.x/', [
            'query' =>[
                'geocode' => $city['country']['title_ru'] . " " . $city['title_ru'] . " " . $query,
                'format' => 'json'
            ]
        ]);
        $response = Json::decode($response->getBody()->getContents());
        $items = [];
        $i = 0;
        if (isset($response['response']['GeoObjectCollection']['featureMember'])){
            foreach ($response['response']['GeoObjectCollection']['featureMember'] as $object){
                $geocoderMetaData = $object['GeoObject']['metaDataProperty']['GeocoderMetaData'];
                if (isset($geocoderMetaData['kind']) && $geocoderMetaData['kind'] == "street"){
                    if (isset($geocoderMetaData['AddressDetails']['Country']['CountryName']) && $geocoderMetaData['AddressDetails']['Country']['CountryName'] == $city['country']['title_ru']){
                        if ((isset($geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['Locality']['LocalityName']) && $geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['Locality']['LocalityName'] == $city['title_ru'])
                            || (isset($geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['SubAdministrativeArea']['Locality']['LocalityName']) && $geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['SubAdministrativeArea']['Locality']['LocalityName'] == $city['title_ru'])){
                            $items[$i]['street'] = $object['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'];
                            $ll = explode(" ", $object['GeoObject']['Point']['pos']);
                            $items[$i]['longitude'] = $ll[0];
                            $items[$i]['latitude'] = $ll[1];
                            $i++;
                        }
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Lists all City models for ajax queries.
     * @param $query
     * @return mixed
     */
    public function actionList($query)
    {
        $query = urldecode($query);
        $models = Country::findList($query);
        $models = array_merge($models, Region::findList($query));
        $models = array_merge($models, City::findList($query));
        $items = [];

        foreach ($models as $key => $model) {
            $title_ru = $model['title_ru'];
            if(!empty($model['region_ru'])) {
                $title_ru .= ', ' . $model['region_ru'];
            }
            if(isset($model['country'])) {
                $title_ru .= ', ' . $model['country']['title_ru'];
            }
            if (array_key_exists('region_id', $model)){
                $city_id = $model['id'];
                $region_id = $model['region_id'];
                $country_id = $model['country_id'];
            }
            else{
                $city_id = null;
                if (isset($model['country_id'])){
                    $region_id = $model['id'];
                    $country_id = $model['country_id'];
                }
                else{
                    $region_id = null;
                    $country_id = $model['id'];
                }
            }
            $items[] = [
                'id' => $city_id . $region_id . $country_id . $title_ru,
                'title_ru' => $title_ru,
                'city_id' => $city_id,
                'region_id' => $region_id,
                'country_id' => $country_id
            ];
        }
        return $items;
    }

    /**
     * Returns tree of locations for ajax queries.
     * @param $query
     * @return mixed
     */
    public function actionTree($query)
    {
        $query = urldecode($query);

        $countriesQuery = Country::find()
            ->where(['like', 'countries.title_ru', $query])
            ->indexBy('id')
            ->asArray()
            ->all();
        $regionsQuery = Country::find()
            ->joinWith(['regions' => function ($q) use ($query){
                $q->indexBy('id')->where(['like', 'regions.title_ru', $query]);
            }])
            ->indexBy('id')
            ->asArray()
            ->all();
        $countryCitiesQuery = Country::find()
            ->joinWith(['cities' => function ($q) use ($query){
                $q->indexBy('id')->where(['and',
                    ['region_id' => null],
                    ['like', 'cities.title_ru', $query]
                ]);
            }])
            ->indexBy('id')
            ->asArray()
            ->all();
        $regionCitiesQuery = Country::find()
            ->joinWith(['regions' => function ($q) use ($query){
                $q->indexBy('id')->joinWith(['cities' => function ($q) use ($query){
                    $q->indexBy('id')->where(['like', 'cities.title_ru', $query]);
                }]);
            }])
            ->indexBy('id')
            ->asArray()
            ->all();

        foreach ($countryCitiesQuery as $key =>$country){
            if (isset($regionCitiesQuery[$key])) {
                $regionCitiesQuery[$key]['cities'] = $countryCitiesQuery[$key]['cities'];
            }
            else{
                $regionCitiesQuery[$key] = $countryCitiesQuery[$key];
            }
        }
        foreach ($regionsQuery as $key => $country){
            if (isset($regionCitiesQuery[$key])){
                $regionCitiesQuery[$key]['regions'] += $regionsQuery[$key]['regions'];
            }
            else{
                $regionCitiesQuery[$key] = $regionsQuery[$key];
            }
        }

        $all = $regionCitiesQuery + $countriesQuery;
        $tree = $this->getTree($all, ['regions', 'cities'], 'countries');
        if (!empty($tree)) {
            return $tree;
        }
        return 'По вашему запросу ничего не найдено';
    }

    private function getTree ($array, $childrenNames = [], $type){
        $list = "";
        if (count($array)){
            $list = "<ul class='list-group'>";
            foreach ($array as $parent) {
                $childrenList = '';
                if (count($childrenNames)){
                    foreach ($childrenNames as $id => $name){
                        if (isset($parent[$name]) && count($parent[$name])){
                            $childrenList .= $this->getTree($parent[$name], array_slice($childrenNames, $id+1), $childrenNames[$id]);
                        }
                    }
                }
                    $list .= "<li class='list-group-item open-list'>";
                    if (!empty($childrenList)) {
                        $list .= "<a class='list-toggle' href='#'><i class='minus'></i></a>";
                    }
                    $list .= "<a class='item-add' data-id='" . $parent['id'] ."' data-item-type='" . $type ."' href='#'>" . $parent['title_ru'] ."</a>";
                    $list .= $childrenList;
                    $list .= "</li>";
            }
            $list .= "</ul>";
        }
        return $list;
    }

    /**
     * Returns countries for ajax queries.
     * @return mixed
     */
    public function actionCountries()
    {
        $query = Country::find()->asArray()->all();
        $list = "<ul class='list-group'>";
        $list .= $this->getList($query, 'countries');
        $list .= "</ul>";
        return $list;
    }

    /**
     * Returns regions for ajax queries.
     * @return mixed
     */
    public function actionRegions($parent_id)
    {
        $regionsQuery = Region::find()->where(['country_id' => $parent_id])->asArray()->all();
        $citiesQuery = City::find()->where(['country_id' => $parent_id, 'region_id' => null])->asArray()->all();
        $list = "<ul class='list-group'>";
        $list .= $this->getList($regionsQuery, 'regions');
        $list .= $this->getList($citiesQuery, 'cities');
        $list .= "</ul>";
        return $list;
    }

    /**
     * Returns cities for ajax queries.
     * @return mixed
     */
    public function actionCities($parent_id)
    {
        $query = City::find()->where(['region_id' => $parent_id])->asArray()->all();
        $list = "<ul class='list-group'>";
        $list .= $this->getList($query, 'cities');
        $list .= "</ul>";
        return $list;
    }

    private function getList ($array, $type){
        $list = "";
        if (count($array)){
            foreach ($array as $item) {
                $has_children = false;
                if ($type == 'countries'){
                    $has_children = Region::find()->where(['country_id' => $item['id']])->exists()
                        || City::find()->where(['country_id' => $item['id'], 'region_id' => null])->exists();
                }
                if ($type == 'regions'){
                    $has_children = City::find()->where(['region_id' => $item['id']])->exists();
                }
                $list .= "<li class='list-group-item closed-list' data-id='" . $item['id'] . "' data-item-type='" . $type . "'>";
                if ($has_children) {
                    $list .= "<a class='list-toggle' href='#'><i class='plus'></i></a>";
                }
                $list .= "<input type='checkbox' id='" . $type . $item['id'] . "' class='item-add'>";
                $list .= "<label for='" . $type . $item['id'] . "'><a class='item-text' href='#'>" . $item['title_ru'] ."</a></label>";
                $list .= "</li>";
            }
        }
        return $list;
    }


}