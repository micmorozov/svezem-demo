<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 19.01.18
 * Time: 16:41
 */

namespace console\controllers;

use common\models\Cargo;
use common\models\CargoCategory;
use common\models\City;
use common\SphinxModels\SphinxTransport;
use frontend\modules\tk\models\Tk;
use Svezem\Services\MatrixContentService\Essence\CargoEssence;
use Svezem\Services\MatrixContentService\Essence\TkEssence;
use Svezem\Services\MatrixContentService\Essence\TransportEssence;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\base\Exception;
use yii\caching\TagDependency;
use yii\console\Controller;
use yii\sphinx\MatchExpression;
use yii\sphinx\Query;

class MatrixContentController extends Controller
{
    const MAIN_CATEGORY_SLUG = 'glavnaya';

    /** @var MatrixContentService */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    public function afterAction($action, $result){
        if ($action->id == 'query') return;

        //По тегу делаем кэш неактуальным
        TagDependency::invalidate(Yii::$app->cache, 'matrixContent');
        $this->stdout("Кэш 'matrixContent' сброшен\n");

        return parent::afterAction($action, $result);
    }

    public function actionBuild(){
        ini_set('memory_limit', '512M');

        //////// ГРУЗЫ ///////////////
        $this->actionCargoBuild();

        //////// ТРАНСПОРТ ///////////
        $this->actionTransportBuild();

        //////// ТК ///////////
       $this->actionTkBuild();
    }

    public function actionCargoBuild()
    {
        $s = time();
        $this->stdout("Построение контента 'Грузов'.\n");

        $this->matrixContentService->beginTransaction();
        //удаляем старые значения
        $this->matrixContentService->clearAll(new CargoEssence());

        ////////////////////////////////////////
        /// Строим матрицу по городам
        ///////////////////////////////////////
        $query = (new \yii\db\Query())
            ->from(Cargo::tableName())
            ->select(['city_from', 'city_to', 'count' => 'count(*)'])
            ->where(['status' => [Cargo::STATUS_ACTIVE, Cargo::STATUS_WORKING, Cargo::STATUS_DONE]])
            ->groupBy(['city_from', 'city_to']);

        /** @var Cargo $cargo */
        foreach($query->each() as $cargo){
            $cityFrom = City::findOne($cargo['city_from']);
            $cityTo = City::findOne($cargo['city_to']);
            $count = $cargo['count'];

            //---------------------------
            //из города, региона, страны
            self::complexIncrValue(new CargoEssence(), $cityFrom, null, null, $count);

            //в город, регион, страну
            self::complexIncrValue(new CargoEssence(), null, $cityTo, null, $count);

            //из города, региона, страны в город, регион, странц
            self::complexIncrValue(new CargoEssence(), $cityFrom, $cityTo, null, $count);
            //------------------------------
        }

        ///////////////////////////////////////

        ///////////////////////////////////////
        /// Строим матрицу по городам + категориям.
        /// ВНИМАНИЕ!! Получить из этой выборки только города(без категорий) нельзя, так как данные будут не верные
        /// inner join позволяет на уровне БД удалить строки с пустым category_id, например грузы, у которых не установлена категория
        ///////////////////////////////////////
        // select city_from, city_to, cat_id, count(distinct cargo_id) cnt from (
        // (select city_from, city_to, assn.category_id cat_id, cargo_id
        //  from cargo
        //  inner join cargo_category_assn assn on assn.cargo_id = cargo.id
        //  where status = 0
        // )
        //
        //union
        // (select city_from, city_to, tree_assn.parent cat_id, cargo_id
        //  from cargo
        //  inner join cargo_category_assn assn on assn.cargo_id = cargo.id
        //  inner join category_tree_assn tree_assn on tree_assn.category_id=assn.category_id
        //  where status = 0
        // )
        //) `dummy`
        //group by  city_from, city_to, cat_id
        //

        // Получаем категории у которых есть грузы
        $query1 = (new \yii\db\Query())
            ->from(Cargo::tableName())
            ->select(['city_from', 'city_to', 'cat_id' => 'cargo_category_id', 'cargo_id' => 'cargo.id'])
            //->innerJoin('cargo_category_assn assn', 'cargo.id = assn.cargo_id')
            ->where(['status' => [Cargo::STATUS_ACTIVE, Cargo::STATUS_WORKING, Cargo::STATUS_DONE]])
            ->andWhere(['not', ['cargo_category_id'=>null]]);
            //->groupBy(['city_from', 'city_to', 'assn.category_id']);

        // Получаем родительские категории для тех категорий у которых есть грузы
        $query2 = (new \yii\db\Query())
            ->from(Cargo::tableName())
            ->select(['city_from', 'city_to', 'cat_id' => 'tree_assn.parent', 'cargo_id' => 'cargo.id'])
            ->innerJoin('cargo_category_assn assn', 'cargo.id = assn.cargo_id')
            ->innerJoin('category_tree_assn tree_assn', 'tree_assn.category_id = assn.category_id')
            ->where(['status' => [Cargo::STATUS_ACTIVE, Cargo::STATUS_WORKING, Cargo::STATUS_DONE]])
            // Такая группировка, что бы соответствовать запросу грузов для родительских категорий
            // на страницах /cargo/transportation/ /intercity/<city>/<category>
            ->groupBy(['city_from', 'city_to', 'tree_assn.parent', 'cargo.cargo_category_id']);

        // Объеденяем две выборки
        $unionQuery = (new \yii\db\Query())
            ->from(['query' => $query1->union($query2, true)])
            ->select(['city_from', 'city_to', 'cat_id', 'count' => 'count(distinct cargo_id)'])
            ->groupBy(['city_from', 'city_to', 'cat_id']);

        //die($unionQuery->createCommand()->rawSql);

        /** @var Cargo $cargo */
        foreach($unionQuery->each() as $cargo){
            $cityFrom = City::findOne($cargo['city_from']);
            $cityTo = City::findOne($cargo['city_to']);
            $cat = CargoCategory::findOne($cargo['cat_id']);
            $count = $cargo['count'];

            //из города, региона, страны
            self::complexIncrValue(new CargoEssence(), $cityFrom, null, $cat, $count);

            //в город, регион
            self::complexIncrValue(new CargoEssence(), null, $cityTo, $cat, $count);

            //из города в город
            self::complexIncrValue(new CargoEssence(), $cityFrom, $cityTo, $cat, $count);

            // Грузы по категориям без направлений
            self::complexIncrValue(new CargoEssence(), null, null, $cat, $count);
            //-----------------------
        }

        $this->matrixContentService->commitTransaction();

        $total = time() - $s;
        $this->stdout("Построение контента 'Грузов'. Время работы: $total сек \n");
    }

    public function actionTransportBuild(){
        $s = time();
        $this->stdout("Построение контента 'Транспорта'.\n");

        $this->matrixContentService->beginTransaction();
        $this->matrixContentService->clearAll(new TransportEssence());

        // Получаем все категории, что бы позже по ним запрос к сфинксу делать и получать количество транспорта по категории и направлению
        $categories = CargoCategory::find()
            ->where(['<>', 'keywords', ''])
            ->all();

        //По России
        //при выдачи если не указаны категории, то выбирается по умолчанию
        //поэтому при формировании тоже ищим по этой категории

        $mainCategory = CargoCategory::findOne(['slug' => self::MAIN_CATEGORY_SLUG]);

        //По России с категориями
        //from0 to0 from0_to0
        //from0_cat to0_cat from0_to0_cat

        foreach($categories as $cat){
            $count = SphinxTransport::find()
                ->match(new MatchExpression($cat->keywords))
                ->count('DISTINCT created_by');

            if($count){
                self::complexIncrValue(new TransportEssence(), null, null, $cat, $count);
            }

            /*$price = SphinxTransport::find()
                ->select(['min_price' => 'min(price_from)'])
                ->match(new MatchExpression($cat->keywords))
                ->one();
            if($price){
                $price_from = $price['min_price'];

                MatrixContentService::incrValue(
                    MatrixContentService::PRICE_TRANSPORT_FROM,
                    0,
                    null,
                    $cat,
                    $price_from
                );

                MatrixContentService::incrValue(
                    MatrixContentService::PRICE_TRANSPORT_FROM,
                    null,
                    0,
                    $cat,
                    $price_from
                );

                MatrixContentService::incrValue(
                    MatrixContentService::PRICE_TRANSPORT_FROM,
                    0,
                    0,
                    $cat,
                    $price_from
                );

                if($cat->id == $mainCategory->id){
                    MatrixContentService::incrValue(
                        MatrixContentService::PRICE_TRANSPORT_FROM,
                        0,
                        null,
                        null,
                        $price_from
                    );

                    MatrixContentService::incrValue(
                        MatrixContentService::PRICE_TRANSPORT_FROM,
                        null,
                        0,
                        null,
                        $price_from
                    );

                    MatrixContentService::incrValue(
                        MatrixContentService::PRICE_TRANSPORT_FROM,
                        0,
                        0,
                        null,
                        $price_from
                    );
                }
            }*/
        }

        $query = SphinxTransport::find()
            ->select(['city_from', 'city_to', 'count' => 'count(DISTINCT created_by)'/*, 'min_price' => 'min(price_from)'*/])
            ->groupBy(['city_from', 'city_to'])
            ->addOptions(['max_matches' => 999999])
            ->limit(500);

        /** @var SphinxTransport[] $transports */
        while($transports = $query->all()) {
            $query->offset += $query->limit;

            foreach ($transports as $transport) {
                $cityFrom = City::findOne($transport->city_from);
                $cityTo = City::findOne($transport->city_to);

                self::complexIncrValue(new TransportEssence(), $cityFrom, null, null, $transport->count);
                self::complexIncrValue(new TransportEssence(), null, $cityTo, null, $transport->count);
                self::complexIncrValue(new TransportEssence(), $cityFrom, $cityTo, null, $transport->count);
            }
        }

        $priceCityFrom = $priceCityTo = [];
        foreach($categories as $cat){
            $query = SphinxTransport::find()
                ->select(['city_from', 'city_to', 'count' => 'count(DISTINCT created_by)'/*, 'min_price' => 'min(price_from)'*/])
                ->match(new MatchExpression($cat->keywords))
                ->groupBy(['city_from', 'city_to'])
                ->options(['max_matches' => 999999])
                ->limit(500);

            $priceCityFromCat = $priceCityToCat = [];
            /** @var SphinxTransport[] $transports */
            while($transports = $query->all()) {
                $query->offset += $query->limit;

                foreach ($transports as $transport) {
                    $cityFrom = City::findOne($transport->city_from);
                    $cityTo = City::findOne($transport->city_to);

                    /*  if(!isset($priceCityFromCat[$transport['city_from']])) $priceCityFromCat[$transport['city_from']] = 0;
                      if($priceCityFromCat[$transport['city_from']] > $transport['min_price']) $priceCityFromCat[$transport['city_from']] = $transport['min_price'];

                      if(!isset($priceCityToCat[$transport['city_to']])) $priceCityToCat[$transport['city_to']] = 0;
                      if($priceCityToCat[$transport['city_to']] > $transport['min_price']) $priceCityToCat[$transport['city_to']] = $transport['min_price'];
      */
                    self::complexIncrValue(new TransportEssence(), $cityFrom, null, $cat, $transport->count);
                    self::complexIncrValue(new TransportEssence(), null, $cityTo, $cat, $transport->count);
                    self::complexIncrValue(new TransportEssence(), $cityFrom, $cityTo, $cat, $transport->count);

                    // Цены
                    /* MatrixContentService::incrValue(
                         MatrixContentService::PRICE_TRANSPORT_FROM,
                         $cityFrom,
                         $cityTo,
                         $cat,
                         $transport['min_price']
                     );*/
                }
            }


            // Цены
           /* foreach($priceCityFromCat as $city_from => $minPriceCityFrom) {
                $cityFrom = City::findOne($city_from);

                if(!isset($priceCityFrom[$city_from])) $priceCityFrom[$city_from] = 0;
                if($priceCityFrom[$city_from] > $minPriceCityFrom) $priceCityFrom[$city_from] = $minPriceCityFrom;

                MatrixContentService::incrValue(
                    MatrixContentService::PRICE_TRANSPORT_FROM,
                    $cityFrom,
                    null,
                    $cat,
                    $minPriceCityFrom
                );
            }

            foreach($priceCityToCat as $city_to => $minPriceCityTo) {
                $cityTo = City::findOne($city_to);

                if(!isset($priceCityTo[$city_to])) $priceCityTo[$city_to] = 0;
                if($priceCityTo[$city_to] > $minPriceCityTo) $priceCityTo[$city_to] = $minPriceCityTo;

                MatrixContentService::incrValue(
                    MatrixContentService::PRICE_TRANSPORT_FROM,
                    null,
                    $cityTo,
                    $cat,
                    $minPriceCityTo
                );
            }*/
        }

        /*foreach($priceCityFrom as $city_from => $minPriceCityFrom){
            $cityFrom = City::findOne($city_from);

            MatrixContentService::incrValue(
                MatrixContentService::PRICE_TRANSPORT_FROM,
                $cityFrom,
                null,
                null,
                $minPriceCityFrom
            );
        }

        foreach($priceCityToCat as $city_to => $minPriceCityTo){
            $cityTo = City::findOne($city_to);

            MatrixContentService::incrValue(
                MatrixContentService::PRICE_TRANSPORT_FROM,
                null,
                $cityTo,
                null,
                $minPriceCityTo
            );
        }*/

        $this->matrixContentService->commitTransaction();

        $total = time() - $s;
        $this->stdout("Построение контента 'Транспорта'. Время работы: $total сек \n");
    }

    public function actionTkBuild()
    {
        $s = time();
        $this->stdout("Построение контента 'ТК'\n");

        $this->matrixContentService->beginTransaction();
        $this->matrixContentService->clearAll(new TkEssence());

        //при выдачи если не указаны категории, то выбирается по умолчанию
        //поэтому при формировании тоже ищим по этой категории

        $mainCategory = CargoCategory::findOne(['slug' => self::MAIN_CATEGORY_SLUG]);

        $categories = CargoCategory::find()
            ->where(['<>', 'keywords', ''])
            ->all();

        $query = Tk::find()
            ->where(['status' => Tk::STATUS_ACTIVE])
            ->with(['details'])
            ->orderBy('id')
            ->limit(500);

        /** @var Tk[] $tks */
        while($tks = $query->all()) {
            $query->offset += $query->limit;

            foreach ($tks as $tk) {
                $cats = [];
                foreach ($categories as $category) {
                    $count = (new Query())
                        ->from('svezem_tk')
                        ->match(new MatchExpression("@describe {$category->keywords}"))
                        ->andWhere(['id' => $tk->id])
                        ->count();

                    if ($count) {
                        $cats[] = $category;

                        self::complexIncrValue(new TkEssence(), null, null, $category, $count);
                    }
                }

                $details = $tk->details;

                $details_count = count($details);
                for ($i = 0; $i < $details_count; $i++) {
                    $cityFrom = $details[$i]->city;

                    if (!empty($cats)) {
                        /** @var CargoCategory $cat */
                        foreach ($cats as $cat) {
                            self::complexIncrValue(new TkEssence(), $cityFrom, null, $cat, 1);

                            if ($cat->id == $mainCategory->id) {
                                self::complexIncrValue(new TkEssence(), $cityFrom, null, null, 1);
                            }
                        }
                    }

                    for ($j = $i + 1; $j < $details_count; $j++) {
                        $cityTo = $details[$j]->city;

                        foreach ($cats as $cat) {
                            self::complexIncrValue(new TkEssence(), $cityFrom, $cityTo, $cat, 1);
                            self::complexIncrValue(new TkEssence(), $cityTo, $cityFrom, $cat, 1);

                            if ($cat->id == $mainCategory->id) {
                                self::complexIncrValue(new TkEssence(), $cityFrom, $cityTo, null, 1);
                                self::complexIncrValue(new TkEssence(), $cityTo, $cityFrom, null, 1);
                            }
                        }
                    }
                }
            }
        }

        $this->matrixContentService->commitTransaction();

        $total = time() - $s;
        $this->stdout("Построение контента 'ТК' Время работы: $total сек \n");
    }

    /**
     * Запросы к матрице контента
     *
     * @param $essence
     * @param null $city_from
     * @param null $city_to
     */
    public function actionQuery($essence, $city_from=null, $city_to=null, $cat=null)
    {
        if($city_from) $city_from = City::findOne($city_from);
        if($city_to) $city_to = City::findOne($city_to);
        if($cat) $cat = CargoCategory::findOne($cat);

        switch ($essence){
            case 'cargo':
                $essence = new CargoEssence();
                break;

            case 'transport':
                $essence = new TransportEssence();
                break;

            case 'tk':
                $essence = new TkEssence();
                break;

            default:
                $essence = null;

        }

        echo $this->matrixContentService->getContent($essence, $city_from, $city_to, $cat);
    }

    /**
     * Увеличиваем количество контента в городе, регионе и стране
     * @param $essense
     * @param City|null $cityFrom
     * @param City|null $cityTo
     * @param CargoCategory|null $cat
     * @param int $count
     * @throws Exception
     */
    private function complexIncrValue($essense,
        City $cityFrom = null,
        City $cityTo = null,
        CargoCategory $cat = null,
        int $count = 1)
    {
        $this->matrixContentService->incrValue(
            $essense,
            $cityFrom,
            $cityTo,
            $cat,
            $count
        );

        $regionFrom = $countryFrom = null;
        if($cityFrom instanceof City){
            $regionFrom = $cityFrom->region;
            $countryFrom = $cityFrom->country;
        }
        $regionTo = $countryTo = null;
        if($cityTo instanceof City){
            $regionTo = $cityTo->region;
            $countryTo = $cityTo->country;
        }

        if($regionFrom || $regionTo) {
            $this->matrixContentService->incrValue(
                $essense,
                $regionFrom,
                $regionTo,
                $cat,
                $count
            );
        }

        if($countryFrom || $countryTo) {
            $this->matrixContentService->incrValue(
                $essense,
                $countryFrom,
                $countryTo,
                $cat,
                $count
            );
        }
    }
}