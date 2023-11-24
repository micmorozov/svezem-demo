<?php
/**
 * ВАЖНО!! Пока не используется
 * Created by PhpStorm.
 * User: ferrum
 * Date: 24.11.17
 * Time: 14:47
 */

namespace console\controllers;

use common\helpers\TemplateHelper;
use common\models\CargoCategory;
use common\models\PageTemplates;
use common\models\PageTemplateType;
use Yii;
use common\models\FastCity;
use common\models\TkSearchTags;
use yii\console\Controller;

class TkController extends Controller
{
    /**
     * Генерирует теги поиска транспортных компаний
     */
    public function actionSearchTagsGenerate(){
        //удаляем все теги
        Yii::$app->db->createCommand()->truncateTable(TkSearchTags::tableName())->execute();

        //получаем все города
        $fastCities = FastCity::find()->all();

        /** @var CargoCategory[] $categories */
        $categories = CargoCategory::find()->all();

        foreach($fastCities as $fastCity){
            $city = $fastCity->city;

            //поиск груза по городу
            $tpl = TemplateHelper::findTemplate('tk-search-category-view', $city);
            if($tpl) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city' => $city->getTitle()
                ], ['tag_name']);

                $tag = new TkSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_from = $city->getId();
                $tag->city_to = $city->getId();
                $tag->domain_id = $city->getId();
                if( !$tag->save() ){
                    Yii::error("Не удалось создать тег ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TkTagsController.SearchTagsGenerate');
                }
            }

            //по городам и категориям
            foreach($categories as $cat){

                //поиск груза по городу
                $tpl = PageTemplates::findOne([
                    'type' => 'tk-search-category-view',
                    'is_city' => 1,
                    'category_id' => $cat->id
                ]);

                if( $tpl ){
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city' => $city->getTitle(),
                        'category_rod' => $cat->category_rod,
                        'category' => $cat->category
                    ], ['tag_name']);

                    $tag = new TkSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_from = $city->getId();
                    $tag->city_to = $city->getId();
                    $tag->category_id = $cat->id;
                    $tag->domain_id = $city->getId();
                    if( !$tag->save() ){
                        Yii::error("Не удалось создать тег ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TkTagsController.SearchTagsGenerate');
                    }
                }
            }
        }
    }
}