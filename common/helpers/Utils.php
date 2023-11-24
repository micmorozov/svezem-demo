<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.05.17
 * Time: 9:25
 */

namespace common\helpers;

use common\models\Cargo;
use common\models\CargoCategory;
use common\models\Profile;
use common\models\Transport;
use frontend\modules\tk\models\Tk;
use Generator;
use Redis;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use zz\Html\HTMLMinify;

class Utils
{

    const SHORTEN_URL_CODE = 'shortenUrlCode';

    /**
     * Установить для транспорта вид перевозки
     * @param $id
     * @return bool
     */
    static public function setTransportTransportationType($id){
        $transport = Transport::findOne($id);
        if( !$transport)
            return false;

        $category = CargoCategory::find()->where(['transportation_type' => 1])->one();

        if( !$category)
            return false;

        $transport->cargoCategoryIds = array_merge($transport->cargoCategoryIds, [$category->id]);
        $transport->save(false);
    }

    /**
     * Установить для груза вид перевозки
     * @param $id
     * @return bool
     */
    static public function setCargoTransportationType($id){
        $cargo = Cargo::findOne($id);
        if( !$cargo)
            return false;

        $category = CargoCategory::find()->where(['transportation_type' => 1])->one();

        if( !$category)
            return false;

        $ids = array_map(function($item){
            return $item->id;
        }, $cargo->categories);

        $ids[] = $category->id;

        $cargo->categories = [
            'list' => $ids,
            'main' => $cargo->cargo_category_id
        ];
        $cargo->save();
    }

    /**
     * Установить для ТК вид перевозки
     * @param $id
     * @return bool
     */
    static public function setTkTransportationType($id){
        $tk = Tk::findOne($id);
        if( !$tk)
            return false;

        $category = CargoCategory::find()->where(['transportation_type' => 1])->one();

        if( !$category)
            return false;

        $tk->categoriesIds = array_merge($tk->categoriesIds, [$category->id]);
        $tk->save();
    }

    /**
     * Установить/снять категорию транспорта "Частное лицо" исходя из профиля создателя
     * @param $profile_id
     * @return bool
     */
    static public function setTransportPrivateTransportation($profile_id){
        $profile = Profile::findOne($profile_id);
        if( !$profile)
            return false;

        $category = CargoCategory::find()->where(['private_transportation' => 1])->one();
        if( !$category)
            return false;

        foreach($profile->transport as $transport){
            //если профиль "частное лицо"
            if($profile->type == Profile::TYPE_TRANSPORTER_PRIVATE){
                $transport->cargoCategoryIds = array_merge($transport->cargoCategoryIds, [$category->id]);
            } else{
                $transport->cargoCategoryIds = array_diff($transport->cargoCategoryIds, [$category->id]);
            }

            $transport->save(false);
        }
    }

    /**
     * Установить категорию груза "Частное лицо"
     * @param $id
     * @return bool
     */
    static public function setCargoPrivateTransportation($id){
        $cargo = Cargo::findOne($id);
        if( !$cargo)
            return false;

        $category = CargoCategory::find()->where(['private_transportation' => 1])->one();
        if( !$category)
            return false;

        $ids = array_map(function($item){
            return $item->id;
        }, $cargo->categories);

        $ids[] = $category->id;

        $cargo->categories = [
            'list' => $ids,
            'main' => $cargo->cargo_category_id
        ];
        $cargo->save();
    }

    /**
     * Добавляет параметр $param со значением $value к урлу $url
     * @param string$url УРЛ с добавленным параметром
     * @param array $params Массив вида  параметр-значение
     * @return string Итоговый урл
     */
    static public function addParamToUrl($url, $params){
        $url = parse_url($url);
        if( isset($url['query']) ){
            parse_str($url['query'], $output);
            $params = array_merge($output, $params);
        }

        $result = '';

        if( isset($url['scheme']) ){
            $result .= $url['scheme']."://";
        }
        if( isset($url['host']) ){
            $result .= $url['host'];
        }

        return $result.$url['path']."?".http_build_query($params);
    }

    /*
     * Расстояние между двумя точками
     * $fA, $gA - широта, долгота 1-й точки,
     * $gB, $gB - широта, долгота 2-й точки
     * Написано по мотивам http://gis-lab.info/qa/great-circles.html
     * Михаил Кобзарев <mikhail@kobzarev.com>
     *
     */
    static function calculateTheDistance($fA, $gA, $fB, $gB){
        // Радиус земли
        $EARTH_RADIUS = 6372795;

        // перевести координаты в радианы
        $lat1 = $fA*M_PI/180;
        $lat2 = $fB*M_PI/180;
        $long1 = $gA*M_PI/180;
        $long2 = $gB*M_PI/180;

        // косинусы и синусы широт и разницы долгот
        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);
        $delta = $long2 - $long1;
        $cdelta = cos($delta);
        $sdelta = sin($delta);

        // вычисления длины большого круга
        $y = sqrt(pow($cl2*$sdelta, 2) + pow($cl1*$sl2 - $sl1*$cl2*$cdelta, 2));
        $x = $sl1*$sl2 + $cl1*$cl2*$cdelta;

        //
        $ad = atan2($y, $x);
        $dist = $ad*$EARTH_RADIUS;

        return $dist;
    }

    /**
     * Создание кода короткого УРЛ
     * @param string $url
     * @return bool|string
     * @throws Exception
     */
    static public function createShortenCode($url){
        $code = StringHelper::str_rand(6);

        $key = self::SHORTEN_URL_CODE.":".$code;

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        $try = 5;
        $success = false;
        while($try--){
            if($redis->setnx($key, $url)){
                $success = true;
                $redis->expire($key, 86400*3);
                break;
            }
        }

        return $success ? $code : false;
    }

    /**
     * Получение УРЛ по коду
     * @param $code
     * @return bool|string
     */
    static public function getUrlByShortenCode($code){
        $key = self::SHORTEN_URL_CODE.":".$code;

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        return $redis->get($key);
    }

    static public function createShortenUrl($url){
        $code = Utils::createShortenCode($url);
        if( !$code )
            return false;

        return 'http://'.Yii::getAlias('@domain')."/r/$code";
    }

    /**
     * @param $val
     * @param $min
     * @param $max
     * @return bool
     */
    static public function between($val, $min, $max): bool {
        return $val >= $min && $val <= $max;
    }

    /**
     * @param $page
     * @return string
     */
    static public function pagePositionTextStyle($page){
        if( $page == 1 ){
            return '(1ая страница)';
        }
        elseif( $page < 100 ){
            return "(<span style='color:red'>{$page}ая страница</span>)";
        }
        else{
            return '(<span style=\'color:red\'>далее 20 страницы</span>)';
        }
    }

    public static function timer($name){
        static $timerName = [];

        if( !isset($timerName[$name]) ){
            $timerName[$name] = floatval(microtime());
            return "Start: $name\n";
        }
        else{
            $time = floatval(microtime()) - $timerName[$name];
            unset($timerName[$name]);
            return $name.": ".$time."\n";
        }
    }

    /**
     * @param $file string Путь к файлу
     * @param $savePath string Путь сохранения
     * @param int $level Уровень сжатия. Целое число от 0 до 9 (0 - без сжатия, 9 - максимальное сжатие).
     * @return bool|int
     */
    public static function gzipFile($file, $savePath, $level = 9){
        $data = implode("", file($file));
        $gzdata = gzencode($data, $level);
        $fp = fopen($savePath, "w");
        $result = fwrite($fp, $gzdata);
        fclose($fp);

        return $result;
    }

    /**
     * Преобразование числа в строку.
     * Вызов: digit2string(
     *        $number -- число для преобразования
     *        $sex      -- 1 если число к женскому роду, 0 если к мужскому
     *        (sex==1, то 301 будет "триста одна", sex==0, то 301 будет "триста один")
     *        $suffix -- массив из трех строк с суффиксом к строке для 1, 4,>4.
     * например: array('рубль', 'рубля', 'рублей')
     */
    public static function digit2string($number, $sex, $suffix)
    {
        $res = "";
        $maxpower = 6;
        $power = [['sex' => $sex, 'one' => $suffix [0], 'four' => $suffix [1], 'many' => $suffix [2]], // 1
            ['sex' => 1, 'one' => 'тысяча ', 'four' => 'тысячи ', 'many' => 'тысяч '], // 2
            ['sex' => 0, 'one' => 'миллион ', 'four' => 'миллиона ', 'many' => 'миллионов '], // 3
            ['sex' => 0, 'one' => 'миллиард ', 'four' => 'миллиарда ', 'many' => 'миллиардов '], // 4
            ['sex' => 0, 'one' => 'триллион ', 'four' => 'триллиона ', 'many' => 'триллионов ']];

        $unit = [
            ['one' => ['', ''], 'two' => 'десять ', 'dec' => '', 'huh' => ''],
            ['one' => ['один ', 'одна '], 'two' => 'одиннадцать ', 'dec' => 'десять ', 'huh' => 'сто '],
            ['one' => ['два ', 'две '], 'two' => 'двенадцать ', 'dec' => 'двадцать ', 'huh' => 'двести '],
            ['one' => ['три ', 'три '], 'two' => 'тринадцать ', 'dec' => 'тридцать ', 'huh' => 'триста '],
            ['one' => ['четыре ', 'четыре '], 'two' => 'четырнадцать ', 'dec' => 'сорок ', 'huh' => 'четыреста '],
            ['one' => ['пять ', 'пять '], 'two' => 'пятнадцать ', 'dec' => 'пятьдесят ', 'huh' => 'пятьсот '],
            ['one' => ['шесть ', 'шесть '], 'two' => 'шестнадцать ', 'dec' => 'шестьдесят ', 'huh' => 'шестьсот '],
            ['one' => ['семь ', 'семь '], 'two' => 'семнадцать ', 'dec' => 'семьдесят ', 'huh' => 'семьсот '],
            ['one' => ['восемь ', 'восемь '], 'two' => 'восемнадцать ', 'dec' => 'восемьдесят ', 'huh' => 'восемьсот '],
            ['one' => ['девять ', 'девять '], 'two' => 'девятнадцать ', 'dec' => 'девяносто ', 'huh' => 'девятьсот ']];

        if ($number == 0) {
            return 'ноль' . $power[0]['many'];
        }
        if ($number < 0) {
            $number = -$number;
            $res = 'минус';
        }

        $d = 1;
        for ($i = 0; $i < $maxpower; ++$i) {
            $d *= 1000;
        }
        for ($i = $maxpower - 1; $i >= 0; --$i) {
            $d /= 1000;
            $m = floor($number / $d);
            $number %= $d;
            $s = "";
            if ($m == 0) {
                if ($i > 0)
                    continue;
                $s .= $power [$i] ['many'];
            } else {
                if ($m >= 100) {
                    $s .= $unit [floor($m / 100)] ['huh'];
                    $m %= 100;
                }
                if ($m >= 20) {
                    $s .= $unit [floor($m / 10)] ['dec'];
                    $m %= 10;
                }
                if ($m >= 10) {
                    $s .= $unit [floor($m - 10)] ['two'];
                } else if ($m >= 1) {
                    $s .= $unit [$m] ['one'] [$power [$i] ['sex']];
                }

                if ($m == 1) {
                    $s .= $power [$i] ['one'];
                } else if ($m >= 2 && $m <= 4) {
                    $s .= $power [$i] ['four'];
                } else {
                    $s .= $power [$i] ['many'];
                }
            }
            $res .= $s;
        }

        return $res;
    }

    /**
     * Ф-ция возвращает массив уникальных значений определенных колбэком
     *
     * @param array $arr - массив объектов
     * @param callable $cb - колбэк ф-ция
     * @param bool $keep_key_assoc - сохранить ассоциативность
     * @return array
     */
    public static function arrayUniqueObjects($arr, $cb, $keep_key_assoc = false)
    {
        $ids = array_map(function ($model) use ($cb){
            return $cb($model);
        }, $arr);

        $unique_models = array_unique($ids);
        $result = array_intersect_key($arr, $unique_models);
        return $keep_key_assoc ? $result : array_values($result);
    }

    /**
     * @param Query $query
     * @param int $limit
     * @return Generator
     */
    public static function arModelsGenerator(Query $query, $limit = 500){
        $query->limit($limit);

        while($models = $query->all()){
            $query->offset += $query->limit;

            foreach ($models as $model){
                yield $model;
            }
        }
    }

    /**
     * Строим битовую маску для массива значений
     * @param array $values
     * @return int
     */
    public static function mask_encode(array $values):int
    {
        $mask = 0;
        foreach ($values as $value) {
            if(!is_numeric($value)) continue;

            $mask |= (1 << $value);
        }
        return $mask;
    }

    /**
     * Из маски восстанавливаем массив значений
     * @param int $mask
     * @return array
     */
    public static function mask_decode(int $mask = null): array
    {
        if(is_null($mask)) return [];

        $values = array_reverse(str_split(decbin($mask)));
        return array_keys(array_filter($values, function($val){
            return $val > 0;
        }));
    }

    /**
     * Входит ли значение в маску
     * @param int $val Проверяемое значение
     * @param int $mask Маска
     * @return bool
     */
    public static function check_mask(int $val, int $mask): bool
    {
        if ($val < 0) return false;

        return $mask & (1 << $val);
    }
}
