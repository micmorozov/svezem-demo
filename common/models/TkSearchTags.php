<?php

namespace common\models;

use common\behaviors\SlugBehavior;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * This is the model class for table "tk_search_tags".
 *
 * @property string $slug
 * @property string $name
 * @property integer $city_from
 * @property integer $city_to
 * @property integer $category_id
 * @property integer $domain_id
 *
 * @property CargoCategory $category
 * @property City $cityFrom
 * @property City $cityTo
 *
 * @property string $searchLink
 */
class TkSearchTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tk_search_tags';
    }

    public function behaviors()
    {
        return [
            [
                'class' => SlugBehavior::className(),
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
            [['slug'], 'string', 'max' => 256],
            [['name'], 'string', 'max' => 128],
            [['slug', 'domain_id'], 'unique', 'targetAttribute' => ['slug', 'domain_id'], 'message' => 'Комбинация ЧПУ и ИД города уже используется'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => CargoCategory::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['city_from'], 'exist', 'skipOnError' => true, 'targetClass' => City::className(), 'targetAttribute' => ['city_from' => 'id']],
            [['city_to'], 'exist', 'skipOnError' => true, 'targetClass' => City::className(), 'targetAttribute' => ['city_to' => 'id']],
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
            'domain_id' => 'ИД города (для поиска)',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(CargoCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityFrom()
    {
        return $this->hasOne(City::className(), ['id' => 'city_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityTo()
    {
        return $this->hasOne(City::className(), ['id' => 'city_to']);
    }

    public function getSearchLink(){
        return Url::toRoute("/tk/search/{$this->slug}");
    }
}
