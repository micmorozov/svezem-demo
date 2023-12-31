<?php
namespace console\models\companies;

use GuzzleHttp\Client;
use Yii;
use console\models\BaseCompany;
use yii\base\Exception;
use \GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\ServerException;

class NrgTkRu extends BaseCompany {

    public function parse() {

        try {
            //$cities = $this->cities();

            $volume = floatval($this->width) * floatval($this->height) * floatval($this->depth);
            
            $city_from = $this->cities($this->from_city_name);//isset($cities[$this->from_city_name]) ? $cities[$this->from_city_name] : '';
            $city_to = $this->cities($this->to_city_name);//isset($cities[$this->to_city_name]) ? $cities[$this->to_city_name] : '';
            
            if( $city_from == '' ){
            	$this->last_error_msg = "Не известен город отправки '{$this->from_city_name}'";
            	 
            	return false;
            }
            
            if( $city_to == '' ){
            	$this->last_error_msg = "Не известен город получения '{$this->to_city_name}'";
            
            	return false;
            }
            
            $sendData = [
            	'idCityFrom' => $city_from,
            	'idCityTo' => $city_to,
            	'items' => [
            			[
            				'height' => $this->height,
            				'length' => $this->depth,
            				'weight' => $this->weight,
            				'width' => $this->width
            			]
            	]
            ];
           
            $data_string = json_encode($sendData);
            
            $client = new Client([
    			'headers'  => [
    				'content-type' => 'application/json'
    			]
    		]);
            $data = $client->post('http://api2.nrg-tk.ru/v2/price', [
            		'body' => $data_string
            ]);
            
            $result = json_decode($data->getBody()->__toString(), 1);
            
            if( is_array($result) && isset($result['transfer']) ){
            	$this->cost = $result['transfer'][0]['price'];
            }
            
            return $this->checkCost();
        }
        catch(ClientException $e){
        	$this->last_error_code = 1;
        	$this->last_error_msg = $e->getMessage();
        	 
        	return false;
        }
        catch(ServerException $e) {
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

    private function cities($cityName)
    {
        $cacheKey = get_class().":getCity";

        if ( !$result = Yii::$app->cache->get($cacheKey) ) {
            $client = new Client();
            $data = $client->get('https://api2.nrg-tk.ru/v2/cities');

            $data = json_decode($data->getBody()->__toString(), 1);

            $result = [];
            array_walk($data['cityList'], function($item) use (&$result) {
                $result[$item['name']] = $item['id'];
            });

            Yii::$app->cache->set($cacheKey, $result, 86400);
        }

        return isset($result[$cityName]) ? $result[$cityName] : false;


        /*return [
            "Абакан" => 3902,
            "Агинское" => 30239,
            "Актау" => 7292,
            "Актобе" => 7132,
            "Алдан" => 41145,
            "Алейск" => 38553,
            "Алматы" => 7272,
            "Алтайское" => 38537,
            "Альметьевск" => 8553,
            "Ангарск" => 3951,
            "Анжеро-Судженск" => 38453,
            "Арсеньев" => 42361,
            "Артем" => 42337,
            "Архангельск" => 8182,
            "Астана" => 7172,
            "Астрахань" => 8512,
            "Атырау" => 7122,
            "Ачинск" => 39151,
            "Балаково" => 84570,
            "Барнаул" => 3852,
            "Белово" => 38452,
            "Белогорск" => 41641,
            "Белокуриха" => 38577,
            "Березники" => 34242,
            "Бийск" => 3854,
            "Бикин" => 42155,
            "Биробиджан" => 42622,
            "Благовещенск" => 41622,
            "Большой Камень" => 42335,
            "Братск" => 3953,
            "Вельск" => 84593,
            "Владивосток" => 4232,
            "Владикавказ" => 8672,
            "Владимир" => 4922,
            "Волгоград" => 8442,
            "Волжский" => 8443,
            "Воронеж" => 4732,
            "Воткинск" => 34145,
            "Вяземский" => 42153,
            "Горно-Алтайск" => 38541,
            "Гродно" => 1522,
            "Дальнереченск" => 42356,
            "Джанкой" => 806564,
            "Евпатория" => 3806569,
            "Екатеринбург" => 343,
            "Ереван" => 37410,
            "Забайкальск" => 30251,
            "Заринск" => 38595,
            "Зеленогорск" => 39169,
            "Зима" => 39514,
            "Златоуст" => 35136,
            "Иваново" => 4932,
            "Ижевск" => 3412,
            "Иркутск" => 3952,
            "Искитим" => 38343,
            "Ишим" => 34551,
            "Йошкар-Ола" => 8362,
            "Казань" => 84321,
            "Калуга" => 4842,
            "Каменск-Уральский" => 3439,
            "Камень-на-Оби" => 38514,
            "Канск" => 39161,
            "Караганда" => 7212,
            "Карасук" => 38355,
            "Кемерово" => 3842,
            "Керчь" => 3806561,
            "Киров" => 8332,
            "Киселевск" => 38464,
            "Кисловодск" => 87937,
            "Кокчетав" => 7162,
            "Коломна" => 4966,
            "Комсомольск-на-Амуре" => 42172,
            "Костанай" => 7142,
            "Краснодар" => 8612,
            "Красноярск" => 3912,
            "Куйбышев" => 38362,
            "Курган" => 3522,
            "Л.Кузнецкий" => 38456,
            "Лесосибирск" => 39145,
            "Лучегорск" => 42357,
            "Москва" => 495,
            "Магнитогорск" => 35137,
            "Мариинск" => 38443,
            "Междуреченск" => 38475,
            "Миасс" => 3513,
            "Минск" => 17,
            "Мирный" => 41136,
            "Могилев" => 80222,
            "Новосибирск" => 383,
            "Набережные челны" => 8552,
            "Находка" => 42366,
            "Невинномысск" => 86554,
            "Нерюнгри" => 41147,
            "Нижневартовск" => 3466,
            "Нижнекамск" => 8555,
            "Нижний Новгород" => 8312,
            "Нижний Тагил" => 3435,
            "Новоалтайск" => 38532,
            "Новокузнецк" => 3843,
            "Новокуйбышевск" => 84635,
            "Новороссийск" => 8617,
            "Новочебоксарск" => 88352,
            "Ноглики" => 42444,
            "Норильск" => 3919,
            "Омск" => 3812,
            "Оренбург" => 35322,
            "Осинники" => 38471,
            "Камчатский" => 2,
            "Павлодар" => 7182,
            "Партизанск" => 423630,
            "Пенза" => 8412,
            "Пермь" => 3422,
            "Петропавловск" => 7152,
            "Поронайск" => 42431,
            "Прокопьевск" => 38466,
            "Пятигорск" => 87933,
            "Ростов-на-Дону" => 863,
            "Рубцовск" => 38557,
            "Рязань" => 4912,
            "Санкт-Петербург" => 812,
            "Салават" => 3476,
            "Самара" => 864,
            "Саратов" => 8452,
            "Саяногорск" => 39042,
            "Свободный" => 41643,
            "Севастополь" => 380692,
            "Семипалатинск" => 7222,
            "Симферополь" => 380652,
            "Сковородино" => 41654,
            "Славгород" => 38568,
            "Славянск-на-Кубани" => 86146,
            "Смоленск" => 4812,
            "Советская Гавань" => 42138,
            "Спасск-Дальний" => 42352,
            "Ставрополь" => 8652,
            "Стерлитамак" => 3473,
            "Сургут" => 346,
            "Сызрань" => 84643,
            "Таганрог" => 86344,
            "Тайга" => 38448,
            "Талды-Курган" => 7282,
            "Тараз" => 7262,
            "Таштагол" => 38473,
            "Тверь" => 4822,
            "Тобольск" => 3456,
            "Тольятти" => 8482,
            "Томск" => 3822,
            "Троицк" => 35168,
            "Тула" => 4872,
            "Тулун" => 39530,
            "Тында" => 41656,
            "Тюмень" => 3452,
            "Улан-Удэ" => 3022,
            "Ульяновск" => 8422,
            "Уральск" => 71122,
            "Усолье-Сибирское" => 39543,
            "Уссурийск" => 4234,
            "Усть-Илимск" => 39535,
            "Усть-Каменогорск" => 7232,
            "Усть-Кут" => 39565,
            "Уфа" => 3472,
            "Феодосия" => 3806562,
            "Хабаровск" => 4212,
            "Чайковский" => 34241,
            "Чебоксары" => 8352,
            "Челябинск" => 3512,
            "Чита" => 3012,
            "Шадринск" => 35253,
            "Шахты" => 8636,
            "Шуя" => 49351,
            "Шымкент" => 7252,
            "Экибастуз" => 71835,
            "Энгельс" => 8453,
            "Южно-Сахалинск" => 7962,
            "Юрга" => 38451,
            "Якутск" => 41122,
            "Ялта" => 380654,
            "Ярославль" => 4852
        ];*/
    }

}