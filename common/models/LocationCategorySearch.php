<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 28.08.18
 * Time: 12:32
 */

namespace common\models;

use common\helpers\LocationHelper;
use Exception;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\console\Application;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;

abstract class LocationCategorySearch extends Model
{
    /** @var int  */
    protected $pageSize = 5;

    /** @var int */
    protected $page = 1;

    /** @var int  */
    protected $order = SORT_DESC;

    /** @var LocationInterface */
    protected $locationFrom;

    /** @var LocationInterface */
    protected $locationTo;

    /** @var int[] */
    protected $categoryFilter;

    /** @var int[] */
    protected $cargoCategoryIds;

    /**
     * Город доставки должен быть  отличным от города отправки
     *
     * @var bool $diffDirection
     */
    protected $diffDirection = false;

    /**
     * Любой город/регион вне зависимости от направления
     * @var bool $anyDirection
     */
    protected $anyDirection = false;

    public function rules()
    {
        return [
            [['locationFrom', 'locationTo', 'cargoCategoryIds', 'categoryFilter'], 'safe']
        ];
    }

    public function attributeLabels() {
        return [
            'locationFrom' => 'Откуда',
            'locationTo' => 'Куда',
            'categoryFilter' => 'Что'
        ];
    }

    public function formName()
    {
        return '';
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search(array $params = []): DataProviderInterface
    {
        $this->load($params);

        /** @var ActiveDataProvider $dataProvider */
        return Yii::$app->cache->getOrSet($this->getCacheKey(), function (){
            return $this->prepare();
        }, 3600);
    }

    public function load($data, $formName = null)
    {
        if(isset($data['locationFrom'])){
            $this->setLocationFrom(LocationHelper::getLocationByCode($data['locationFrom']));
            unset($data['locationFrom']);
        }

        if(isset($data['locationTo'])){
            $this->setLocationTo(LocationHelper::getLocationByCode($data['locationTo']));
            unset($data['locationTo']);
        }

        if(isset($data['categoryFilter'])){
            $this->setCategoryFilter($data['categoryFilter']);
            unset($data['categoryFilter']);
        }

        if(isset($data['cargoCategoryIds'])){
            $this->setCategoryFilter($data['cargoCategoryIds']);
            unset($data['cargoCategoryIds']);
        }

        parent::load($data, $formName);
    }

    public function init(){
        parent::init();

        //Ограничиваем кол-во вывода
        if( Yii::$app instanceof Application){
            $this->pageSize = Yii::$app->params['itemsPerPage']['defaultPageSize'];
            $this->page = 1;
        } else {
            $this->pageSize = Yii::$app->session->get('per-page', Yii::$app->params['itemsPerPage']['defaultPageSize']);
            $this->page = max(1, (int)Yii::$app->request->get('page', 1));
        }
    }

    /**
     * Выбранное значение в выпадающем списке
     * @param LocationInterface $location
     * @return array
     */
    public function getLocationString(LocationInterface $location = null): array
    {
        $result = [];
        if ($location instanceof LocationInterface) {
            $result = [
                $location->getCode() => $location->getFullTitle()
            ];
        }
        return $result;
    }

    protected function prepare(): DataProviderInterface
    {
        throw new \yii\db\Exception('Prepare method must be realized');
    }

    protected function fillCategoryByFilter()
    {
        if ($this->categoryFilter) {
            $this->cargoCategoryIds = CategoryFilter::getCategoriesByFilterIds($this->categoryFilter);
        }
    }

    public function setDirection(bool $anyDirection): self
    {
        $this->anyDirection = $anyDirection;

        return $this;
    }

    public function setDiffDirection(bool $diffDirection): self
    {
        $this->diffDirection = $diffDirection;

        return $this;
    }

    public function setLocationFrom(LocationInterface $location = null): self
    {
        $this->locationFrom = $location;

        return $this;
    }

    public function getLocationFrom(): ?LocationInterface
    {
        return $this->locationFrom;
    }

    public function setLocationTo(LocationInterface $location = null): self
    {
        $this->locationTo = $location;

        return $this;
    }

    public function getLocationTo(): ?LocationInterface
    {
        return $this->locationTo;
    }

    public function setSortOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function setPageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setCargoCategories($cargoCategories = null): self
    {
        if(is_null($cargoCategories)) $cargoCategories = [];

        if($cargoCategories instanceof CargoCategory) $cargoCategories = [$cargoCategories];

        if(!is_array($cargoCategories))
            throw new Exception('cargoCategories must be an array or CargoCategory class');

        $this->cargoCategoryIds = array_map(function(CargoCategory $category){
            return $category->id;
        }, $cargoCategories);

        return $this;
    }

    public function getCargoCategoryIds(): ?array
    {
        return $this->cargoCategoryIds;
    }

    public function setCategoryFilter($categoryFilter = null): self
    {
        if(!$categoryFilter) $categoryFilter = [];

        $this->categoryFilter = $categoryFilter;

        return $this;
    }

    public function getCategoryFilter(): ?array
    {
        return $this->categoryFilter;
    }

    public function setCargoCategoryIds($categoryIds = null): self
    {
        if(!$categoryIds) $categoryIds = [];

        $this->cargoCategoryIds = $categoryIds;

        return $this;
    }

    protected function getCacheKey(): array
    {
        return [
            strtolower(str_replace('\\', '_', get_called_class())),
            $this->page,
            $this->pageSize,
            $this->locationFrom ? $this->locationFrom->getCode() : null,
            $this->locationTo ? $this->locationTo->getCode() : null,
            $this->cargoCategoryIds,
            $this->categoryFilter,
            $this->diffDirection,
            $this->anyDirection,
            $this->order
        ];
    }
}
