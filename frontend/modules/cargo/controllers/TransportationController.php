<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 15.11.17
 * Time: 11:07
 */

namespace frontend\modules\cargo\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\CategoryHelper;
use common\helpers\LocationHelper;
use common\helpers\TemplateHelper;
use common\helpers\Utils;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CargoCategoryTags;
use common\models\City;
use common\models\FastCity;
use common\models\IntercityTags;
use common\models\LocationInterface;
use common\models\Payment;
use common\models\Transport;
use frontend\modules\articles\models\ArticlesSearch;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\cargo\widgets\models\CargoCarriageModel;
use frontend\modules\tk\models\TkSearch;
use frontend\modules\transport\models\TransportSearch;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\caching\ChainedDependency;
use yii\caching\DbDependency;
use yii\caching\TagDependency;
use yii\filters\PageCache;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use morphos\Russian\RussianLanguage;

class TransportationController extends Controller
{
    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }
    
    public function behaviors()
    {
        /** @var LocationInterface $location */
        $location = Yii::$app->request->get('location');

        /** @var CargoCategory[] $categories */
        $categories = Yii::$app->request->get('categories');
        if($categories){
            $categories = implode('-', CategoryHelper::categoryToLineSlug($categories));
        }

        $searchDependencyTags = [
            Cargo::tableName(),
            Transport::tableName(),
            Payment::tableName()
        ];
        if($location){
            $searchDependencyTags = [
                Cargo::tableName() . "-from-".$location->getCode(),
                Cargo::tableName() . "-to-".$location->getCode(),
                Transport::tableName() . "-from-".$location->getCode(),
                Transport::tableName() . "-to-".$location->getCode(),
                Payment::tableName()
            ];
        }

        return [
            [
                'class' => NoSubdomain::class,
                'only' => ['search2']
            ],

            [
                'class' => PageCache::class,
                // Кэш работает когда нет города в поддомене
                'enabled' => !LocationHelper::getCityFromDomain(),
                'only' => ['index'],
                'duration' => 86400,
                'variations' => [
                    $location ? $location->getCode() : null
                ]
            ],

            [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest && !LocationHelper::getCityFromDomain(),
                'only' => ['search2'],
                'duration' => 3600,
                'dependency' => new TagDependency(['tags' => $searchDependencyTags]),
                'variations' => [
                    $location ? $location->getCode() : null,
                    $categories
                ]
            ],
        ];
    }

    public function actionIndex(LocationInterface $location = null)
    {
        /// Обработка старой схемы
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;
        if($domainCity) {
            // Делаем редирект на новую структуру
            return $this->redirect('https://' . Yii::getAlias('@domain') .
                Url::toRoute(['/cargo/transportation', 'location' => $domainCity]), 301);
        }
        /////////////////////////

        $rootCategories = CargoCategory::find()
            ->andWhere([
                'create_tag' => 1,
                'root' => 1
            ])
            ->all();

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
        $this->view->params['breadcrumbs'][] = [
            'label' => 'Виды перевозок'
        ];
        ////////////////////////

        return $this->render('index', [
            'rootCategories' => $rootCategories,
            'location' => $location,
            'tpl' => TemplateHelper::get('cargo-transportation-list', $location),
            'matrixContentService' => $this->matrixContentService
        ]);
    }

    public function actionSearch($slug)
    {
        if($slug == 'perevozka-metaloprokata') $slug = 'perevozka-metalla';

        /** @var CargoCategory $category */
        $category = CargoCategory::findOne([
            'slug' => $slug,
            'create_tag' => 1
        ]);
        if( !$category)
            throw new NotFoundHttpException('Страница не найдена');

        /** @var FastCity $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;

        // Надо проверить есть ли контент на этой странице
        // Если нет, то 404
        // категория частная перевозка не отображается почему-то
        if ($domainCity)
            $isEnough = $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $domainCity, $category);
        else
            $isEnough = $this->matrixContentService->isEnoughContent('cargo-transportation-view', null, null, $category);

        if(!$isEnough){
            // Делаем редирект на новую структуру
            return Yii::$app->getResponse()->redirect('https://'.Yii::getAlias('@domain') .
                Url::toRoute(['/cargo/transportation/search2', 'slug' => $category, 'location' => $domainCity]), 301, false);
            //throw new NotFoundHttpException('Страница не найдена');
        }

        // Делаем редирект на новую структуру
        return Yii::$app->getResponse()->redirect('https://'.Yii::getAlias('@domain') .
            Url::toRoute(['/cargo/transportation/search2', 'slug' => $category, 'location' => $domainCity]), 301, false);


        //Чтобы при добавлении груза он был с текущей категорией
        $cargoCarriage = CargoCarriageModel::getInstance();
        $cargoCarriage->category_id = $category->id;

        $transportSearchModel = new TransportSearch();
        $tkSearchModel = new TkSearch();
        $cargoSearchModel = new CargoSearch();
        $arSearchModel = new ArticlesSearch();

        //если "Автомобильная перевозка"
        if($category->id == 85){
            $transportSearchModel->order = SORT_ASC;
            $tkSearchModel->order = SORT_ASC;
            $cargoSearchModel->order = SORT_ASC;
        }

        $transportSearchModel->pageSize = 5;
        $trSearchParams['TransportSearch'] = [
            'cargoCategoryIds' => $category->id,
            'locationFrom' => $cityID ? $cityID : ''
        ];
        $transportSearchModel->anyDirection = true;
        $transportSearchModel->sortPatternType = TransportSearch::SORT_PATTERN_MAIN_WITH_CATEGORY;

        $transportDataProvider = $transportSearchModel->search($trSearchParams);

        $tkSearchModel->pageSize = 5;
        $tkSearchParams['TkSearch'] = [
            'cargoCategoryIds' => [$category->id],
            'locationFrom' => $cityID ? $cityID : ''
        ];
        $tkDataProvider = $tkSearchModel->search($tkSearchParams);

        $cargoSearchModel->pageSize = 5;
        //заполняем параметры поиска груза на основне данных тега
        $queryParams['CargoSearch'] = [
            'cargoCategoryIds' => [$category->id],
            'locationFrom' => $cityID ? $cityID : ''
        ];
        $cargoSearchModel->anyDirection = true;
        $cargoSearchModel->showMainCargoCategory = true;
        $cargoDataProvider = $cargoSearchModel->search($queryParams);

        $arSearchModel->pageSize = 4;
        //заполняем параметры поиска груза на основне данных тега
        $queryParams['ArticlesSearch'] = [
            'categoryIds' => $category->id
        ];
        $articleDataProvider = $arSearchModel->search($queryParams);

        $this->layout = "@app/views/layouts/main_page/main";
        $this->viewPath = "@app/views/site";

        $this->view->params['breadcrumbs'][] = [
            'label' => 'Виды перевозки ' . ($domainCity?
                    RussianLanguage::in(GeographicalNamesInflection::getCase($domainCity->title, Cases::PREPOSITIONAL)):
                    'по России'),
            'url' => Url::toRoute('/cargo/transportation/')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => $category->category
        ];
        ///////////////

        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'pageTpl' => TemplateHelper::get('cargo-transportation-view', $domainCity, $category),
            'articlesDataProvider' => $articleDataProvider,
            'slug' => $slug
        ]);
    }

    /**
     * Метод для второго варианта (без доменов)
     * @param CargoCategory[] $categories - Список категорий из url адреса
     */
    public function actionSearch2(array $categories, LocationInterface $location = null)
    {
        /** @var CargoCategory $category */
        $category = array_pop($categories);
        if(!($category instanceof CargoCategory)){
            throw new NotFoundHttpException('Страница не найдена');
        }

        $categoryRequired = true;
        if($location instanceof City) {
            // Проверяем, должна ли быть доступна категория в городе в соответствии с настройками структуры
            $categoryRequired = Utils::check_mask($location->size, $category->city_size_mask);
        }

        $isEnoughContent = $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $location, $category);
        // Если на странице нет контента, но категория корневая
        if($category->root && !$categories && !$isEnoughContent){
            $this->view->registerMetaTag([
                'name' => 'robots',
                'content' => 'noindex'
            ]);

            $categoryRequired = true;
        }
        ////////////////////////////////////

        $isEnough = $categoryRequired || $isEnoughContent;
        if (!$isEnough) {
            return Yii::$app->getResponse()->redirect(Url::toRoute([
                '/cargo/transportation/search2',
                'slug' => $categories,
                'location' => $location
            ]), 301, false);
        }

        $priceFrom = 0;

        //Чтобы при добавлении груза он был с текущей категорией
        $cargoCarriage = CargoCarriageModel::getInstance();
        $cargoCarriage->category_id = $category->id;

        ///////////////
        /// Поиск
        $transportDataProvider = (new TransportSearch())
            ->setCargoCategories($category)
            ->setDirection(true)
            ->setSortPatternType(TransportSearch::SORT_PATTERN_MAIN_WITH_CATEGORY)
            ->setLocationFrom($location)
            ->setPageSize(8)
            ->search();

        $tkDataProvider = (new TkSearch())
            ->setCargoCategories($category)
            ->setLocationFrom($location)
            ->setPageSize(8)
            ->search();

        $cargoDataProvider = (new CargoSearch())
            ->setCargoCategories($category)
            ->setDirection(true)
            ->setLocationFrom($location)
            ->setShowMainCargoCategory(true)
            ->setPageSize(8)
            ->search();
        /////////////////////

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

        $cats = [];
        foreach($categories as $cat) {
            $cats[] = $cat;
            $this->view->params['breadcrumbs'][] = [
                'label' => $cat->category,
                'url' => Url::toRoute([
                    '/cargo/transportation/search2',
                    'location' => $location,
                    'slug' => $cats
                ])
            ];
        }
        $this->view->params['breadcrumbs'][] = [
            'label' => $category->category
        ];
        //////////////////////////////////

        $this->layout = "@app/views/layouts/main_page/main";
        $this->viewPath = "@app/views/site";
        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'pageTpl' => TemplateHelper::get('cargo-transportation-view', $location, $category, [
                'count_service' => $cargoDataProvider->totalCount + $transportDataProvider->totalCount,
                'price_from' => $priceFrom
            ]),
            'category' => $category,
            'location' => $location,
            'matrixContentService' => $this->matrixContentService,
            'intercityTags' => [
                'fromLocation' => IntercityTags::getFromLocationTags($location),
                'toLocation' => IntercityTags::getToLocationTags($location)
            ],
            'seeAlsoTags' => CargoCategoryTags::getCategoryTags($category, $location)
        ]);
    }
}
