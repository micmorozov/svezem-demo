<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 05.10.17
 * Time: 12:33
 */

namespace frontend\modules\cargo\models;

use common\models\Cargo;
use common\models\CargoCategory;
use common\models\City;
use common\models\FastCity;
use common\models\LocationCategorySearch;
use common\models\Region;
use Throwable;
use Yii;
use yii\caching\TagDependency;
use yii\console\Application;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

class CargoSearch extends LocationCategorySearch
{
    /**
     * Отображать грузы у которых главная категория соответствует $cargoCategoryIds
     * @var bool
     */
    private $showMainCargoCategory = false;

    /**
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    protected function prepare(): DataProviderInterface
    {
        $this->fillCategoryByFilter();

        $query = Cargo::find()
            ->alias('t')
            ->with(['cityFrom.country', 'cityTo.country', 'cargoCategory'])
            // Этот join здесь размещать нельзя так как на страницах cargo/transportation/perevozka-veshej/ (род категория)
            // создается своя связка с cargo_categories и возникает дублирование
            //->joinWith('cargoCategory')
            ->andWhere(['t.status' => [Cargo::STATUS_ACTIVE, Cargo::STATUS_WORKING, Cargo::STATUS_DONE]])
            ->groupBy('t.id')
            ->orderBy(['created_at' => $this->order]);
        //->groupBy('id');
        //->orderBy(["id" => $this->order]);

        //если любое направление, то есть поиск по городу/региону отправки или доставки
        if ($this->anyDirection && $this->locationFrom) {
            if ($this->locationFrom instanceof City) {
                $query->andWhere([
                    'OR',
                    ['city_from' => $this->locationFrom->getId()],
                    ['city_to' => $this->locationFrom->getId()],
                ]);
            }

            if ($this->locationFrom instanceof Region) {
                $query->andWhere([
                    'OR',
                    ['region_from' => $this->locationFrom->getId()],
                    ['region_to' => $this->locationFrom->getId()],
                ]);
            }
        } else {
            if ($this->locationFrom) {
                if ($this->locationFrom instanceof City) {
                    $query->andWhere(['city_from' => $this->locationFrom->getId()]);
                }
                if ($this->locationFrom instanceof Region) {
                    $query->andWhere(['region_from' => $this->locationFrom->getId()]);
                }
            }

            if ($this->locationTo) {
                if ($this->locationTo instanceof City) {
                    $query->andWhere(['city_to' => $this->locationTo->getId()]);
                }
                if ($this->locationTo instanceof Region) {
                    $query->andWhere(['region_to' => $this->locationTo->getId()]);
                }
            }
            //если локация доставки не указана
            // и необходимо найти груз чтобы город доставки
            // отличался от города отправки
            elseif ($this->locationFrom && $this->diffDirection) {
                if ($this->locationFrom instanceof City) {
                    $query->andWhere(['<>', 'city_to', $this->locationFrom->getId()]);
                }
                if ($this->locationTo instanceof Region) {
                    $query->andWhere(['<>', 'region_to', $this->locationFrom->getId()]);
                }
            }
        }

        //указаны категориии
        if ($this->cargoCategoryIds) {
            //Поиск категории
            //Если указана дочерняя категория, то ищим по ней
            //Если корневая, то по ней и её дочерним

            $category = CargoCategory::find()
                ->where(['id' => $this->cargoCategoryIds])
                ->one();

            // Для дочерней категории должны отображаться грузы у которых в качестве главной категории указана данная категория
            if ($this->showMainCargoCategory && !$category->root) {
                $query->joinWith('cargoCategory')
                    ->andWhere(['cargo_category_id' => $this->cargoCategoryIds]);
            } else {
//                $catIds = CargoCategory::getNodeIdsByParentIds($this->cargoCategoryIds);
//                $catIds = array_merge($catIds, $this->cargoCategoryIds);
//                $catIds = array_unique($catIds);

                $catIds = $this->cargoCategoryIds;
                // Для родительских категорий нужны грузы из подкатегорий
                if($category->root) {
                    $catIds = array_merge($catIds, CargoCategory::getNodeIdsByParentIds($this->cargoCategoryIds));
                }

                $query->joinWith([
                    'categories'
                ], true, 'INNER JOIN');
                $query->andWhere([CargoCategory::tableName().'.id' => $catIds]);

                // Для родительской категории должны отображаться грузы из уникальных подкатегорий, что бы небыло дублей страниц
                if ($this->showMainCargoCategory && $category->root) {
                    // Хитрый трюк. Нам надо отобразить все грузы у которых главная категория - текущая родительская
                    // и по одному из дочерних категорий
                    // group by cargo_category_id оставлял из родительских грузов только один
                    // Для решения данной проблемы добавлено случайное число которое 0 - для грузов дочерних категорий и
                    // случайное для грузов из родительских категорий. Таким образом group by по двум полям не уберет грузы из родительских категорий
                    // В функцию rand() передается параметр. Он работает как seed инициализируясь только один раз
                    $query->addSelect(['t.*', 'IF('.CargoCategory::tableName().'.root,rand(now()),0) rnd'])
                        ->groupBy(['cargo_category_id', 'rnd']);
                }
            }
        } else {
            $query->joinWith('cargoCategory');
        }

        //die($query->createCommand()->rawSql);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageParam' => 'page',
                'page' => $this->page - 1,
                'defaultPageSize' => $this->pageSize,
                'pageSizeLimit' => false,
                'forcePageParam' => false,
            ],
        ]);
    }

    public function setShowMainCargoCategory(bool $showMainCargoCategory): self
    {
        $this->showMainCargoCategory = $showMainCargoCategory;

        return $this;
    }
}
