<?php
/**
 * Обновляем базу IP адресов
 * Запуск скриптов происходит из shell скриптов, которые предварительно закачивают базу с серверов
 * Скрипты лежат в app/cron/geoip
 * 
 */
namespace console\controllers;

use PDO;
use yii\console\Controller;
use Yii;

class UpdateGeoIpController extends Controller{

    public $path;

    public function options($actionID)
    {
        return ['path'];
    }

    /**
     * Обновляем базу адресов с сайта http://ipgeobase.ru
     */
    public function actionIpGeoBase(){
        $connection = Yii::$app->db;

        Yii::trace('Заполняем временные таблицы из файлов с данными...', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');
        $result = $this->_initTmpTables(dirname(__FILE__) . '/geoip/ipgeobase.tpl.sql', array(
            ':IPGeoBase_Cities' => $this->path.'/cities.txt',
            ':IPGeoBaseCitiesLocation' => $this->path.'/cidr_optim.txt'
        ));
        if($result){
            Yii::error("Произошла ошибка: {$result}", 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');
        }
        Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');

		Yii::trace('Исправляем ошибки с регионами в БД IPGeoBase...', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');
		$sql = 'UPDATE `tmp_ipgeobase_cidr_city` SET `oblName`="Киевская область" WHERE `oblName`="Киев";
				UPDATE `tmp_ipgeobase_cidr_city` SET `oblName`="Московская область" WHERE `oblName`="Москва";
				UPDATE `tmp_ipgeobase_cidr_city` SET `oblName`="Ленинградская область" WHERE `oblName`="Санкт-Петербург";				
				UPDATE `tmp_ipgeobase_cidr_city` SET `oblName`="Республика Крым" WHERE `oblName`="Крым";';
		$connection->createCommand($sql)->execute();
		Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');

        $transaction=$connection->beginTransaction();
        $connection->createCommand('TRUNCATE TABLE `geo_network`')->execute();

        Yii::trace('Заполняем сети...', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');
        // Сети, привязаные к стране и не имеющие привязки к городу
		// Нам нужны только города
        /*$sql = 'REPLACE geo_network (`beginip`, `endip`, `length`, `cityid`, `regionid`, `countryid`)
				SELECT startIpNum, endIpNum, endIpNum-startIpNum length, 0, 0,  c.id
				FROM `tmp_ipgeobase_cidr_location` ticl
				INNER JOIN `countries` c ON c.code = ticl.countryCode
				WHERE `cityId`=0 AND countryCode IN ("RU", "UA");';*/

        // Сети привязаные к городу
        // Порядок обновления важен. Сначала более общие значения, потом более точные занчения, что бы значения не переписались
        $sql = 'REPLACE geo_network (`beginip`, `endip`, `length`, `cityid`, `regionid`, `countryid`)
				SELECT startIpNum, endIpNum, endIpNum-startIpNum length, gci.`id`, reg.`id`, countries.id
                FROM `tmp_ipgeobase_cidr_location` loc
                INNER JOIN `tmp_ipgeobase_cidr_city` ci ON ci.cityId = loc.cityId
                INNER JOIN `countries` ON countries.code = loc.countryCode
                INNER JOIN `regions` reg ON reg.`title_ru` = ci.`oblName` 
                INNER JOIN `cities` gci ON gci.`title_ru` = ci.`cityName` AND gci.region_id = reg.id
                WHERE loc.countryCode IN ("RU", "UA");';

        $connection->createCommand($sql)->execute();
        Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');

        $transaction->commit();

        Yii::trace('Удаляем таблицы...', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');
        $sql = 'DROP TABLE IF EXISTS `tmp_ipgeobase_cidr_location`;
				DROP TABLE IF EXISTS `tmp_ipgeobase_cidr_city`;';
        //$connection->createCommand($sql)->execute();
        Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.IPGeoBase');

        return 0;
    }

    /**
     * Обновляем базу адресов с сайта http://maxmind.com
     * Только страны
     */
    /*
public function actionMaxmindCountry(){
    $connection = Yii::$app->db;

    Yii::trace('Заполняем временные таблицы из файлов с данными...', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');
    $this->_initTmpTables(dirname(__FILE__) . '/geoip/maxmind_country.tpl.sql', array(
        ':GeoLiteCountriesWhois' => $this->path.'/GeoIPCountryWhois.csv'
    ));
    Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');

    Yii::trace('Заполняем страны...', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');
    $sql = 'INSERT IGNORE `geo_countries` (`name`, `code` )
            SELECT DISTINCT tmp_mm_country_whois.country_name, tmp_mm_country_whois.country_code
            FROM  `tmp_mm_country_whois`';
    $connection->createCommand($sql)->execute();
    Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');

    Yii::trace('Заполняем сети...', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');
    $sql = 'INSERT IGNORE `geo_network` (`beginip`, `endip`, `length`, `cityid`, `regionid`, `country`)
            SELECT startIpNum, endIpNum, endIpNum - startIpNum length, 0, 0, country_code
            FROM `tmp_mm_country_whois` WHERE country_code NOT IN ("RU", "UA")';
    $connection->createCommand($sql)->execute();
    Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');

    Yii::trace('Удаляем таблицы...', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');
    $sql = 'DROP TABLE IF EXISTS `tmp_mm_country_whois`;';
    $connection->createCommand($sql)->execute();
    Yii::trace('Готово', 'application.commands.UpdateGeoIPBaseCommand.MaxmindCountry');

    return 0;
}
*/
    /**
     * Инициализируем временные таблицы для импорта БД GeoIP
     * @param string $tplSql Файл-шаблон с SQL инструкциями для создания и заполнения временных таблиц
     * @param array $placeholders Массив с подстановками параметров в файле-шаблоне
     */
    private function _initTmpTables($tplSql, $placeholders){
        $fillSql = file_get_contents($tplSql);
        $fillSql = str_replace(array_keys($placeholders), array_values($placeholders), $fillSql);

        $db = Yii::$app->db;

        $pdo = new \yii\db\mssql\PDO(
            $db->dsn,
            $db->username,
            $db->password,
            [
                PDO::MYSQL_ATTR_LOCAL_INFILE => true
            ]
        );

        $queries = explode(";", $fillSql);
        foreach($queries as $query){
            $query = trim($query);
            if(!$query) continue;

            //Yii::$app->db->createCommand($query)->execute();
            $pdo->query($query);
        }

        return 0;
    }
}