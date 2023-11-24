<?php

namespace frontend\modules\subscribe\models;

use common\behaviors\JunctionBehavior;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\City;
use common\models\Region;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\validators\ExistValidator;

/**
 * This is the model class for table "subscribe_rules".
 *
 * @property int $id ИД правила
 * @property int $subscribe_id ИД подписки
 * @property int $city_from Город отправки
 * @property int $city_to Город назначения
 * @property int $region_from Регион отправки
 * @property int $region_to Регион доставки
 * @property string $status Статус правила
 * @property int $msgCount Кол-во сообщений
 * @property int $created_at Дата создания
 * @property int $updated_at Дата обновления
 *
 * @property SubscribeLog[] $subscribeLogs
 * @property CargoCategory[] $categories
 * @property Subscribe $subscribe
 * @property City $cityFrom
 * @property City $cityTo
 * @property Region regionFrom
 * @property Region regionTo
 * @property array $categoriesId
 */
class SubscribeRules extends ActiveRecord
{
    const STATUS_ACTIVE = 'active';
    const STATUS_DELETED = 'deleted';

    ///////
    /// Тип перевозки
    const TRANSPORTATION_TYPE_ALL_CITY = 0; // По всем городам
    const TRANSPORTATION_TYPE_INSIDE_REGION = 10; // По одному региону
    const TRANSPORTATION_TYPE_BETWEEN_REGION = 20; // Между регионами
    const TRANSPORTATION_TYPE_DIFF_CITY = 30; // По разным городам
    const TRANSPORTATION_TYPE_INSIDE_CITY = 40; // По одному городу
    ///////////////

    const LOCATION_TYPE_CITY = 'city';
    const LOCATION_TYPE_REGION = 'region';

    protected $_categoriesId = null;

    public $locationFromType;
    public $locationToType;

    public $locationFrom;
    public $locationTo;

    /**
     * {@inheritdoc}
     */
    public static function tableName(){
        return 'subscribe_rules';
    }

    public function behaviors(){
        return [
            [
                //Сохранение данных в промежуточные таблицы
                'class' => JunctionBehavior::class,
                'association' => [
                    ['categoriesId', CargoCategory::class, 'categories']
                ]
            ],
            TimestampBehavior::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(){
        return [
            [['subscribe_id', 'msgCount', 'created_at', 'updated_at'], 'integer'],
            [['subscribe_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscribe::class, 'targetAttribute' => ['subscribe_id' => 'id']],
            [['categoriesId', 'locationFrom', 'locationTo'], 'required'],
            ['categoriesId', 'exist', 'targetClass' => CargoCategory::class, 'targetAttribute' => 'id', 'allowArray' => true],
            [['locationFrom'], 'locationValidator', 'skipOnEmpty' => false, 'params' => ['type' => 'locationFromType']],
            [['locationTo'], 'locationValidator', 'skipOnEmpty' => false, 'params' => ['type' => 'locationToType']],
            [['status'], 'default', 'value'=>self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            [['locationFromType', 'locationToType'], 'in', 'range' => [self::LOCATION_TYPE_CITY, self::LOCATION_TYPE_REGION]]
        ];
    }

    public function beforeValidate(){
        if( !parent::beforeValidate())
            return false;

        switch($this->locationFromType){
            case self::LOCATION_TYPE_CITY:
                $this->city_from = $this->locationFrom;
                $this->region_from = null;
                break;
            case self::LOCATION_TYPE_REGION:
                $this->region_from = $this->locationFrom;
                $this->city_from = null;
                break;
            default:
                $this->city_from = null;
                $this->region_from = null;
        }

        switch($this->locationToType){
            case self::LOCATION_TYPE_CITY:
                $this->city_to = $this->locationTo;
                $this->region_to = null;
                break;
            case self::LOCATION_TYPE_REGION:
                $this->region_to = $this->locationTo;
                $this->city_to = null;
                break;
            default:
                $this->city_to = null;
                $this->region_to = null;
        }

        return true;
    }

    public function locationValidator($attribute, $params){
        if($this->$attribute == 'all'){
            return true;
        }

        $exist = new ExistValidator();

        if($this->{$params['type']} == self::LOCATION_TYPE_CITY){
            $exist->targetClass = City::class;
        } else{
            $exist->targetClass = Region::class;
        }

        $exist->targetAttribute = 'id';
        $exist->skipOnEmpty = false;

        if( !$exist->validate($this->$attribute)){
            $this->addError($attribute, $this->getAttributeLabel($attribute).' указан неверно');
        }
    }

    public function afterFind(){
        parent::afterFind();

        if($this->city_from){
            $this->locationFrom = $this->city_from;
            $this->locationFromType = self::LOCATION_TYPE_CITY;
        } elseif($this->region_from){
            $this->locationFrom = $this->region_from;
            $this->locationFromType = self::LOCATION_TYPE_REGION;
        } else{
            $this->locationFrom = 'all';
        }

        if($this->city_to){
            $this->locationTo = $this->city_to;
            $this->locationToType = self::LOCATION_TYPE_CITY;
        } elseif($this->region_to){
            $this->locationTo = $this->region_to;
            $this->locationToType = self::LOCATION_TYPE_REGION;
        } else{
            $this->locationTo = 'all';
        }

        if(empty($this->categoriesId)){
            $cats = CargoCategory::find()
                ->showModerCargo()
                ->all();

            $this->categoriesId = array_map(function($cat){
                /** @var $cat CargoCategory */
                return $cat->id;
            }, $cats);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(){
        return [
            'id' => 'ИД правила',
            'subscribe_id' => 'ИД подписки',
            'city_from' => 'Город отправки',
            'city_to' => 'Город назначения',
            'locationFrom' => 'Место отправки',
            'locationTo' => 'Место доставки',
            'region_from' => 'Регион отправки',
            'region_to' => 'Регион доставки',
            'status' => 'Статус правила',
            'categoriesId' => 'Виды перевозки',
            'msgCount' => 'Кол-во сообщений',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления'
        ];
    }

    public function afterValidate(){
        parent::afterValidate();

        $this->saveMessageCount();
    }

    public function beforeSave($insert){
        if( !parent::beforeSave($insert))
            return false;

        if($this->city_from == 'all')
            $this->city_from = '';

        if($this->city_to == 'all')
            $this->city_to = '';

        $total_categories = CargoCategory::find()
            ->showModerCargo()
            ->count();

        //если указаны все катеории, то не сохраняем ничего
        if(count($this->categoriesId) >= $total_categories){
            $this->categoriesId = [];
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes){
        parent::afterSave($insert, $changedAttributes);
        //после сохранения модель отправляется клиенту,
        //но в beforeSave категории были удалены, поэтому
        //запускаем afterFind чтобы заново заполнить их
        $this->afterFind();
    }

    /**
     * @return ActiveQuery
     */
    public function getCategories(){
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('subscribe_rule_category_assn', ['subscribe_rule_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSubscribe(){
        return $this->hasOne(Subscribe::class, ['id' => 'subscribe_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityFrom(){
        return $this->hasOne(City::class, ['id' => 'city_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityTo(){
        return $this->hasOne(City::class, ['id' => 'city_to']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegionFrom(){
        return $this->hasOne(Region::class, ['id' => 'region_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegionTo(){
        return $this->hasOne(Region::class, ['id' => 'region_to']);
    }

    /**
     * @param $dir
     * @return bool|City|Region
     */
    private function location($dir){
        if($dir == 'From'){
            $locTypeParam = 'locationFromType';
        } else{
            $locTypeParam = 'locationToType';
        }

        if($this->$locTypeParam == self::LOCATION_TYPE_CITY){
            return $this->{"city$dir"};
        }
        if($this->$locTypeParam == self::LOCATION_TYPE_REGION){
            return $this->{"region$dir"};
        }

        return false;
    }

    /**
     * @param $direction 'From' | 'To'
     * @return array
     */
    public function getCityString($direction, $select = false){
        $result = [];

        //если у модели есть ИД (из базы или временный), то по умолчанию
        //выставляем "Все города"
        if($this->id){
            $result = ['all' => 'Все города'];
        }

        $location = $this->location($direction);

        if($location instanceof City){
            $text = $location->title_ru;
            if( !empty($location->region_ru)){
                $text .= ', '.$location->region_ru;
            }
            $text .= ', '.$location->country->title_ru;

            $result = [$location->id => $text];
        }

        if($location instanceof Region){
            $text = $location->title_ru;

            $text .= ', '.$location->country->title_ru;

            $result = [$location->id => $text];
        }

        return $result;
    }

    public function flag($direction){
        $location = $this->location($direction);

        if( !$location)
            return '/img/flags/all.png';

        return $location->country->flagIcon;
    }

    public function countryTitle($direction){
        $location = $this->location($direction);

        if( !$location)
            return '';

        return $location->country->title_ru;
    }

    public function getCategoriesId(){
        if($this->_categoriesId === null){
            $this->_categoriesId = array_map(function($model){
                return $model->id;
            }, $this->categories);
        }
        return $this->_categoriesId;
    }

    public function setCategoriesId($val){
        $this->_categoriesId = $val;
    }

    public function selectedCity($dir){
        $location = $this->location($dir);

        if( !$location)
            return 'Все города';

        return $location->title_ru;
    }

    /**
     * Расчет кол-ва сообщений в день
     * @param null $locationFrom
     * @param null $locationTo
     * @param string $locationFromType
     * @param string $locationToType
     * @param null $category
     * @return float
     */
    static public function calcMessageCount(
        $locationFrom = null,
        $locationTo = null,
        $locationFromType = self::LOCATION_TYPE_CITY,
        $locationToType = self::LOCATION_TYPE_CITY,
        $category = null){
        //Период (дней) расчета цены
        $calc_days = 30;

        $time = strtotime('today midnight');
        $time = strtotime("-$calc_days days", $time);

        $locationFrom = $locationFrom != 'all' ? $locationFrom : null;
        $locationTo = $locationTo != 'all' ? $locationTo : null;

        //количество добавленных грузов по данному направлению за 30 дней
        $query = Cargo::find()
            ->select(Cargo::tableName().'.id')
            ->where(['>', 'created_at', $time]);

        if($locationFrom){
            if( $locationFromType == self::LOCATION_TYPE_CITY ){
                $query->andWhere(['city_from' => $locationFrom]);
            }
            if( $locationFromType == self::LOCATION_TYPE_REGION ){
                $query->andWhere(['region_from' => $locationFrom]);
            }
        }

        if($locationTo){
            if( $locationToType == self::LOCATION_TYPE_CITY ){
                $query->andWhere(['city_to' => $locationTo]);
            }
            if( $locationToType == self::LOCATION_TYPE_REGION ){
                $query->andWhere(['region_to' => $locationTo]);
            }
        }

        if($category){
            $cats = CargoCategory::findAll($category);
            $catIds = $category;
            foreach($cats as $cat){
                if($cat->root)
                    $catIds = array_merge($catIds, $cat->nodesids);
            }

            $query->joinWith('categories')
                ->andFilterWhere([CargoCategory::tableName().'.id' => $catIds]);

            // Хитрый способ избавиться от дублей грузов при джоине категорий
            $query->distinct();
        }

        $cargoCount = $query->count();
        //хотя бы одно
        $cargoCount = $cargoCount ? $cargoCount : 1;
        return ceil($cargoCount/$calc_days);
    }

    protected function saveMessageCount(){
        ///////////////////////////////
        // Определяем направление правила
        $locationFromType = $locationToType = self::LOCATION_TYPE_CITY;
        $locationFrom = $locationTo = null;

        if($this->city_from){
            $locationFrom = $this->city_from;
            $locationFromType = self::LOCATION_TYPE_CITY;
        }elseif ($this->region_from){
            $locationFrom = $this->region_from;
            $locationFromType = self::LOCATION_TYPE_REGION;
        }

        if($this->city_to){
            $locationTo = $this->city_to;
            $locationToType = self::LOCATION_TYPE_CITY;
        }elseif ($this->region_to){
            $locationTo = $this->region_to;
            $locationToType = self::LOCATION_TYPE_REGION;
        }
        ///////////////////////////////

        $this->msgCount = self::calcMessageCount(
            $locationFrom,
            $locationTo,
            $locationFromType,
            $locationToType,
            $this->categoriesId
        );
    }

    static public function statusLabels($colored = false){
        return [
            self::STATUS_ACTIVE => $colored ? '<font style="color: #3ab845">Активный</font>' : 'Активный',
            self::STATUS_DELETED => $colored ? '<font style="color: #b81221">Удален</font>' : 'Удален'
        ];
    }

    static public function getStatusLabel($status, $colored = false){
        $list = self::statusLabels($colored);

        return isset($list[$status]) ? $list[$status] : null;
    }

    /**
     * @return ActiveQuery
     */
    public function getSubscribeLogs(){
        return $this->hasMany(SubscribeLog::class, ['rule_id' => 'id']);
    }
}
