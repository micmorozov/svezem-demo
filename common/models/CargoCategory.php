<?php

namespace common\models;

use common\behaviors\JunctionBehavior;
use common\helpers\Utils;
use common\models\query\CargoCategoryQuery;
use Yii;
use yii\base\BaseObject;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cargo_category".
 *
 * @property integer $id
 * @property string $category
 * @property string $category_rod
 * @property string $slug
 * @property string $keywords ключи поиска
 * @property int $city_size_mask
 * @property TransportCargoCategoryAssn[] $transportCargoCategoryAssns
 * @property Transport[] $transports
 *
 * @property boolean $root
 * @property boolean $transport_type
 * @property boolean $transportation_object
 * @property boolean $load_type
 * @property int $show_filter Показывать в фильтрах
 * @property int $show_add_transport Показывать на странице добавления транспорта
 * @property int $show_moder_cargo Показывать у модератора в грузах
 * @property int $show_moder_tr_tk Показывать у модератора в перевозчиках и ТК
 * @property int $create_tag Участвует в формировании тега
 * @property int $transportation_type Вид перевозки
 * @property int $private_transportation Частные перевозки
 * @property int $rent Аренда
 *
 * @property CargoCategory[] $nodes
 * @property CargoCategory[] $parents
 * @property array $parentsids
 * @property array $nodesids
 */
class CargoCategory extends ActiveRecord
{
    //требуется при сохранении родителей в дереве
    protected $_parentsIds;

    // ид дочерних (категорий)узлов
    protected $_nodesIds;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cargo_category';
    }

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        if ($this->isNewRecord) {
            $this->create_tag = 1;
        }
    }

    public function behaviors()
    {
        return [
            [
                //Сохранение данных в промежуточные таблицы
                'class' => JunctionBehavior::class,
                'association' => [
                    ['parentsids', self::class, 'parents']
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category', 'category_rod', 'slug'], 'required'],
            [['category', 'category_rod', 'tr_extra_title'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 128],
            [
                [
                    'parentsids',
                    'transport_type',
                    'transportation_object',
                    'load_type',
                    'show_filter',
                    'show_add_transport',
                    'show_moder_cargo',
                    'show_moder_tr_tk',
                    'create_tag',
                    'transportation_type',
                    'private_transportation',
                    'rent',
                    'citySizeMask'
                ],
                'safe'
            ],

            [['keywords'], 'string', 'max' => 512],

            ['slug', 'uniqueByParent']
        ];
    }

    /**
     * slug должен быть уникальным в рамках одного родителя
     * @param $attribute
     * @param $params
     */
    public function uniqueByParent($attribute, $params) {
        if($this->parentsids) {
            foreach ($this->parentsids as $parentId) {
                $childNodes = $this->getNodesByParentId($parentId);
                foreach ($childNodes as $node) {
                    if ($node['slug'] == $this->slug && $node['id'] != $this->id) {
                        $this->addError($attribute, 'Slug должен быть уникальным в рамках одного родителя');
                    }
                }
            }
        }else{
            // Проверка для родительской категории
            $cnt = self::find()
                ->where([
                    'root' => 1,
                    'slug' => $this->slug
                ])
                ->andWhere(['not', ['id' => $this->id]])
                ->count();
            if($cnt){
                $this->addError($attribute, 'Slug должен быть уникальным в рамках одного родителя');
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Название',
            'category_rod' => 'Название в Род. падеже',
            'slug' => 'ЧПУ',
            'keywords' => 'Ключи поиска',
            'root' => 'Корень',
            'transport_type' => 'Требования к транспорту',
            'load_type' => 'Способ погрузки',
            'show_filter' => 'Показывать в фильтрах',
            'show_add_transport' => 'Показывать на странице добавления транспорта',
            'show_moder_cargo' => 'Показывать у модератора в грузах',
            'show_moder_tr_tk' => 'Показывать у модератора в перевозчиках и ТК',
            'create_tag' => 'Участвует в формировании тега',
            'transportation_type' => 'Вид услуг',
            'transportation_object' => 'Объект перевозки',
            'private_transportation' => 'Частные перевозки',
            'rent' => 'Аренда',
            'tr_extra_title' => 'Доп. заголовок для перевозчика'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getTransportCargoCategoryAssns()
    {
        return $this->hasMany(TransportCargoCategoryAssn::class, ['category_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTransports()
    {
        return $this->hasMany(Transport::class, ['id' => 'transport_id'])->viaTable('transport_cargo_category_assn',
            ['category_id' => 'id']);
    }

    /**
     * @return array
     */
    public static function filterList()
    {
        return CargoCategory::find()
            ->showFilter()
            ->all();
    }

    public static function getNotRootList()
    {
        return CargoCategory::find()
            ->root(0)
            ->all();
    }

    static public function transportTypeList()
    {
        return CargoCategory::find()
            ->transportType()
            ->all();
    }

    static public function loadTypeList()
    {
        return CargoCategory::find()
            ->loadType()
            ->all();
    }

    /**
     * @inheritdoc
     * @return CargoCategoryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new CargoCategoryQuery(get_called_class());
    }

    /**
     * @param CargoCategory $model
     * @return array
     */
    static public function tree($selectId = null)
    {

        /** @var $models self[] */
        $models = self::find()
            ->root()
            ->all();

        $data = [];
        foreach ($models as $model) {
            $data[] = [
                'id' => $model->id,
                'text' => $model->category,
                'nodes' => $model->nodesArray($selectId),
                'state' => [
                    'selected' => $model->id == $selectId
                ]
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    static public function treeModerCargo()
    {

        /** @var $models self[] */
        $models = self::find()
            ->with(['nodes'])
            ->root()
            ->showModerCargo()
            ->all();

        $data = [];
        foreach ($models as $model) {
            $nodes = $model->nodesModerCargoArray();

            $data[] = [
                'id' => $model->id,
                'text' => $model->category,
                'show_moder' => $model->show_moder_cargo,
                'nodes' => $nodes
            ];
        }

        return $data;
    }

    public function getNodes()
    {
        return $this->hasMany(self::class, ['id' => 'category_id'])
            ->viaTable('category_tree_assn', ['parent' => 'id'])
            ->cache(2);
    }

    public function getNodesIds()
    {
        if ( !isset($this->_nodesIds)) {
            $this->_nodesIds = ArrayHelper::getColumn($this->nodes, 'id');
        }
        return $this->_nodesIds;
    }

    protected function nodesArray($selectId = null)
    {
        $data = [];
        foreach ($this->nodes as $model) {
            $data[] = [
                'id' => $model->id,
                'text' => $model->category,
                'nodes' => $model->nodesArray($selectId),
                'state' => [
                    'selected' => $model->id == $selectId
                ]
            ];
        }
        return $data;
    }

    protected function nodesModerCargoArray()
    {
        $models = array_filter($this->nodes, function ($model){
            /** @var CargoCategory $model */
            return $model->show_moder_cargo;
        });

        $data = [];
        foreach ($models as $model) {
            $data[] = [
                'id' => $model->id,
                'text' => $model->category,
                'show_moder' => $model->show_moder_cargo,
                //если уровней будет больше 2-х
                //'nodes' => $model->nodesModerCargoArray()
            ];
        }
        return $data;
    }

    public function getParents()
    {
        return $this->hasMany(self::class, ['id' => 'parent'])
            ->viaTable('category_tree_assn', ['category_id' => 'id']);
    }

    public function getParentsIds()
    {
        if ( !isset($this->_parentsIds)) {
            $this->_parentsIds = ArrayHelper::getColumn($this->parents, 'id');
        }
        return $this->_parentsIds;
    }

    public function setParentsIds($v)
    {
        $this->_parentsIds = is_array($v) ? $v : [];
    }

    public function getCitySizeMask():array
    {
        return Utils::mask_decode($this->city_size_mask);
    }

    public function setCitySizeMask($mask)
    {
        if(!is_array($mask)) $mask = [];

        $this->city_size_mask = Utils::mask_encode($mask);
    }

    public function beforeValidate()
    {
        if ( !parent::beforeValidate()) {
            return false;
        }

        //если указаны родители
        if ( !empty($this->_parentsIds)) {
            if (in_array($this->id, $this->_parentsIds)) {
                $this->addError('parents', 'Не может быть родителем самого себя');
                return false;
            }

            //проверяем что указанные родители находятся в корне
            $models = self::findAll([
                'id' => $this->_parentsIds,
                'root' => 1
            ]);
            if (count($models) != count($this->_parentsIds)) {
                $this->addError('parents', 'Вложенность не более 2-х уровней');
                return false;
            }

            //если имеет дочерние
            if ( !empty($this->nodes)) {
                $this->addError('parents', 'Узел не может иметь родителей т.к. сам имеет узлы');
                return false;
            }
        }

        return !$this->hasErrors();
    }

    public function beforeSave($insert)
    {
        if ( !parent::beforeSave($insert)) {
            return false;
        }

        //если указаны родители
        if ($this->_parentsIds) {
            $this->root = 0;
        } else {
            $this->root = 1;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        //если изменился slug и это не создание новой модели
        if (in_array('slug', array_keys($changedAttributes)) && !$insert) {
            Yii::$app->gearman->getDispatcher()->background("changeCategorySlug", [
                'category_id' => $this->id
            ]);
        }

        /////////////////
        // Восстанавливаем размеры городов из маски
        $this->city_size_mask = Utils::mask_decode($this->city_size_mask);
        /////////////////
    }

    public function remove()
    {
        $transaction = static::getDb()->beginTransaction();

        foreach ($this->nodes as $node) {
            //если удаляемая категория является единственным родителем
            if (count($node->parents) == 1) //то делаем дочернюю корнем
            {
                self::updateAll(['root' => 1], ['id' => $node->id]);
            }
        }

        if ($result = $this->delete()) {
            $transaction->commit();
        } else {
            $transaction->rollBack();
        }

        return $result;
    }

    /*static public function moderList(){
        return self::find()->showModer()->all();
    }*/

    static public function rootModerList()
    {
        return self::find()->root()->showModer()->all();
    }

    static public function moderTrTKList()
    {
        return self::find()->showModerTrTK()->all();
    }

    static public function showAddTransportList()
    {
        return self::find()->showAddTransport()->all();
    }

    /**
     * @param array $ids
     * @return string
     */
    static public function createSphinxQuery($ids)
    {
        $categories = CargoCategory::find()->where(['id' => $ids])->asArray()->all();

        $keywords = [];
        foreach ($categories as $category) {
            if (trim($category['keywords']) != '') {
                $keywords[] = '('.trim($category['keywords']).')';
            }
        }

        $sphinxMatch = implode('|', $keywords);

        if ($sphinxMatch == '') {
            $sphinxMatch = ' ';
        }

        return $sphinxMatch;
    }

    /**
     * @param $id
     * @param bool $absolute
     * @return string
     */
    static public function getIcon($id, $absolute = false)
    {
        //Картинка
        // Отображаем иконки категорий
        $resultPath = "/img/icons/categories/icon_{$id}.svg";
        if ( !file_exists(Yii::getAlias('@webroot').$resultPath)) {
            $resultPath = "/img/icons/categories/icon_x.svg";
        }

        if ($absolute) {
            $resultPath = "https://".Yii::getAlias('@assetsDomain').$resultPath;
        }

        return $resultPath;
    }

    /**
     * @param $id
     * @param bool $absolute
     * @return string
     */
    static public function getIconPng($id, $absolute = false)
    {
        //Картинка
        // Отображаем иконки категорий
        $resultPath = "/img/icons/categories_png/icon_{$id}.png";
        if ( !file_exists(Yii::getAlias('@webroot').$resultPath)) {
            $resultPath = "/img/icons/categories_png/icon_x.png";
        }

        if ($absolute) {
            $resultPath = "https://".Yii::getAlias('@assetsDomain').$resultPath;
        }

        return $resultPath;
    }

    /**
     * @param int|array $catIds
     * @return array
     */
    public static function getNodeIdsByParentIds($catIds)
    {
        $result = (new Query)
            ->distinct('category_id')
            ->select('category_id')
            ->from('category_tree_assn')
            ->where(['parent' => $catIds])
            ->all();

        return array_map(function ($item){
            return $item['category_id'];
        }, $result);
    }

    /**
     * @param int|array $catIds
     * @return array
     */
    public static function getNodesByParentId(int $parentId)
    {
        return (new Query)
            ->from('category_tree_assn cta')
            ->innerJoin('cargo_category cc', 'cc.id=cta.category_id')
            ->where(['parent' => $parentId])
            ->all();
    }

    /**
     * @param int|array $catIds
     * @return array
     */
    public static function getParentIdsByChildIds($catIds)
    {
        $result = (new Query)
            ->distinct('parent')
            ->select('parent')
            ->from('category_tree_assn')
            ->where([
                'and',
                ['category_id' => $catIds]
            ])
            ->all();

        return array_map(function ($item){
            return $item['parent'];
        }, $result);
    }
}
