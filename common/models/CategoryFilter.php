<?php

namespace common\models;

use common\behaviors\JunctionBehavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "category_filter".
 *
 * @property int $id
 * @property string $title Наименование
 * @property int $type Тип
 * @property int $sort Порядок сортировки
 * @property int $other Прочее
 *
 * @property CargoCategory[] $categories
 * @property array $categoryIds
 */
class CategoryFilter extends ActiveRecord
{
    const TYPE_CARGO_SEARCH = 1;
    const TYPE_TRANSPORT_SEARCH = 2;
    const TYPE_TK_SEARCH = 3;
    const TYPE_SUBSCRIBE = 4;

    private $_categoryIds = null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category_filter';
    }

    public function behaviors()
    {
        return [
            [
                //Сохранение данных в промежуточные таблицы
                'class' => JunctionBehavior::class,
                'association' => [
                    ['categoryIds', CargoCategory::class, 'categories']
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'sort'], 'required'],
            [['title'], 'string', 'max' => 64],
            [['type', 'sort', 'other'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование',
            'type' => 'Тип',
            'sort' => 'Порядок сортировки',
            'other' => 'Прочее'
        ];
    }

    public static function getTypelabels()
    {
        return [
            self::TYPE_CARGO_SEARCH => 'Фильтр поиска грузов',
            self::TYPE_TRANSPORT_SEARCH => 'Фильтр поиска транспорта',
            self::TYPE_TK_SEARCH => 'Фильтр поиска тк',
            self::TYPE_SUBSCRIBE => 'Фильтр подписок'
        ];
    }

    public static function getTypeLabel($type)
    {
        $list = self::getTypelabels();
        return $list[$type]??'тип не определен';
    }

    /**
     * @param $type
     * @return bool
     */
    public static function allowOther($type){
        return $type == self::TYPE_CARGO_SEARCH;
    }

    /**
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getCategories()
    {
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('category_filter_assn', ['filter_id' => 'id']);
    }

    /**
     * @return array|null
     */
    public function getCategoryIds()
    {
        if ( $this->_categoryIds === null ) {
            $this->_categoryIds = array_map(function ($model){
                /** @var CargoCategory $model */
                return $model->id;
            }, $this->categories);
        }
        return $this->_categoryIds;
    }

    /**
     * @param array $v
     */
    public function setCategoryIds(array $v)
    {
        $this->_categoryIds = $v;
    }

    public static function searchFilter($type)
    {
        $categories = self::find()
            ->where(['type' => $type])
            ->orderBy(['sort' => SORT_ASC])
            ->asArray()
            ->all();

        return ArrayHelper::map($categories, 'id', 'title');
    }

    /**
     * @return array
     */
    public static function cargoSearchFilter()
    {
        return self::searchFilter(self::TYPE_CARGO_SEARCH);
    }

    /**
     * @return array
     */
    public static function transportSearchFilter()
    {
        return self::searchFilter(self::TYPE_TRANSPORT_SEARCH);
    }

    /**
     * @return array
     */
    public static function tkSearchFilter()
    {
        return self::searchFilter(self::TYPE_TK_SEARCH);
    }

    /**
     * @param $type
     * @return bool
     */
    public static function generateOther($type)
    {
        $filter = self::find()
            ->where(['other' => 1])
            ->andWhere(['type' => $type])
            ->one();

        if ( !$filter) {
            $filter = new CategoryFilter();
            $filter->title = 'Прочее';
            $filter->other = 1;
            $filter->type = $type;
            $filter->sort = 999999;
            $filter->save();
        }

        //Находим незадействованные категории
        $sub_query = CategoryFilter::find()
            ->alias('cf')
            ->select('category_id')
            ->innerJoin('category_filter_assn cfa', 'cfa.filter_id = cf.id')
            ->where(['cf.type' => $type])
            ->andWhere(['other' => 0]);

        $categories = CargoCategory::find()
            ->select('id')
            ->where(['not', ['id' => $sub_query]])
            ->andWhere(['<>', 'transportation_type', 1])
            ->andWhere(['<>', 'private_transportation', 1])
            ->asArray()
            ->all();

        $categories = array_map(function ($item){
            return $item['id'];
        }, $categories);

        $filter->categoryIds = $categories;
        return $filter->save();
    }

    /**
     * @param array $ids
     * @return array
     */
    public static function getCategoriesByFilterIds(array $ids)
    {
        $res = (new Query())
            ->select('category_id')
            ->from('category_filter_assn cfa')
            ->leftJoin('category_filter cf', 'cfa.filter_id=cf.id')
            ->where(['id' => $ids])
            ->all();

        return array_map(function ($item){
            return $item['category_id'];
        }, $res);
    }
}
