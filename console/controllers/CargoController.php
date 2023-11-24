<?php
/**
 * Created by PhpStorm.
 * User: ������� ������
 * Date: 14.07.2016
 * Time: 13:54
 */

namespace console\controllers;

use common\helpers\SqlHelper;
use common\helpers\TemplateHelper;
use common\helpers\Utils;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CargoCategoryTags;
use common\models\CargoSearchTags;
use common\models\FastCity;
use Redis;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\helpers\Url;

class CargoController extends BaseController
{
    //блокировка на 30 мин
    protected $actionTTL = 60*30;

    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    /**
     * Обновляем теги на странице грузов. Если передан $cargoid то только у этого груза, иначе у всех
     * @param null $cargoid ИД груза
     *
     * Теги на страницы груза генерятся по другому алгоритму, описанному в init
     */
    /*public function actionTagsUpdate($cargoid = null)
    {
        if (is_null($cargoid)) {
            $query = Cargo::find()
                ->where(['<>', 'status', Cargo::STATUS_BANNED])
                ->limit(500);

            while ($cargos = $query->all()) {
                $query->offset += $query->limit;

                foreach ($cargos as $cargo) {
                    Yii::$app->gearman->getDispatcher()->background("UpdateCargoTags", [
                        'cargo_id' => $cargo->id
                    ]);
                }
            }
        } else {
            Yii::$app->gearman->getDispatcher()->background("UpdateCargoTags", [
                'cargo_id' => (int)$cargoid
            ]);
        }
    }*/

    /**
     * Генерирует теги на странице поиска грузов
     */
    public function actionSearchTagsGenerate()
    {
        //получаем все города
        $fastCities = FastCity::find()->all();
        $categories = CargoCategory::find()->all();

        //поиск груза по стране
        $tpl = TemplateHelper::findTemplate('cargo-search-inside-country-view');
        if($tpl && $this->matrixContentService->isEnoughContent('cargo-search-inside-country-view')) {
            $tpl = TemplateHelper::fillTemplate($tpl, [], ['tag_name']);
            $tag = new CargoSearchTags();
            $tag->name = $tpl->tag_name;
            if( !$tag->save() ){
                Yii::error("Не удалось создать тег ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'CargoTagsController.SearchTagsGenerate');
            }
        }

        foreach ($categories as $cat) {
            //поиск груза по стране с категориями
            $tpl = TemplateHelper::findTemplate('cargo-search-inside-country-view',null, $cat, false);
            if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-inside-country-view', null, null, $cat)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'category_rod' => $cat->category_rod
                ], ['tag_name']);

                $tag = new CargoSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->category_id = $cat->id;
                if ( !$tag->save()) {
                    Yii::error("Не удалось создать тег(1) ".print_r($tag->attributes, 1)."\n".print_r($tag->getErrors(),
                            1), 'CargoTagsController.SearchTagsGenerate');
                }
            }
        }

        // Генерим теги, зависящие от городов
        $count = count($fastCities);
        $iter = 0;
        foreach ($fastCities as $fastCity) {
            $iter++;
            printf("\r%3d%%", $iter/$count*100);
            $city = $fastCity->city;

            //поиск грузов из города
            $tpl = TemplateHelper::findTemplate('cargo-search-from-city-view', $city);
            if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-from-city-view', $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city_from' => $city->getTitle(),
                ], ['tag_name']);

                $tag = new CargoSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_from = $city->getId();
                $tag->domain_id = $city->getId();
                if ( !$tag->save()) {
                    Yii::error("Не удалось создать тег(2) ".print_r($tag->attributes, 1)."\n".print_r($tag->getErrors(),
                            1), 'CargoTagsController.SearchTagsGenerate');
                }
            }

            //поиск грузов в город
            // https://ru.yougile.com/board/u5qm752atccf#chat:9bcfe894f38b
           /* $tpl = TemplateHelper::findTemplate('cargo-search-to-city-view', $city);
            if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-to-city-view', null, $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city_to' => $city->getTitle(),
                ], ['tag_name']);

                $tag = new CargoSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_to = $city->getId();
                $tag->domain_id = $city->getId();
                if ( !$tag->save()) {
                    Yii::error("Не удалось создать тег(3) ".print_r($tag->attributes, 1)."\n".print_r($tag->getErrors(),
                            1), 'CargoTagsController.SearchTagsGenerate');
                }
            }*/

            //поиск грузов по России в город
            $tpl = TemplateHelper::findTemplate('cargo-search-to-city-view');
            if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-to-city-view', null, $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city_to' => $city->getTitle()
                ], ['tag_name']);

                $tag = new CargoSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_to = $city->getId();
                if ( !$tag->save()) {
                    Yii::error("Не удалось создать тег(4) ".print_r($tag->attributes, 1)."\n".print_r($tag->getErrors(),
                            1), 'CargoTagsController.SearchTagsGenerate');
                }
            }

            $tpl = TemplateHelper::findTemplate('cargo-search-from-to-city-view', $city);
            if ($tpl) {
                foreach ($fastCities as $fastCity2) {
                    if ($fastCity->id == $fastCity2->id) {
                        continue;
                    }

                    $city2 = $fastCity2->city;

                    //поиск грузов из города в город
                    if ($this->matrixContentService->isEnoughContent('cargo-search-from-to-city-view', $city, $city2)) {
                        $_tpl = TemplateHelper::fillTemplate($tpl, [
                            'city_from' => $city->getTitle(),
                            'city_to' => $city2->getTitle(),
                        ], ['tag_name']);

                        $tag = new CargoSearchTags();
                        $tag->name = $_tpl->tag_name;
                        $tag->city_from = $city->getId();
                        $tag->city_to = $city2->getId();
                        $tag->domain_id = $city->getId();
                        if ( !$tag->save()) {
                            Yii::error("Не удалось создать тег(5) ".print_r($tag->attributes,
                                    1)."\n".print_r($tag->getErrors(), 1), 'CargoTagsController.SearchTagsGenerate');
                        }
                    }
                }
            }

            //по городам и категориям
            foreach ($categories as $cat) {
                //поиск грузов из города
                $tpl = TemplateHelper::findTemplate('cargo-search-from-city-view', $city, $cat, false);
                if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-from-city-view', $city, null, $cat)) {
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city_from' => $city->getTitle(),
                        'category_rod' => $cat->category_rod
                    ], ['tag_name']);

                    $tag = new CargoSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_from = $city->getId();
                    $tag->category_id = $cat->id;
                    $tag->domain_id = $city->getId();
                    if ( !$tag->save()) {
                        Yii::error("Не удалось создать тег(6) ".print_r($tag->attributes,
                                1)."\n".print_r($tag->getErrors(), 1), 'CargoTagsController.SearchTagsGenerate');
                    }
                }

                //поиск грузов в город
                $tpl = TemplateHelper::findTemplate('cargo-search-to-city-view', $city, $cat, false);
                if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-to-city-view', null, $city, $cat)) {
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city_to' => $city->getTitle(),
                        'category_rod' => $cat->category_rod
                    ], ['tag_name']);

                    $tag = new CargoSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_to = $city->getId();
                    $tag->category_id = $cat->id;
                    $tag->domain_id = $city->getId();
                    if ( !$tag->save()) {
                        Yii::error("Не удалось создать тег(7) ".print_r($tag->attributes,
                                1)."\n".print_r($tag->getErrors(), 1), 'CargoTagsController.SearchTagsGenerate');
                    }
                }

                //поиск грузов в город для главного домена
                $tpl = TemplateHelper::findTemplate('cargo-search-to-city-view',  null, $cat, false);
                if ($tpl && $this->matrixContentService->isEnoughContent('cargo-search-to-city-view', null, $city, $cat)) {
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city_to' => $city->getTitle(),
                        'category_rod' => $cat->category_rod
                    ], ['tag_name']);

                    $tag = new CargoSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_to = $city->getId();
                    $tag->category_id = $cat->id;
                    if ( !$tag->save()) {
                        Yii::error("Не удалось создать тег(8) ".print_r($tag->attributes,
                                1)."\n".print_r($tag->getErrors(), 1), 'CargoTagsController.SearchTagsGenerate');
                    }
                }

                $tpl = TemplateHelper::findTemplate('cargo-search-from-to-city-view', $city, $cat, false);
                if ($tpl) {
                    //поиск грузов из города в город
                    foreach ($fastCities as $fastCity2) {
                        if ($fastCity->id == $fastCity2->id) {
                            continue;
                        }

                        $city2 = $fastCity2->city;

                        if ($this->matrixContentService->isEnoughContent('cargo-search-from-to-city-view', $city, $city2, $cat)) {
                            $_tpl = TemplateHelper::fillTemplate($tpl, [
                                'city_from' => $city->getTitle(),
                                'city_to' => $city2->getTitle(),
                                'category_rod' => $cat->category_rod
                            ], ['tag_name']);

                            $tag = new CargoSearchTags();
                            $tag->name = $_tpl->tag_name;
                            $tag->city_from = $city->getId();
                            $tag->city_to = $city2->getId();
                            $tag->category_id = $cat->id;
                            $tag->domain_id = $city->getId();
                            if ( !$tag->save()) {
                                Yii::error("Не удалось создать тег(9) ".print_r($tag->attributes,
                                        1)."\n".print_r($tag->getErrors(), 1),
                                    'CargoTagsController.SearchTagsGenerate');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Строим теги для видов перевозки
     */
    public function actionCategoryTags()
    {
        $categories = CargoCategory::findAll(['create_tag' => 1]);
        $fastCities = FastCity::find()->all();

        // Генерим теги, зависящие от городов
        $batchInsert = [];
        $tag = new CargoCategoryTags();
        $batchAttributes = $tag->attributes();

        foreach ($categories as $cat) {
            $tagTpl = TemplateHelper::findTemplate('cargo-transportation-view',null, $cat);

            $tpl = TemplateHelper::fillTemplate($tagTpl, [
                'category' => $cat->category,
                'category_rod' => $cat->category_rod,
                'city' => ''
            ], ['tag_name']);

            array_push($batchInsert, [
                'id' => null,
                'name' => $tpl->tag_name,
                'city_id' => null,
                'region_id' => null,
                'category_id' => $cat->id,
                'url' => 'https://' . Yii::getAlias('@domain') . Url::toRoute(["cargo/transportation/search2", 'slug' => $cat]),
            ]);

            foreach($fastCities as $fastCity) {
                $city = $fastCity->city;

                // Если категория должна быть в этом городе значит ссылку добавляем
                // Иначе надо проверить наличие контента. Если его мало - ссылку не создаем
                if(!Utils::check_mask($city->size, $cat->city_size_mask)){
                    $isEnough = $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $city, $cat);
                    if (!$isEnough) continue;
                }

                $tagTpl = TemplateHelper::findTemplate('cargo-transportation-view', $city, $cat);

                $tpl = TemplateHelper::fillTemplate($tagTpl, [
                    'category' => $cat->category,
                    'category_rod' => $cat->category_rod,
                    'city' => $city->getTitle()
                ], ['tag_name']);

                array_push($batchInsert, [
                    'id' => null,
                    'name' => $tpl->tag_name,
                    'city_id' => $city->getId(),
                    'region_id' => $city->region_id,
                    'category_id' => $cat->id,
                    'url' => 'https://' . Yii::getAlias('@domain') . Url::toRoute(["cargo/transportation/search2", 'slug' => $cat, 'location' => $city]),
                ]);

                if(count($batchInsert)>=1000) {
                    $sql = SqlHelper::buildBatchInsertQuery(CargoCategoryTags::tableName(), $batchAttributes, $batchInsert, true);
                    Yii::$app->db
                        ->createCommand($sql)
                        ->execute();

                    $batchInsert = [];
                }
            }
        }

        if(count($batchInsert)) {
            $sql = SqlHelper::buildBatchInsertQuery(CargoCategoryTags::tableName(), $batchAttributes, $batchInsert, true);
            Yii::$app->db
                ->createCommand($sql)
                ->execute();
        }
    }

    /**
     * Построение отметок грузов на карте
     */
    public function actionMap()
    {
        $query = Cargo::find()
            ->joinWith(['cityFrom cf', 'cityTo ct'])
            ->where(['status' => Cargo::STATUS_ACTIVE])
            ->isExpired(false);

        $result = '';

        /** @var Cargo $cargo */
        foreach (Utils::arModelsGenerator($query) as $cargo) {
            if (!$cargo->cityFrom->latitude) {
                continue;
            }

            $result .= $cargo->id . "|" . $cargo->cityFrom->latitude . "|" . $cargo->cityFrom->longitude . ";";
        }

        //Удаляем последний символ ;
        $result = rtrim($result, ";");

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;
        //на 4 часа
        $redis->set('cargoMap', $result, 60 * 60 * 4);
    }

    /**
     * Актуализаиця грузов для построения маршрутов
     */
    public function actionRoute()
    {
        $query = Cargo::find()->limit(500);

        while ($cargos = $query->all()) {
            $query->offset += $query->limit;

            foreach ($cargos as $cargo) {
                Yii::$app->gearman->getDispatcher()->background("CargoRoute", [
                    'cargo_id' => $cargo->id
                ]);
            }
        }
    }
}
