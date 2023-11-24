<?php

namespace frontend\modules\cargo\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\CategoryHelper;
use common\helpers\LocationHelper;
use common\helpers\TemplateHelper;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CargoSearchTags;
use common\models\City;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\Payment;
use common\models\Region;
use common\models\Transport;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\cargo\models\CargoSearchForm;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Yii;
use yii\caching\TagDependency;
use yii\filters\PageCache;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class SearchController extends Controller
{
    public function behaviors()
    {
        /** @var string $locationFrom */
        $locationFrom = Yii::$app->request->get('locationFrom');

        /** @var string $locationTo */
        $locationTo = Yii::$app->request->get('locationTo');

        /** @var LocationInterface $location */
        $location = Yii::$app->request->get('location');

        if($locationFrom || $locationTo){
            $searchDependencyTags = [];
            if ($locationFrom) {
                $searchDependencyTags[] = Cargo::tableName() . "-from-" . $locationFrom;
            }
            if ($locationTo) {
                $searchDependencyTags[] = Cargo::tableName() . "-to-" . $locationTo;
            }
        }elseif($location){
            $searchDependencyTags[] = Cargo::tableName() . "-from-".$location->getCode();
        }else{
            $searchDependencyTags[] = Cargo::tableName();
        }

        return [
            [
                'class' => NoSubdomain::class,
                'only' => ['all']
            ],

            [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest && !LocationHelper::getCityFromDomain(),
                'only' => ['index'],
                'duration' => 3600,
                'dependency' => new TagDependency(['tags' => $searchDependencyTags]),
                'variations' => [
                    $location ? $location->getCode() : null,
                    $locationFrom,
                    $locationTo,
                    Yii::$app->request->get('slug'),
                    Yii::$app->request->get('page')
                ]
            ],

            [
                'class' => PageCache::class,
                'only' => ['all'],
                'duration' => 86400
            ]
        ];
    }

    public function actionIndex(LocationInterface $location = null, $slug = null)
    {
        ///////////////////
        // Редирект на папки для старой версии
        /** @var $dCity LocationInterface */
        $dCity = Yii::$app->getBehavior('geo')->domainCity;
        if($dCity){
            return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') .
                Url::toRoute(['/cargo/search/index', 'location' => $dCity, 'slug' => $slug]), 301, false);
        }
        //////////////////


        $params = [];
        $tplSubName = '';
        $category = null;

        $searchModel = (new CargoSearch())
                //если нет параметров поиска и есть поддомен,
                //то ищим груз, в котором присутствует город поддомена
                ->setLocationFrom($location);

        /////////////////////////////////////
        // Формирование breadcrumbs
        $bcLocations = $location ? $location->getParentLocation() : [];
        /** @var LocationInterface $bcLocation */
        foreach($bcLocations as $bcLocation){
            $this->view->params['breadcrumbs'][] = [
                'label' => $bcLocation->getTitle(),
                'url' => Url::toRoute([
                    '/cargo/transportation/search2',
                    'location' => $bcLocation
                ])
            ];
        }
        ///////////////////////////

        //Если передан ЧПУ
        if ($slug) {
            $filter = CargoSearchTags::findOne(['slug' => $slug/*, 'domain_id' => $domainCityId*/]);
            if (!$filter) {
                throw new NotFoundHttpException('Страница не найдена');
            }

            /////////////////
            // Если обращение с городом, а фильтр без города делаем 301 редирект на страницу из фильтра
            $filterDomain = $filter->domain;
            if(($filterDomain && !$filterDomain->equal($location)) || ($location && !$location->equal($filterDomain))){
                return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') .
                    Url::toRoute(['/cargo/search/index', 'location' => $filterDomain, 'slug' => $slug]), 301, false);
            }
            ///////////////

            $category = $filter->category;

            //формируем параметры поиска
            $searchModel
                ->setLocationFrom($filter->cityFrom)
                ->setLocationTo($filter->cityTo)
                ->setCargoCategories($category)
                ->setDiffDirection(true);

            //определяем шаблон из вариантов
            if (isset($filter->city_from) && isset($filter->city_to)) {
                $tplSubName = '-from-to-city'; //"Из города в город"
                $params['city_to'] = $filter->cityTo->title_ru;
                $params['city_from'] = $filter->cityFrom->title_ru;
                $params['city_to_region_ex'] = $filter->cityTo->getTitleWithRegionForTwig();
                $params['city_from_region_ex'] = $filter->cityFrom->getTitleWithRegionForTwig();
            } elseif (isset($filter->city_from)) {
                $tplSubName = '-from-city'; //"Из города"
                $params['city_from'] = $filter->cityFrom->title_ru;
                $params['city_from_region_ex'] = $filter->cityFrom->getTitleWithRegionForTwig();
            } elseif (isset($filter->city_to)) {
                $tplSubName = '-to-city'; //"В город"
                $params['city_to'] = $filter->cityTo->title_ru;
                $params['city_to_region_ex'] = $filter->cityTo->getTitleWithRegionForTwig();
            } else { //"По стране"
                $tplSubName = '-inside-country';
            }

            // Хлебные крошки
            $this->view->params['breadcrumbs'][] = [
                'label' => 'Поиск грузов',
                'url' => Url::toRoute(['/cargo/search/index', 'location'=>$location])
            ];
            $this->view->params['breadcrumbs'][] = [
                'label' => 'Фильтры поиска',
                'url' => Url::toRoute('/cargo/search/all')
            ];
            $this->view->params['breadcrumbs'][] = [
                'label' => $filter->name
            ];
            ////////////////
        } else {
            // Если основной домен, номер страницы более 1 и нет параметров поиска - 404 ошибка
            // Для того, что бы на основном домене не выдавались роботу лишние страницы, которые есть на поддоменах
            $page = $searchModel->getPage();
           /* if ( !$location && $page > 1 && !isset($queryParams['searchClick'])) {
                throw new NotFoundHttpException('Страница не найдена');
            }*/
            ///////////////////////////////

            if($page > 1) {
                $this->view->params['breadcrumbs'][] = [
                    'label' => 'Поиск грузов',
                    'url' => Url::toRoute(['/cargo/search/index', 'location'=>$location])
                ];
                $this->view->params['breadcrumbs'][] = [
                    'label' => "Страница {$page}"
                ];
            } else {
                $this->view->params['breadcrumbs'][] = [
                    'label' => 'Поиск грузов'
                ];
            }
            //////////////////////
        }

        $queryParams = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($queryParams);

        //Скрыть пагинацию если это первая страница и не нажата кнопка поиска
        // и поиск на главном домене или если slug
        $showPagination = !($searchModel->getPage() == 1 && !isset($queryParams['searchClick']) && (!$location || $slug));

        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'model' => $searchModel,
            'data' => [
                'ip' => Yii::$app->request->remoteIP,
                'userid' => (!Yii::$app->user->isGuest ? Yii::$app->user->id : Yii::$app->session->id),
                'userAgent' => Yii::$app->request->userAgent,
                'domainId' => $location ? $location->getId() : 0,
                'domainCode' => $location ? $location->getCode() : 'main'
            ]
        ]);

        return $this->render('search', [
            'model' => $searchModel,
            'dataProvider' => $dataProvider,
            'tags' => CargoSearchTags::findTags($location)->limit(5)->all(),
            'pageTpl' => TemplateHelper::get("cargo-search{$tplSubName}-view", $location, $category, array_merge($params,[
                'count_service' => $dataProvider->totalCount
            ])),
            'showPagination' => $showPagination
        ]);
    }

    /**
     * Отображение ссылок фильтра
     *
     * @return string
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function actionAll()
    {
      //  $curcity = Yii::$app->getBehavior('geo')->domainCity;

        return $this->render('all', [
            'tags' => CargoSearchTags::find()->/*where(['domain_id' => $curcity ? $curcity->id : 0])->*/all(),
            'pageTpl' => TemplateHelper::get('cargo-search-list')
        ]);
    }
}
