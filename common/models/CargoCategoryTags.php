<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Query;

/**
 * This is the model class for table "cargo_category_tags".
 *
 * @property integer $id
 * @property string $url
 * @property string $name
 * @property string $city_id
 * @property string $region_id
 * @property string $category_id
 */
class CargoCategoryTags extends ActiveRecord implements TagInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cargo_category_tags}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'name'], 'required'],
            [['url'], 'string', 'max' => 256],
            [['name'], 'string', 'max' => 128],
            [['city_id', 'region_id', 'category_id'], 'integer'],
            ['city_id', 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_id' => 'id']],
            ['region_id', 'exist', 'skipOnError' => true, 'targetClass' => Region::class, 'targetAttribute' => ['region_id' => 'id']],
            ['category_id', 'exist', 'skipOnError' => true, 'targetClass' => CargoCategory::class, 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'url' => 'Урл',
            'name' => 'Наименование'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::class, ['id' => 'region_id']);
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
     * Получаем список ссылок на соседние страницы относительно переденной
     * @param LocationInterface $location
     * @param CargoCategory $cargoCategory
     * @param int $count
     * @return array
     */
    public static function getCategoryTags(CargoCategory $cargoCategory, LocationInterface $location = null, int $count = 5):?array
    {
        $query = self::find();
        if(is_null($location) || ($location instanceof City)){
            $query->andWhere([
                'city_id' => is_null($location) ? null : $location->getId()
            ]);
        }else{
           return null;
        }
        $listAfter = clone $query;

        // Ищем позицию переданной страницы в списке
        $curTag = (clone $query)
            ->andWhere(['category_id' => $cargoCategory->id])
            ->one();
        if($curTag){
            $listAfter->andWhere(['>', 'id', $curTag->id]);
        }

        $result = $listAfter
            ->orderBy(['id' => SORT_ASC])
            ->limit($count)
            ->all();

        // Если находимся в конце списка, то добавляем недостающие элементы с начала списка
        if(count($result) < $count){
            $res = $query
                ->orderBy(['id' => SORT_ASC])
                ->limit($count-count($result))
                ->all();
            if($res) array_push($result, ...$res);
        }

        return $result;
    }

    public function getTitle(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
