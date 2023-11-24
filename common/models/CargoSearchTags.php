<?php

namespace common\models;

use common\behaviors\SlugBehavior;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\helpers\Url;

/**
 * This is the model class for table "cargo_search_tags".
 *
 * @property integer $id
 * @property string $slug
 * @property string $url
 * @property string $name
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
class CargoSearchTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cargo_search_tags';
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
            'domain_id' => 'ИД города (для поиска)',
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
    public function getCategory()
    {
        return $this->hasOne(CargoCategory::class, ['id' => 'category_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getDomain()
    {
        return $this->hasOne(City::class, ['id' => 'domain_id']);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        try {
            return parent::save($runValidation, $attributeNames);
        } catch(Exception $e){
                // при одновременных запросах регистрации валидатор уникальности пропускает одинаковые email
                // чтобы выдать пользователю красивый ответ ловим исключение базы данных
                // о дублирующихся значениях
                //Integrity constraint violation: 1062
                if( $e->errorInfo[1] == 1062 )
                    $this->addError('slug', $e->getMessage());

                return false;
        }
    }

    public function beforeSave($insert)
    {
        $this->url = 'https://' . Yii::getAlias('@domain') .
            Url::toRoute(['cargo/search/index', 'slug' => $this->slug, 'location' => $this->domain]);

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
}
