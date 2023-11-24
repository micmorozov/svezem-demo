<?php
/**
 * Определяем гео координаты груза
 */
namespace console\jobs;

use GearmanJob;
use GuzzleHttp\Client;
use micmorozov\yii2\gearman\JobBase;
use yii\helpers\Json;
use common\models\Cargo;
use common\models\CargoLocation;
use common\models\City;

class LocateGeoCodeCargo extends JobBase
{
	public function execute(GearmanJob $job = null)
	{
		$workload = $this->getWorkload($job);
		if(!$workload) return;

		$cargo = Cargo::findOne($workload['cargo_id']);
		if(!$cargo) return;

		$client = new Client();
		$cargoLocations = $workload['cargoLocations'];
		if (count($cargoLocations)) {
			foreach ($cargoLocations as $id => $location) {
				if (!isset($location['house'])){
					$cargoLocations[$id]['house'] = '';
				}
				if (!isset($location['loaders'])){
					$cargoLocations[$id]['loaders'] = CargoLocation::LOADERS_NOT_NEEDED;
				}

				$cargoLocations[$id]['longitude'] = '';
				$cargoLocations[$id]['latitude'] = '';
				$city = City::find()->with('country')->where(['id' => $location['city_id']])->asArray()->one();
				$response = $client->get('https://geocode-maps.yandex.ru/1.x/', [
					'query' => [
						'geocode' => $city['country']['title_ru'] . " " . $city['region_ru'] . " " . $city['title_ru'],
						'format' => 'json',
						'results' => 1
					]
				]);
				$response = Json::decode($response->getBody()->getContents());
				if (isset($response['response']['GeoObjectCollection']['featureMember'])) {
					foreach ($response['response']['GeoObjectCollection']['featureMember'] as $object) {
						$geocoderMetaData = $object['GeoObject']['metaDataProperty']['GeocoderMetaData'];
						if (isset($geocoderMetaData['AddressDetails']['Country']['CountryName']) && $geocoderMetaData['AddressDetails']['Country']['CountryName'] == $city['country']['title_ru']) {
							if ((isset($geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['Locality']['LocalityName']) && $geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['Locality']['LocalityName'] == $city['title_ru'])
								|| (isset($geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['SubAdministrativeArea']['Locality']['LocalityName']) && $geocoderMetaData['AddressDetails']['Country']['AdministrativeArea']['SubAdministrativeArea']['Locality']['LocalityName'] == $city['title_ru'])
							) {
								$ll = explode(" ", $object['GeoObject']['Point']['pos']);
								$cargoLocations[$id]['longitude'] = $ll[0];
								$cargoLocations[$id]['latitude'] = $ll[1];
							}
						}
					}
				}

				if (!empty($location['house'])) {
					$response = $client->get('https://geocode-maps.yandex.ru/1.x/', [
						'query' => [
							'geocode' => $location['street'] . " " . $location['house'],
							'format' => 'json',
							'results' => 1
						]
					]);
					$response = Json::decode($response->getBody()->getContents());
					if (isset($response['response']['GeoObjectCollection']['featureMember'])) {
						foreach ($response['response']['GeoObjectCollection']['featureMember'] as $object) {
							$geocoderMetaData = $object['GeoObject']['metaDataProperty']['GeocoderMetaData'];
							if (isset($geocoderMetaData['precision']) && $geocoderMetaData['precision'] == "exact") {
								$ll = explode(" ", $object['GeoObject']['Point']['pos']);
								$cargoLocations[$id]['longitude'] = $ll[0];
								$cargoLocations[$id]['latitude'] = $ll[1];
							}
						}
					}
				}
			}
		}

		$cargo->updateLocations(false, $cargoLocations);
	}
}