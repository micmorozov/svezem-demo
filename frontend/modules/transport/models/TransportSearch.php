<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 05.10.17
 * Time: 12:33
 */

namespace frontend\modules\transport\models;

use common\models\CargoCategory;
use common\models\City;
use common\models\FastCity;
use common\models\LocationCategorySearch;
use common\models\Region;
use common\models\Transport;
use common\SphinxModels\SphinxTransport;
use common\SphinxModels\SphinxTransportCommon;
use common\SphinxModels\SphinxTransportRealTime;
use Yii;
use yii\caching\TagDependency;
use yii\data\ArrayDataProvider;
use yii\data\DataProviderInterface;
use yii\db\Expression;
use yii\db\Query;
use yii\sphinx\MatchExpression;
use yii\web\NotFoundHttpException;

class TransportSearch extends LocationCategorySearch
{
    const SORT_PATTERN_MAIN_WITHOUT_CATEGORY = 1;
    const SORT_PATTERN_MAIN_WITH_CATEGORY = 2;
    const SORT_PATTERN_SEARCH = 3;
    const SORT_PATTERN_RECOMMENDATION = 4;

    private $sortPatternType = self::SORT_PATTERN_MAIN_WITHOUT_CATEGORY;

    public function init()
    {
        parent::init();

        //Есть ограничение выдачи Sphinx. Если оно превышено, то выдаем 404 ошибку
        if($this->page > Yii::$app->params['sphinx_max_matches']/$this->pageSize){
            throw new NotFoundHttpException('Страница не найдена');
        }
    }

    /**
     * @param $models
     * @param $match
     */
    private function reWriteDescription(&$models, $match){
        //Сниппет нахождения текста указанного в MATCH
        $snippet = function($rows){
            $ids = array_map(function($row){
                return $row['id'];
            }, $rows);

            $models = (new Query)
                ->select('id, description')
                ->from(Transport::tableName())
                ->where(['id' => $ids])
                ->orderBy(new Expression('FIELD('.Transport::tableName().'.id, '.implode(',', $ids).')'))
                ->cache(3600, new TagDependency(['tags' => 'TransportSearch']))
                ->all();

            $result = array_map(function($model){
                return $model['description'];
            }, $models);

            return $result;
        };

        $snippetOptions = [
            "before_match" => '',
            'after_match' => '',
            'around' => 10
        ];

        $ids = array_map(function($a){
            return $a->id;
        }, $models);

        $commonRes = SphinxTransportCommon::find()
            ->match(new MatchExpression($match))
            ->where(['id' => $ids])
            ->snippetCallback($snippet)
            ->snippetOptions($snippetOptions)
            ->all();

        $realTimeRes = SphinxTransportRealTime::find()
            ->match(new MatchExpression($match))
            ->where(['id' => $ids])
            ->snippetCallback($snippet)
            ->snippetOptions($snippetOptions)
            ->all();

        $descriptions = [];
        foreach($commonRes as $model){
            $descriptions[$model->id] = $model->snippet;
        }

        foreach($realTimeRes as $model){
            $descriptions[$model->id] = $model->snippet;
        }

        foreach($models as $index => $model){
            if( !isset($descriptions[$model->id])) continue;

            $models[$index]->description_short = $descriptions[$model->id];
        }
    }

    /**
     * Поиск транспортных заявок.
     *
     * При выдаче приоритет отдается оплаченным заявкам.
     * Есть два вида услуг влияющих на поиск
     * 1.Разместить на главной - Размещение объявления ТОЛЬКО на главной странице города,
     * например на krasnoyarsk.svezem.ru. В поиске и на страницах перевозки по категориям оно не дает преимуществ.
     *
     * 2.Закрепить в ТОП - закрепление объявления в поиске по городу и категории, а так же на странице перевозки
     * по категориям и по направлению и категориям. На главной города он отображаться не будет(для этого есть отдельная услуга).
     * На странице поиска перевозчика будут отображаться объявления подходящие под фильтр с приоритетом платных.
     * Т.е. сначала будут отображаться платные объявления, подходящие под фильтр, потом бесплатные. Причем платные не будут
     * группироваться по пользователю, а бесплатные будут.
     *
     * @return array|void
     * @throws NotFoundHttpException
     */
    protected function prepare(): DataProviderInterface
    {
        $this->fillCategoryByFilter();

        //По шаблону определяем колонки платной услуги и сортировки
        switch($this->sortPatternType){
            case self::SORT_PATTERN_MAIN_WITH_CATEGORY:
                $payColumn = 'show_main_page';
                $paySortColumn = 'show_main_page_payed';
                break;
            case self::SORT_PATTERN_SEARCH:
                $payColumn = 'top';
                $paySortColumn = 'top_payed';
                break;
            case self::SORT_PATTERN_RECOMMENDATION:
                $payColumn = 'recommendation';
                $paySortColumn = 'recommendation_payed';
                break;
            default:
                $payColumn = 'show_main_page';
                $paySortColumn = 'show_main_page_payed';
        }

        //Результат поиска собирается из MySQL и Sphinx
        //Создаем запросы для каждой базы
        //и заполняем данными из фильтра
        $time = time();
        $payQuery = Transport::find()
            ->alias('t')
            ->with(['cityFrom', 'cityTo'])
            ->where(['status' => Transport::STATUS_ACTIVE])
            ->andWhere(['>', $payColumn, $time])
            ->groupBy('t.id')
            ->orderBy([$paySortColumn => SORT_DESC]);

        $restQuery = SphinxTransport::find()
            ->where(['status' => Transport::STATUS_ACTIVE])
            ->andWhere(['<', $payColumn, $time])
            ->groupBy('created_by');

        //Локация должна присутсвовать в отправки или доставки
        if($this->anyDirection && $this->locationFrom){
            if($this->locationFrom instanceof City){
                $payQuery->andWhere(['or',
                    ['city_from' => $this->locationFrom->getId()],
                    ['city_to' => $this->locationFrom->getId()]
                ]);
                $restQuery->select(["id", "IF(city_from = {$this->locationFrom->getId()} OR city_to = {$this->locationFrom->getId()}, 1, 0) AS satisfy"]);
            }

            if($this->locationFrom instanceof Region){
                $payQuery->andWhere(['or',
                    ['region_from' => $this->locationFrom->getId()],
                    ['region_to' => $this->locationFrom->getId()]
                ]);
                $restQuery->select(["id", "IF(region_from = {$this->locationFrom->getId()} OR region_to = {$this->locationFrom->getId()}, 1, 0) AS satisfy"]);
            }

            $restQuery->andWhere(['satisfy' => 1]);
        } else {
            //Указана точная локация отправки
            if($this->locationFrom){
                if($this->locationFrom instanceof City){
                    $payQuery->andWhere(['city_from' => $this->locationFrom->getId()]);
                    $restQuery->andWhere(['city_from' => $this->locationFrom->getId()]);
                }
                if($this->locationFrom instanceof Region){
                    $payQuery->andWhere(['region_from' => $this->locationFrom->getId()]);
                    $restQuery->andWhere(['region_from' => $this->locationFrom->getId()]);
                }
            }
            //Указана точная локация доставки
            if($this->locationTo){
                if($this->locationTo instanceof City){
                    $payQuery->andWhere(['city_to' => $this->locationTo->getId()]);
                    $restQuery->andWhere(['city_to' => $this->locationTo->getId()]);
                }
                if($this->locationTo instanceof Region){
                    $payQuery->andWhere(['region_to' => $this->locationTo->getId()]);
                    $restQuery->andWhere(['region_to' => $this->locationTo->getId()]);
                }

            }
            //если локация доставки не указана
            // и необходимо найти транспорт чтобы город доставки
            // отличался от города отправки
           /* elseif ( $this->locationFrom && $this->exceptLocationFrom){
                if($this->locationToType == self::LOCATION_TYPE_CITY){
                    $payQuery->andWhere(['<>', 'city_to', $this->locationFrom]);
                    $restQuery->andWhere(['<>', 'city_to', $this->locationFrom]);
                }
                if($this->locationToType == self::LOCATION_TYPE_REGION){
                    $payQuery->andWhere(['<>', 'region_to', $this->locationFrom]);
                    $restQuery->andWhere(['<>', 'region_to', $this->locationFrom]);
                }
            }*/

            if($this->diffDirection && (!$this->locationFrom || !$this->locationTo)){
                if($this->locationFrom){
                    if($this->locationTo instanceof City){
                        $payQuery->andWhere(['<>', 'city_to', $this->locationFrom->getId()]);
                        $restQuery->andWhere(['<>', 'city_to', $this->locationFrom->getId()]);
                    }
                    if($this->locationTo instanceof Region){
                        $payQuery->andWhere(['<>', 'region_to', $this->locationFrom->getId()]);
                        $restQuery->andWhere(['<>', 'region_to', $this->locationFrom->getId()]);
                    }
                }

                elseif($this->locationTo){
                    if($this->locationFrom instanceof City){
                        $payQuery->andWhere(['<>', 'city_from', $this->locationTo->getId()]);
                        $restQuery->andWhere(['<>', 'city_from', $this->locationTo->getId()]);
                    }
                    if($this->locationFrom instanceof Region){
                        $payQuery->andWhere(['<>', 'region_from', $this->locationTo->getId()]);
                        $restQuery->andWhere(['<>', 'region_from', $this->locationTo->getId()]);
                    }
                }
            }
        }

        if($this->cargoCategoryIds){
            //Категория у оплаченных транспортных заказов проверяется при поиске
            //и страницах использующих главный шаблон
            //и берется та что указана пользователем
            if(in_array($this->sortPatternType, [
                self::SORT_PATTERN_SEARCH,
                self::SORT_PATTERN_MAIN_WITH_CATEGORY,
                self::SORT_PATTERN_RECOMMENDATION
            ])){
                //Поиск категории
                //Если указана дочерняя категория, то ищим по ней и родительской
                //Если родительская, то по ней и её дочерним
//                $catIds = array_merge(
//                    CargoCategory::getNodeIdsByParentIds($this->cargoCategoryIds),
//                    CargoCategory::getParentIdsByChildIds($this->cargoCategoryIds),
//                    $this->cargoCategoryIds
//                );
//
//                $catIds = array_unique($catIds);

                $catIds = $this->cargoCategoryIds;

                $payQuery->joinWith(['fullCargoCategories fcc']);
                $payQuery->andWhere(['fcc.id' => $catIds]);
            }

            //Неоплаченные выдаются по релевантности текста описания
            $sphinxMatch = CargoCategory::createSphinxQuery($this->cargoCategoryIds);
            $restQuery->match(new MatchExpression($sphinxMatch));
        }else{
            // Если нет категорий, то сортируем по ИД в обратном опрядке
            // Такое происходит на странице /transport/search/
            $restQuery->orderBy(['id' => SORT_DESC]);
        }


        //die($payQuery->createCommand()->rawSql);

        //Определяем кол-во оплаченных услуг
        $payCount = $payQuery->count();

        //Костыль для получения неоплаченных. Ошибка связана с group_by
        $restCountQuery = clone $restQuery;
        $restCountQuery->groupBy = null;
        $restCountQuery->snippetCallback = null;
        $restCountQuery->select[] = 'COUNT(DISTINCT created_by) count';
        $countModel = $restCountQuery->one();
        $restCount = isset($countModel) ? $countModel->count : 0;

        //общее количество записей
        $totalCount = $payCount + $restCount;

        //Теперь чтобы получить данные из двух множеств, необходимо расчитать
        //offset и limit для каждого из множеств

        //первый и последний индексы текущей страницы
        $firstIndex = $this->pageSize*($this->page - 1);
        $lastIndex = $this->pageSize*$this->page - 1;

        $payLimit = 0;
        //на данной странице есть результаты из ТОПа
        if($firstIndex < $payCount){
            $payOffset = $firstIndex;
            $payLimit = $payCount - $payOffset;

            if($payLimit > $this->pageSize)
                $payLimit = $this->pageSize;
        }

        //если на данной странице есть результаты (неоплаченные)
        if($lastIndex > $payCount - 1){
            $restOffset = $firstIndex - $payCount;
            $restLimit = $this->pageSize - $payLimit;

            if($restOffset < 0)
                $restOffset = 0;
        }

        $payResult = [];
        if(isset($payOffset)){
            $payResult = $payQuery
                ->offset($payOffset)
                ->limit($payLimit)
                ->all();
        }

        $restResult = [];
        if(isset($restOffset)){
            $restResult = $restQuery
                ->offset($restOffset)
                ->limit($restLimit)
                ->all();
        }

        $payIds = array_map(function($a){
            return $a->id;
        }, $payResult);

        $restIds = array_map(function($a){
            return $a->id;
        }, $restResult);

        $resultIds = array_merge($payIds, $restIds);

        //Получаем транспорт из БД по ИД полученным из Sphinx
        $transportsQuery = Transport::find()
            ->with(['cityFrom.country', 'cityTo.country', 'fullCargoCategories'])
            ->joinWith('profile.city')
            ->where([Transport::tableName().'.id' => $resultIds]);

        if($resultIds){
            $transportsQuery->orderBy(new Expression('FIELD('.Transport::tableName().'.id, '.implode(',', $resultIds).')'));
        }

        $transports = $transportsQuery->all();

        //Если есть категории, то подставляем в description_short
        // ключевые фразы
        if( isset($sphinxMatch) ){
            $this->reWriteDescription($transports, $sphinxMatch);
        }

        //Если при пейджировании нет контента на странице
        if( $this->page > 1 && count($transports) == 0 ){
            throw new NotFoundHttpException('Страница не найдена');
        }

        return new ArrayDataProvider([
            'totalCount' => $totalCount,
            'models' => $transports,
            'pagination' => [
                'pageParam' => 'page',
                'defaultPageSize' => $this->pageSize,
                'pageSizeLimit' => false,
                'forcePageParam' => false,
                'totalCount' => $totalCount < Yii::$app->params['sphinx_max_matches'] ? $totalCount : Yii::$app->params['sphinx_max_matches']
            ]
        ]);
    }

    public function setSortPatternType(int $sortPatternType): self
    {
        $this->sortPatternType = $sortPatternType;

        return $this;
    }
}
