<?php

namespace common\models;

use common\helpers\SlugHelper;
use Exception;
use frontend\modules\transport\models\TransportSearch;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use morphos\Russian\RussianLanguage;
use yii\base\InvalidArgumentException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "service".
 *
 * @property int $id
 * @property string $name
 * @property string $short_desc Краткое описание
 * @property int $open_price Плавающая цена
 * @property double $count
 * @property double $price
 *
 * @property PaymentDetails[] $paymentDetails
 * @property ServiceRate[] $serviceRates
 */
class Service extends ActiveRecord
{
    protected $_price = null;
    protected $_count;

    const SEARCH = 1;
    const SMS_NOTIFY = 2;
    const COLORED = 3;
    const MAIN_PAGE = 4;
    const RECOMMENDATIONS = 5;
    const BOOKING_START = 6;
    const BOOKING_BUSINESS = 7;
    const BOOKING_PROFI = 8;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return 'service';
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 255],
            [['short_desc'], 'string', 'max' => 256],

            [['open_price'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'name' => 'Название',
            'short_desc' => 'Краткое описание',
            'open_price' => 'Плавающая цена'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getPaymentDetails(){
        return $this->hasMany(PaymentDetails::class, ['service_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getServiceRates(){
        return $this->hasMany(ServiceRate::class, ['service_id' => 'id']);
    }

    public function setCount($count){
        $this->_count = $count;
        $this->_price = null;
    }

    public function getCount(){
        return $this->_count;
    }

    /**
     *
     * @return ServiceRate|null
     */
    public function getPrice(){
        if( !isset($this->count))
            throw new InvalidArgumentException('Необходимо указать count');

        if( !$this->_price){
            $this->_price = self::getPriceByCount($this->id, $this->count);
        }

        return $this->_price;
    }

    /**
     * @param $service_id
     * @param $price
     * @return bool|float
     */
    static public function getCountByPrice($service_id, $price){
        $service = self::findOne($service_id);

        if( !$service){
            return false;
        }

        if($service->open_price){
            /** @var ServiceRate $rate */
            $rate = $service->getServiceRates()
                ->andWhere(['>=', 'price', $price])
                ->orderBy(['price' => SORT_ASC])
                ->one();

            if( !$rate){
                $rate = $service->getServiceRates()
                    ->andWhere(['<', 'price', 999999999])
                    ->orderBy(['price' => SORT_DESC])
                    ->one();
            }

            if($rate){
                return floor($price/($rate->price/$rate->amount));
            }
        } else{
            /** @var ServiceRate $rate */
            $rate = $service->getServiceRates()
                ->andWhere(['price' => $price])
                ->one();

            if($rate){
                return $rate->amount;
            }
        }
    }

    /**
     * @param $service_id
     * @param $count
     * @return array|bool|float|mixed
     */
    static public function getPriceByCount($service_id, $count){
        $service = self::findOne($service_id);

        if( !$service){
            return false;
        }

        if($service->open_price){
            //если открытая цена, находим ближайший по кол-ву
            /** @var ServiceRate $rate */
            $rate = $service->getServiceRates()
                ->andWhere(['>=', 'amount', $count])
                ->orderBy(['amount' => SORT_ASC])
                ->one();

            if( !$rate){
                $rate = $service->getServiceRates()
                    ->andWhere(['<', 'amount', 999999999])
                    ->orderBy(['amount' => SORT_DESC])
                    ->one();
            }

            if($rate){
                $service->_price = round(($rate->price/$rate->amount)*$count, 2);
            }
        } else{
            /** @var ServiceRate $rate */
            $rate = $service->getServiceRates()
                ->andWhere(['amount' => $count])
                ->one();

            if($rate){
                $service->_price = $rate->price;
            }
        }

        return $service->_price;
    }

    /**
     * @param Service $service
     * @param array $opt
     * @return string
     * @throws Exception
     */
    static public function extenedDescription($service, $opt = []){
        $linksLimit = 5;
        $text = '';

        $opt['transport_id'] = $opt['transport_id'] ?? null;
        $opt['descrOnly'] = $opt['descrOnly'] ?? false;

        //закрпить в поиске
        if( $service->id == self::SEARCH ){
            $transport = Transport::findOne($opt['transport_id']);

            $links = [];

            if( $transport ){
                //Предложный падеж города
                $city_name_pred = GeographicalNamesInflection::getCase($transport->cityFrom->title_ru, Cases::PREPOSITIONAL);
                $in_city = RussianLanguage::in($city_name_pred);

                //формируем ссылки, по которым может быть найден транспорт

                //По городу
                if( $transport->city_from == $transport->city_to ){
                    $linkLabel = 'Поиск перевозчика по '.GeographicalNamesInflection::getCase($transport->cityFrom->title_ru, Cases::DATIVE);

                    $link = Html::a($linkLabel, [
                        '/transport/search/',
                        'TransportSearch[locationFrom]' => $transport->cityFrom->getCode(),
                        'TransportSearch[locationTo]' => $transport->cityTo->getCode()
                    ]);

                    $links[] = $link;
                }
                else{
                    //Разные города
                    //Пример:
                    //Поиск перевозчика в Красноярске
                    $linkLabel = 'Поиск перевозчика '.$in_city;

                    $link = Html::a($linkLabel, [
                        '/transport/search/',
                        'TransportSearch[locationFrom]' => $transport->cityFrom->getCode()
                    ]);

                    $links[] = $link;

                    $linkLabel = 'Поиск перевозчика '.$transport->cityFrom->title_ru . ' - ' . $transport->cityTo->title_ru;

                    $link = Html::a($linkLabel, [
                        '/transport/search/',
                        'TransportSearch[locationFrom]' => $transport->cityFrom->getCode(),
                        'TransportSearch[locationTo]' => $transport->cityTo->getCode()
                    ]);

                    $links[] = $link;
                }


                //По категориям.
                //Пример:
                //Поиск перевозчика по категории «перевозка мебели» в Красноярске
                foreach($transport->cargoCategories as $category){

                    $linkLabel = 'Поиск перевозчика по категории «'.$category->category.'» '.$in_city;

                    $link = Html::a($linkLabel, [
                        '/transport/search/',
                        'TransportSearch[locationFrom]' => $transport->cityFrom->getCode(),
                        'TransportSearch[cargoCategoryIds][]' => $category->id
                    ]);

                    $links[] = $link;

                    if(count($links) == $linksLimit)
                        break;
                }
            }

            $text = "Заказчики часто пользуются поиском, что бы найти подходящего перевозчика. Помогите им найти вас - закрепите свое объявление в поиске.<br>";

            if( !empty($links) ){
                $text .= "Ваше объявление будет перемещено на первую позицию в поиске на следующих страницах:<br>"
                .self::addLinksToText($links);
            }

            if(!$opt['descrOnly'])
                $text .= "<br>По истечению 7 дней, ваше объявление вернется на свои прежние позиции в поиске.";
        }

        if( $service->id == self::COLORED ){
            $text = "На фоне других, ваше объявление может затеряться. Сделайте его <b>заметнее</b> – выделите цветом. <br><br>По истечению 7 дней, ваше объявление станет обычным.";
        }

        if( $service->id == self::MAIN_PAGE ){
            $transport = Transport::findOne($opt['transport_id']);

            $links = [];

            if( $transport ){
                $subdomain = strtolower(SlugHelper::rus2translit($transport->cityFrom->title_ru));
                $basePath = '//'.$subdomain.'.'.Yii::getAlias('@domain');

                //Грузоперевозки по Красноярску
                $linkLabel = "Грузоперевозки по ".GeographicalNamesInflection::getCase($transport->cityFrom->title_ru, Cases::DATIVE);


                $link = Html::a($linkLabel, $basePath);

                $links[] = $link;

                if( $transport->city_from != $transport->city_to ){
                    //Разные города
                    //Грузоперевозки Красноярск – Москва
                    $linkLabel = "Грузоперевозки ".$transport->cityFrom->title_ru . ' - ' . $transport->cityTo->title_ru;

                    $url_path = Url::to([
                        "/intercity/default/transportation2",
                        'cityTo' => strtolower(SlugHelper::rus2translit($transport->cityTo->title_ru))
                    ]);

                    $link = Html::a($linkLabel, $basePath.$url_path);

                    $links[] = $link;
                }

                //Предложный падеж города
                $city_name_pred = GeographicalNamesInflection::getCase($transport->cityFrom->title_ru, Cases::PREPOSITIONAL);
                $in_city = RussianLanguage::in($city_name_pred);

                //по категориям
                foreach($transport->cargoCategories as $cat){
                    $linkLabel = $cat->category." ".$in_city;

                    $url_path = Url::to([
                        "/cargo/transportation/search2",
                        'slug' => $cat->slug
                    ]);

                    $link = Html::a($linkLabel, $basePath.$url_path);

                    $links[] = $link;

                    if(count($links) == $linksLimit)
                        break;
                }
            }

            $text = "Сделайте ваше объявление <b>престижнее</b> – закрепите на главной.<br>";
            if( !empty($links) ){
                $text .= " Ваше объявление будет перемещено на первую позицию в разделе «Услуги перевозчиков» на главной и следующих страницах:<br>"
                .self::addLinksToText($links);
            }
        }

        if( $service->id == self::RECOMMENDATIONS ){
            $text = "<b>Ваше объявление точно заметят!</b> Сразу после добавления груза, заказчику отображается подходящий список перевозчиков. Данная услуга позволяет разместиться в этом списке среди первых объявлений.";
        }

        if( $text == '' ){
            $text = $service->short_desc;
        }

        return $text;
    }

    /**
     * @param $links
     * @return string
     */
    private static function addLinksToText($links){
        $result = Html::beginTag('ul', ['class'=>'list-unstyled']);
        $links = array_map(function ($item){
            return Html::tag('li', $item);
        }, $links);

        $result .= implode("", $links);

        $result .= Html::endTag('ul');

        return $result;
    }
}
