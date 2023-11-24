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
use common\models\CargoCategory;
use common\models\City;
use common\models\FastCity;
use common\models\IntercityTags;
use Svezem\Services\MatrixContentService\Essence\CargoEssence;
use Svezem\Services\MatrixContentService\Essence\TransportEssence;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\helpers\Url;


class IntercityController extends BaseController
{
    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }
    
    /**
     * Генерирует теги на странице поиска грузов
     */
    public function actionTagsGenerate()
    {
        $s = time();

        //получаем все города
        $fastCities = FastCity::find()->all();
        $rootCategories = CargoCategory::findAll(['slug' => ['pereezd', 'gruzoperevozki']]);

        $trans = Yii::$app->db->beginTransaction();
        IntercityTags::deleteAll();

        // Генерим теги, зависящие от городов
        $batchInsert = [];
        $tag = new IntercityTags();
        $batchAttributes = $tag->attributes();

        $count = count($fastCities);
        $iter = 0;
        foreach ($fastCities as $fastCity1) {
            $iter++;
            printf("\r%3d%%", $iter/$count*100);

            /** @var City $city1 */
            $city1 = $fastCity1->city;

            // Если контента недостаточно из города, нет смысла проверять дальше
            $categories = [];
            foreach($rootCategories as $rootCategory) {
                if (!$this->matrixContentService->isEnoughContent('intercity-view', $city1, null, $rootCategory))
                    continue;

                $categories[] = $rootCategory;
            }

            if(!$categories) continue;

            foreach ($fastCities as $fastCity2) {
                if ($fastCity1->id == $fastCity2->id) continue;

                /** @var City $city2 */
                $city2 = $fastCity2->city;

                foreach($categories as $rootCategory) {
                    if (!$this->matrixContentService->isEnoughContent('intercity-view', $city1, $city2, $rootCategory))
                        continue;

                    $tagTpl = TemplateHelper::findTemplate('intercity-view', $city1, $rootCategory);
                    if(!$tagTpl){
                        echo ' нет шаблона '."\n";
                        continue;
                    }

                    $tpl = TemplateHelper::fillTemplate($tagTpl, [
                        'category' => $rootCategory->category,
                        'category_rod' => $rootCategory->category_rod,
                        'city_from' => $city1->getTitle(),
                        'city_to' => $city2->getTitle(),
                        'city_from_region_ex' => $city1->getTitleWithRegionForTwig(),
                        'city_to_region_ex' => $city2->getTitleWithRegionForTwig()
                    ], ['tag_name']);

                    array_push($batchInsert, [
                        'id' => null,
                        'name' => $tpl->tag_name,
                        'city_from' => $city1->getId(),
                        'city_to' => $city2->getId(),
                        'category_id' => null,
                        'url' => 'https://' . Yii::getAlias('@domain') . Url::toRoute([
                                "intercity/default/transportation2",
                                "root" => $rootCategory,
                                "cityFrom" => $city1,
                                "cityTo" => $city2
                            ]),
                        'ads_count' => (
                            (int)$this->matrixContentService->getContent(new CargoEssence(), $city1, $city2, $rootCategory) +
                            (int)$this->matrixContentService->getContent(new TransportEssence(), $city1, $city2, $rootCategory)
                        )
                    ]);
                }

                if(count($batchInsert)>=1000) {
                    $sql = SqlHelper::buildBatchInsertQuery(IntercityTags::tableName(), $batchAttributes, $batchInsert, true);

                    Yii::$app->db
                        ->createCommand($sql)
                        ->execute();

                    $batchInsert = [];
                }
            }
        }

        if(count($batchInsert)) {
            $sql = SqlHelper::buildBatchInsertQuery(IntercityTags::tableName(), $batchAttributes, $batchInsert, true);
            Yii::$app->db
                ->createCommand($sql)
                ->execute();
        }

        $trans->commit();

        $total = time() - $s;
        $this->stdout("Время работы: $total сек \n");
    }
}
