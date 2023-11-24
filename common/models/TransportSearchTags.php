<?php

namespace common\models;

use common\behaviors\SlugBehavior;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * This is the model class for table "transport_search_tags".
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $url
 * @property integer $city_from
 * @property integer $city_to
 * @property integer $category_id
 * @property integer $domain_id
 *
 * @property City $cityFrom
 * @property City $cityTo
 * @property City $domain
 * @property CargoCategory $category
 */
class TransportSearchTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transport_search_tags';
    }

    public function behaviors()
    {
        return [
            [
                'class' => SlugBehavior::class,
                'attribute' => 'name'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slug', 'name'], 'required'],
            [['city_from', 'city_to', 'category_id', 'domain_id'], 'integer'],
            ['slug', 'string', 'max' => 256],
            ['name', 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'slug' => 'ЧПУ',
            'name' => 'Наименование',
            'city_from' => 'Город отправки',
            'city_to' => 'Город доставки',
            'category_id' => 'ИД категории',
            'domain_id' => 'ИД города',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCityFrom()
    {
        return $this->hasOne(City::class, ['id' => 'city_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityTo()
    {
        return $this->hasOne(City::class, ['id' => 'city_to']);
    }

    /**
     * @return ActiveQuery
     */
    public function getDomain()
    {
        return $this->hasOne(City::class, ['id' => 'domain_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(CargoCategory::class, ['id' => 'category_id']);
    }

    public function beforeSave($insert)
    {
        $this->url = 'https://' . Yii::getAlias('@domain') .
            Url::toRoute(['/transport/search/index', 'slug' => $this->slug, 'location' => $this->domain]);

        return parent::beforeSave($insert);
    }

    /**
     * Возвращает список тегов подходящих под указанную локацию
     * @param LocationInterface $location
     * @return ActiveQuery
     */
    public static function findTags(LocationInterface $location = null): ActiveQuery
    {
        $query = self::find();
        if($location instanceof City)
            $query->where(['domain_id' => $location->getId()]);
        elseif($location instanceof Region && $location->center)
            $query->where(['domain_id' => $location->center]);
        elseif (is_null($location))
            $query->where(['domain_id' => null]);

        return $query;
    }

    /**
     * Получаем следующий тег
     */
    public function getNext(){
        $query = self::find()
            ->andWhere(['AND',
                ['domain_id' => $this->domain_id],
                ['>', 'slug', $this->slug]
            ]);

        return $query->limit(1)->one();
    }

    /**
     * Получаем предыдущий тег
     */
    public function getPrev(){
        $query = self::find()
            ->andWhere(['AND',
                ['domain_id' => $this->domain_id],
                ['<', 'slug', $this->slug]
            ]);

        return $query->orderBy(['slug' => SORT_DESC])->limit(1)->one();
    }
}
