<?php

namespace frontend\modules\cabinet\models;

use common\models\Cargo;
use common\models\CargoCategory;
use common\models\City;
use common\models\FastCity;
use common\models\LocationCategorySearch;
use common\models\Region;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQuery;
use yii\db\Query;

class CargoBookingSearch extends LocationCategorySearch
{

    /**
     * Статус отображаемых заказов
     * @var null
     */
    public $status = null;

    /**
     * Отображать только новые заказы или все за исключением новых
     * @var bool
     */
    public $allCargo = false;

    public function rules(){
        return array_merge(parent::rules(), [
            ['status', 'in', 'range' => [Cargo::STATUS_WORKING, Cargo::STATUS_DONE]],
            ['allCargo', 'in', 'range' => [0,1]]
        ]);
    }

    public function prepare(): DataProviderInterface
    {
        $this->fillCategoryByFilter();

        if( $this->status ){
            $query = $this->getFilterQuery();
        } else {
            $query = $this->searchQuery();
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => $this->pageSize,
                'forcePageParam' => false
            ],
        ]);
    }

    /**
     * @return ActiveQuery
     */
    public function searchQuery(){
        //$blockMinutes = Setting::getValueByCode(Setting::CARGO_BOOKING_BLOCK, 30);

        $query = Cargo::find()
            //cache - это хитрость помогает убрать
            //дублирование запросов
            ->cache(2)
            ->alias('t')
            ->with('cityFrom.country')
            ->with('cityTo.country')
            ->with('cargoCategory')
            // TODO надо комментарии вернуть
            /*->with(['bookingComment' => function($q){

                $q->where(['created_by' => Yii::$app->user->id]);
            }])*/
            ->andFilterCompare('t.status', Cargo::STATUS_ACTIVE)
            // Отображаем все
            //->andWhere([$this->allCargo?'<':'>', 't.created_at', strtotime("-$blockMinutes min")])
            ->orderBy(['t.created_at' => SORT_DESC]);
            //->orderBy(["id" => $this->order]);

        $this->addSearchFormToQuery($query);


        return $query;
    }

    /**
     * Ф-ция добавляет параметры из формы поиска
     * @param Query $query
     */
    private function addSearchFormToQuery($query){
        //если любое направление, то есть поиск по городу/региону отправки или доставки
        if($this->anyDirection && $this->locationFrom){
            if($this->locationFrom instanceof City){
                $query->andWhere(['OR',
                    ['city_from' => $this->locationFrom->getId()],
                    ['city_to' => $this->locationFrom->getId()],
                ]);
            }

            if($this->locationFrom instanceof Region){
                $query->andWhere(['OR',
                    ['region_from' => $this->locationFrom->getId()],
                    ['region_to' => $this->locationFrom->getId()],
                ]);
            }
        } else{
            if($this->locationFrom){
                if($this->locationFrom instanceof City){
                    $query->andWhere(['city_from' => $this->locationFrom->getId()]);
                }
                if($this->locationFrom instanceof Region){
                    $query->andWhere(['region_from' => $this->locationFrom->getId()]);
                }
            }

            if($this->locationTo){
                if($this->locationFrom instanceof City){
                    $query->andWhere(['city_to' => $this->locationTo->getId()]);
                }
                if($this->locationTo instanceof Region){
                    $query->andWhere(['region_to' => $this->locationTo->getId()]);
                }
            }
        }

        //указаны категориии
        if($this->cargoCategoryIds){
            //Поиск категории
            //Если указана дочерняя категория, то ищим по ней
            //Если корневая, то по ней и её дочерним

            //$cats = CargoCategory::findAll($this->cargoCategoryIds);
            $cats = CargoCategory::find()
                ->cache()
                ->with('nodes')
                ->where(['id' => $this->cargoCategoryIds])
                ->all();

            $catIds = (array)$this->cargoCategoryIds;
            foreach($cats as $cat){
                if($cat->root)
                    $catIds = array_merge($catIds, $cat->nodesids);
            }

            $query->joinWith('categories')
                ->andFilterWhere([CargoCategory::tableName().'.id' => $catIds]);
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getFilterQuery(){
        $query = Cargo::find()
            ->alias('t')
            ->with('cityFrom.country')
            ->with('cityTo.country')
            ->with('cargoCategory')
            ->andWhere(['t.status' => $this->status])
            ->andWhere(['booking_by' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->orderBy(["id" => $this->order])
            ->groupBy('id');

        $this->addSearchFormToQuery($query);

        return $query;
    }
}
