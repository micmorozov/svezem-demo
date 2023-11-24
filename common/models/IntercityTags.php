<?php

namespace common\models;

use common\behaviors\SlugBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\helpers\Url;

/**
 * This is the model class for table "intercity_tags".
 *
 * @property integer $id
 * @property string $url
 * @property string $name
 * @property integer $city_from
 * @property integer $city_to
 * @property integer $category_id
 * @property integer $ads_count
 *
 * @property City $cityFrom
 * @property City $cityTo
 */
class IntercityTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'intercity_tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'name'], 'required'],
            [['city_from', 'city_to', 'category_id', 'ads_count'], 'integer'],
            [['url'], 'string', 'max' => 256],
            [['name'], 'string', 'max' => 128],
            [['city_from'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_from' => 'id']],
            [['city_to'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_to' => 'id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => CargoCategory::class, 'targetAttribute' => ['category_id' => 'id']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'url' => 'Урл',
            'name' => 'Наименование',
            'city_from' => 'Город отправки',
            'city_to' => 'Город доставки',
            'category_id' => 'ИД категории',
            'ads_count' => 'Количество объявлений'
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

    /**
     * Теги для направления Из города
     * @param LocationInterface|null $location
     * @param int $limit
     * @return array|null
     */
    public static function getFromLocationTags(LocationInterface $location = null, $limit = 10): ?array
    {
        $result = null;
        if($location instanceof City) {
            $result = self::find()
                ->where([
                    'city_from' => $location->getId()
                ])
                ->orderBy(['ads_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }

        return $result;
    }

    /**
     * Теги для направления В город
     * @param LocationInterface $location
     * @param int $limit
     * @return array|null
     */
    public static function getToLocationTags(LocationInterface $location = null, $limit = 10): ?array
    {
        $result = null;
        if($location instanceof City) {
            $result = self::find()
                ->where([
                    'city_to' => $location->getId()
                ])
                ->orderBy(['ads_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }

        return $result;
    }
}
