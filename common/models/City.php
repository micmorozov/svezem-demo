<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "cities".
 *
 * @property integer $id
 * @property integer $country_id
 * @property integer $region_id
 * @property string $title_ru
 * @property string $area_ru
 * @property string $region_ru
 *
 * @property Country $country
 * @property Region $region
 * @property FastCity $fastcity
 *
 * @property double $latitude
 * @property double $longitude
 * @property string $code Наименование города латиницей
 * @property int $main_city
 * @property int $size
 */
class City extends ActiveRecord implements LocationInterface
{
    const REDIS_CITY_COORDINATE_KEY = 'cityCoordinate';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cities';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['country_id'], 'required'],
            [['id', 'country_id', 'region_id'], 'integer'],
            [['title_ru', 'area_ru', 'region_ru'], 'string', 'max' => 150],
            [['code'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'country_id' => 'Country ID',
            'region_id' => 'Region ID',
            'title_ru' => 'Title Ru',
            'area_ru' => 'Area Ru',
            'region_ru' => 'Region Ru',
            'code' => 'Наименование города латиницей',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::class, ['id' => 'region_id']);
    }

    public function getFQTitle()
    {
        $title_ru = $this->title_ru;
        if (!empty($this->region_id)) {
            $title_ru .= ', ' . $this->region_ru;
        }
        $title_ru .= ', ' . $this->country->title_ru;
        return $title_ru;
    }

    public function getTitle(): string
    {
        return $this->title_ru;
    }

    /**
     * Возвращает наименование города с регионом. Используется только для шаблона Twig
     * @return string
     */
    public function getTitleWithRegionForTwig(): string
    {
        return $this->title_ru . (!$this->main_city && $this->region_id ? '_' . $this->region->title_ru : '');
    }

    /**
     * @param $title_ru
     * @param $limit - Количество записей для выдачи
     * @return static[]
     */
    public static function findList($title_ru, $limit = 20)
    {
        return static::find()
            ->where(['like', 'title_ru', $title_ru])
            ->with(['country'])
            ->limit($limit)
            ->asArray()
            ->all();
    }

    /**
     * @return ActiveQuery
     */
    public function getFastcity()
    {
        return $this->hasOne(FastCity::class, ['cityid' => 'id']);
    }

    /**
     * @param $longitude
     * @param $latitude
     * @param int $radius
     */
    public static function getCityByCoordinate($longitude, $latitude, $radius = 20)
    {
        //TODO Отложено до корректировки координат в базе
//        /** @var \Redis $redis */
//        $redis = Yii::$app->redisGeo;
//
//        $ids = $redis->georadius(
//            self::REDIS_CITY_COORDINATE_KEY,
//            $longitude,
//            $latitude,
//            $radius,
//            'km',
//            ['WITHDIST', 'ASC']
//        );
//
//        print_r($ids);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getFullTitle(): string
    {
        $title = $this->getTitle();
        if ($this->region_id) {
            $title .= ', ' . $this->region->getTitle();
        }
        $title .= ', ' . $this->country->getTitle();

        return $title;
    }

    public function getParentLocation(): array
    {
        $result = [];

       // array_push($result, $this->country);

        if($region = $this->region)
            array_push($result, $region);

        array_push($result, $this);

        return $result;
    }

    public function equal(LocationInterface $eq = null): bool
    {
        if(is_null($eq)) return false;

        return $this->getId() === $eq->getId();
    }
}
