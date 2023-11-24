<?php
/**
 * Created by PhpStorm.
 * User: ������� ������
 * Date: 14.07.2016
 * Time: 13:54
 */

namespace console\controllers;

use common\models\City;
use common\models\Profile;
use common\models\Region;
use common\models\Transport;
use common\models\TransporterTags;
use console\helpers\PageCacheHelper;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use common\helpers\TemplateHelper;
use common\models\CargoCategory;
use common\models\FastCity;
use common\models\TransportSearchTags;
use yii\helpers\Url;

class TransporterController extends BaseController
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
     * Обновляем теги на странице перевозчика. Если передан $profileid, то только у этого перевозчика, иначе у всех
     * @param null $profileid ИД профиля перевозчика
     */
    public function actionTagsUpdate($profileid = null)
    {
        $trans = Yii::$app->db->beginTransaction();
        TransporterTags::deleteAll();

        $query = Profile::find()->limit(500);
        if($profileid)
            $query->where(['id' => $profileid]);

        /** @var Profile $profile */
        while($profiles = $query->all()) {
            $query->offset += $query->limit;

            foreach ($profiles as $profile) {
                $tQuery = Transport::find()->where(['profile_id' => $profile->id]);

                $cityIdList = $categoryIdList = [];
                /** @var Transport $transport */
                 foreach($tQuery->each(10) as $transport){
                     array_push($cityIdList, $transport->city_from);
                     array_push($cityIdList, $transport->city_to);

                     $categories = $transport->cargoCategoryIds;
                     if($categories)
                        array_push($categoryIdList, ...$categories);
                 }

                 foreach(array_unique($cityIdList) as $cityId){
                      $city = City::findOne($cityId);
                      $tpl = TemplateHelper::findTemplate('main', $city);
                      if($tpl && $this->matrixContentService->isEnoughContentAnyDirection('main', $city)) {
                          $tpl = TemplateHelper::fillTemplate($tpl, [
                              'city' => $city->getTitle()
                          ], ['tag_name']);

                          $tag = new TransporterTags();
                          $tag->profile_id = $profile->id;
                          $tag->name = $tpl->tag_name;
                          $tag->count = $this->matrixContentService->getContentAnyDirectionByTpl('main', $city);
                          $tag->url = 'https://' . Yii::getAlias('@domain') .
                              Url::toRoute(['/cargo/transportation/search2', 'location' => $city]);
                          $tag->save();
                      }

                      foreach(array_unique($categoryIdList) as $categoryId){
                          $category = CargoCategory::findOne($categoryId);
                          $tpl = TemplateHelper::findTemplate('cargo-transportation-view', $city, $category);
                          if($tpl && $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $city, $category)) {
                              $tpl = TemplateHelper::fillTemplate($tpl, [
                                  'city' => $city->getTitle(),
                                  'category' => $category->category
                              ], ['tag_name']);

                              $tag = new TransporterTags();
                              $tag->profile_id = $profile->id;
                              $tag->name = $tpl->tag_name;
                              $tag->count = $this->matrixContentService->getContentAnyDirectionByTpl('cargo-transportation-view', $city, $category);
                              $tag->url = 'https://' . Yii::getAlias('@domain') . Url::toRoute(['/cargo/transportation/search2',
                                  'location' => $city,
                                  'slug' => $category]);
                              $tag->save();
                          }
                      }
                 }
            }
        }

        $trans->commit();
    }

    /**
     * Генерирует теги для страницы поиска транспорта
     * @param $cityid Ид домена. Если не указан, то теги строятся для всех городов
     */
    public function actionSearchTagsGenerate(int $cityid = null)
    {
        $mainCities = FastCity::find()->all();
        if($cityid) {
            $mainCities = [FastCity::findOne($cityid)];
        }

        if(!$mainCities){
            echo 'Нет городов';
            return;
        }

        /** @var CargoCategory[] $mainCategories */
        $mainCategories = CargoCategory::find()->all();

        $count = count($mainCities);
        $iter = 0;
        foreach($mainCities as $mainCity){
            $iter++;
            printf("\r%3d%%", $iter/$count*100);

            $city = $mainCity->city;

            //поиск груза по городу
            $tpl = TemplateHelper::findTemplate('transport-search-inside-city-view', $city);
            if($tpl && $this->matrixContentService->isEnoughContent('transport-search-inside-city-view', $city, $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city' => $city->getTitle()
                ], ['tag_name']);

                $tag = new TransportSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_from = $city->getId();
                $tag->city_to = $city->getId();
                $tag->domain_id = $city->getId();
                if( !$tag->save() ){
                    Yii::error("Не удалось создать тег(1) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                }
            }

            //поиск грузов из города
            $tpl = TemplateHelper::findTemplate('transport-search-from-city-view', $city);
            if($tpl && $this->matrixContentService->isEnoughContent('transport-search-from-city-view', $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city_from' => $city->getTitle(),
                ], ['tag_name']);

                $tag = new TransportSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_from = $city->getId();
                $tag->domain_id = $city->getId();
                if( !$tag->save() ){
                    Yii::error("Не удалось создать тег(2) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                }
            }

            //поиск грузов в город
            $tpl = TemplateHelper::findTemplate('transport-search-to-city-view', $city);
            if($tpl && $this->matrixContentService->isEnoughContent('transport-search-to-city-view', null, $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city_to' => $city->getTitle(),
                ], ['tag_name']);

                $tag = new TransportSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_to = $city->getId();
                $tag->domain_id = $city->getId();
                if( !$tag->save() ){
                    Yii::error("Не удалось создать тег(3) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                }
            }

            //поиск грузов в город для главного домена
            $tpl = TemplateHelper::findTemplate('transport-search-to-city-view');
            if($tpl && $this->matrixContentService->isEnoughContent('transport-search-to-city-view', null, $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city_to' => $city->getTitle(),
                ], ['tag_name']);

                $tag = new TransportSearchTags();
                $tag->name = $tpl->tag_name;
                $tag->city_to = $city->getId();
                if( !$tag->save() ){
                    Yii::error("Не удалось создать тег(4) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                }
            }

            $tpl = TemplateHelper::findTemplate('transport-search-from-to-city-view', $city);
            if( $tpl ){
                //поиск грузов из города в город
                foreach($mainCities as $mainCity2){
                    if( $city->getId() == $mainCity2->cityid ) continue;

                    $city2 = $mainCity2->city;

                    if( $this->matrixContentService->isEnoughContent('transport-search-from-to-city-view', $city, $city2)) {
                        $_tpl = TemplateHelper::fillTemplate($tpl, [
                            'city_from' => $city->getTitle(),
                            'city_to' => $city2->getTitle(),
                        ], ['tag_name']);

                        $tag = new TransportSearchTags();
                        $tag->name = $_tpl->tag_name;
                        $tag->city_from = $city->getId();
                        $tag->city_to = $city2->getId();
                        $tag->domain_id = $city->getId();
                        if( !$tag->save() ){
                            Yii::error("Не удалось создать тег(5) transport-search-from-to-city-view ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                        }
                    }
                }
            }

            //по городам и категориям
            foreach($mainCategories as $cat){
                $tpl = TemplateHelper::findTemplate('transport-search-inside-city-view', $city, $cat, false);
                if( $tpl && $this->matrixContentService->isEnoughContent('transport-search-inside-city-view', $city, $city, $cat) ){
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city' => $city->getTitle(),
                        'category_rod' => $cat->category_rod
                    ], ['tag_name']);

                    $tag = new TransportSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_from = $city->getId();
                    $tag->city_to = $city->getId();
                    $tag->category_id = $cat->id;
                    $tag->domain_id = $city->getId();
                    if( !$tag->save() ){
                        Yii::error("Не удалось создать тег(6) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                    }
                }

                //поиск грузов из города
                $tpl = TemplateHelper::findTemplate('transport-search-from-city-view', $city, $cat, false);
                if( $tpl && $this->matrixContentService->isEnoughContent('transport-search-from-city-view', $city, null, $cat) ){
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city_from' => $city->getTitle(),
                        'category_rod' => $cat->category_rod
                    ], ['tag_name']);

                    $tag = new TransportSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_from = $city->getId();
                    $tag->category_id = $cat->id;
                    $tag->domain_id = $city->getId();
                    if( !$tag->save() ){
                        Yii::error("Не удалось создать тег(7) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                    }
                }


                //поиск грузов в город
                $tpl = TemplateHelper::findTemplate('transport-search-to-city-view', $city, $cat, false);
                if( $tpl && $this->matrixContentService->isEnoughContent('transport-search-to-city-view', null, $city, $cat) ){
                    $tpl = TemplateHelper::fillTemplate($tpl, [
                        'city_to' => $city->getTitle(),
                        'category_rod' => $cat->category_rod
                    ], ['tag_name']);

                    $tag = new TransportSearchTags();
                    $tag->name = $tpl->tag_name;
                    $tag->city_to = $city->getId();
                    $tag->category_id = $cat->id;
                    $tag->domain_id = $city->getId();
                    if( !$tag->save() ){
                        Yii::error("Не удалось создать тег(8) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                    }
                }

                $tpl = TemplateHelper::findTemplate('transport-search-from-to-city-view', $city, $cat, false);
                if( $tpl ){
                    //поиск грузов из города в город
                    foreach($mainCities as $mainCity2){
                        if( $city->getId() == $mainCity2->cityid ) continue;

                        $city2 = $mainCity2->city;

                        if( $this->matrixContentService->isEnoughContent('transport-search-from-to-city-view', $city, $city2, $cat) ){
                            $_tpl = TemplateHelper::fillTemplate($tpl, [
                                'city_from' => $city->getTitle(),
                                'city_to' => $city2->getTitle(),
                                'category_rod' => $cat->category_rod
                            ], ['tag_name']);

                            $tag = new TransportSearchTags();
                            $tag->name = $_tpl->tag_name;
                            $tag->city_from = $city->getId();
                            $tag->city_to = $city2->getId();
                            $tag->category_id = $cat->id;
                            $tag->domain_id = $city->getId();
                            if( !$tag->save() ){
                                Yii::error("Не удалось создать тег(9) ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'TransportSearchTags.SearchTagsGenerate');
                            }
                        }
                    }
                }
            }
        }

        // Перенесли создание кэша за коммит транзакции иначе данные не будут обновлены
        foreach($mainCities as $city){
            $this->buildSearchTagsAllUrl('https://'.$city->code.'.'.Yii::getAlias('@domain'));
        }

        $this->buildSearchTagsAllUrl('https://'.Yii::getAlias('@domain'));
    }

    /**
     * Строим кэш страниц со ссылками на поиск
     * @param $baseUrl - базовый урл для страниц
     */
    private function buildSearchTagsAllUrl($baseUrl)
    {
        PageCacheHelper::fetchUrl($baseUrl.Url::toRoute(['/transport/search/all']));
        PageCacheHelper::fetchUrl($baseUrl.Url::toRoute(['/cargo/search/all']));
    }
}
