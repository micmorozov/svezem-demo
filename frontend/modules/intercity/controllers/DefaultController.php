<?php

namespace frontend\modules\intercity\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\CategoryHelper;
use common\helpers\LocationHelper;
use common\helpers\TemplateHelper;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\City;
use common\models\FastCity;
use common\models\IntercityTags;
use common\models\LocationInterface;
use common\models\Payment;
use common\models\Transport;
use frontend\modules\articles\models\ArticlesSearch;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\tk\models\TkSearch;
use frontend\modules\transport\models\TransportSearch;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Yii;
use yii\caching\ChainedDependency;
use yii\caching\DbDependency;
use yii\caching\TagDependency;
use yii\filters\PageCache;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use yii\web\Response;

/**
 * Default controller for the `intercity` module
 */
class DefaultController extends Controller
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
        $subDomain = Yii::$app->getBehavior('geo')->domainCity;
        if($subDomain){
            return [
                [
                    'class' => NoSubdomain::class,
                    'only' => ['transportation2']
                ]
            ];
        }

        /** @var LocationInterface $location */
        $location = Yii::$app->request->get('location');

        /** @var City $cityFrom */
        $cityFrom = Yii::$app->request->get('cityFrom');
        /** @var City $cityTo */
        $cityTo = Yii::$app->request->get('cityTo');
        /** @var CargoCategory $root */
        $root = Yii::$app->request->get('root');

        $indexDependencyTags = [];
        if(!$location) {
            $indexDependencyTags = [
                Cargo::tableName(),
                Transport::tableName(),
                Payment::tableName()
            ];
        }

        $transportationDependencyTags = [];
        if($cityFrom && $cityTo) {
            $transportationDependencyTags = [
                Cargo::tableName() . '-from-' . $cityFrom->getCode() . '-to-' . $cityTo->getCode(),
                Transport::tableName() . '-from-' . $cityFrom->getCode() . '-to-' . $cityTo->getCode(),
                Payment::tableName()
            ];
        }

        return [
            [
                'class' => NoSubdomain::class,
                'only' => ['transportation2']
            ],

            [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest && !LocationHelper::getCityFromDomain(),
                'only' => ['index'],
                'duration' => 3600,
                'dependency' => new TagDependency(['tags' => $indexDependencyTags]),
                'variations' => [
                    ($location instanceof LocationInterface) ? $location->getCode() : null
                ]
            ],

            [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest && !LocationHelper::getCityFromDomain(),
                'only' => ['transportation2'],
                'duration' => 3600,
                'dependency' => new TagDependency(['tags' => $transportationDependencyTags]),
                'variations' => [
                    ($cityFrom instanceof LocationInterface) ? $cityFrom->getCode() : null,
                    ($cityTo instanceof LocationInterface) ? $cityTo->getCode() : null,
                    $root ? $root->slug : null
                ]
            ],
        ];
    }

    /**
     * Страница со списком направлений перевозки: город из домена - город из таблицы fast_city
     * @param LocationInterface $location
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex(LocationInterface $location = null){

        /** @var LocationInterface $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;
        if($domainCity) {
            // Делаем редирект на новую структуру
            return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') .
                Url::toRoute(['/intercity/default/index', 'location' => $domainCity]), 301, false);
        }

        if($location instanceof City){
            $this->view->params['breadcrumbs'][] = [
                'label' => 'Междугородние перевозки'
            ];

            $intercityTags = IntercityTags::find()
                ->where(['city_from' => $location->getId()])
                ->all();

            return $this->render('index', [
                'pageTpl' => TemplateHelper::get('intercity-list', $location),
                'tags' => $intercityTags
            ]);
        } else {
            return $this->_search2($location);
        }
    }

    /**
     * Страница(интерфейс главной) где отображаются грузы, соответствующие направлению перевозки: город из домена-$cityTo
     * @param $cityTo
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionTransportation($cityTo){
        $cityTo = City::findOne(['code' => $cityTo]);
        /** @var FastCity $cityfrom */
        $cityfrom = Yii::$app->getBehavior('geo')->domainCity;

        if( !$cityTo || !$cityfrom || ($cityTo == $cityfrom))
            throw new NotFoundHttpException('Страница не найдена');

        $rootCategory = CargoCategory::findOne(['slug' => 'gruzoperevozki']);


        return Yii::$app->getResponse()->redirect('https://'.Yii::getAlias('@domain') .
            Url::toRoute(['/intercity/default/transportation2', 'root' => $rootCategory, 'cityFrom' => $cityfrom, 'cityTo' => $cityTo]), 301, false);

        // Надо проверить есть ли контент на этой странице
        // Если нет, то 404
        if (!$this->matrixContentService->isEnoughContent('intercity-view', $cityfrom, $cityTo))
            throw new NotFoundHttpException('Страница не найдена');

        //транспорт может выводиться по регионам/городам
        $transportShow = Yii::$app->request->cookies->getValue('transportShow', 'city');
        $transportShow = Yii::$app->request->get('transportShow', $transportShow);
        Yii::$app->response->cookies->add(new Cookie([
            'name' => 'transportShow',
            'value' => $transportShow
        ]));

        $transportSearchModel = new TransportSearch();
        $tkSearchModel = new TkSearch();
        $cargoSearchModel = new CargoSearch();

        //шаблон сортировки
        $transportSearchModel->sortPatternType = TransportSearch::SORT_PATTERN_MAIN_WITH_CATEGORY;

        //Есть теги с указанием вида перевозки
        //Чтобы на главной странице контент отличался сортируем его в обратном порядке по ИД
        $transportSearchModel->order = $tkSearchModel->order = $cargoSearchModel->order = SORT_DESC;

        $transportSearchModel->pageSize = 5;
        $queryParams['TransportSearch'] = [
            'locationFrom' => $cityfrom->cityid,
            'locationTo' => $cityTo->cityid
        ];

        $transportDataProvider = $transportSearchModel->search($queryParams);

        $tkSearchModel->pageSize = 5;
        $tkSearchParams['TkSearch'] = [
            'locationFrom' => $cityfrom->cityid,
            'locationTo' => $cityTo->cityid
        ];
        $tkDataProvider = $tkSearchModel->search($tkSearchParams);

        $cargoSearchModel = new CargoSearch();
        $cargoSearchModel->pageSize = 5;

        //заполняем параметры поиска груза на основне данных тега
        $queryParams['CargoSearch'] = [
            'locationFrom' => $cityfrom->cityid,
            'locationTo' => $cityTo->cityid
        ];

        $cargoDataProvider = $cargoSearchModel->search($queryParams);

        $this->layout = "@app/views/layouts/main_page/main";
        $this->viewPath = "@app/views/site";

        $this->view->params['breadcrumbs'][] = [
            'label' => 'Междугородние перевозки',
            'url' => Url::toRoute('/intercity/')
        ];
        $strCityDirection = GeographicalNamesInflection::getCase($cityfrom->title, Cases::ABLATIVE) .
            ' и ' . GeographicalNamesInflection::getCase($cityTo->title, Cases::ABLATIVE);
        $this->view->params['breadcrumbs'][] = [
            'label' => "Грузоперевозки между {$strCityDirection}"
        ];

        // Ссылки следующая и предыдущая
        /* TODO Слишком долго работает поиск следующего городо
        $nextCity = $this->getNextCity($cityfrom, $cityTo);
        $prevCity = $this->getPrevCity($cityfrom, $cityTo);
        if($nextCity) $this->view->params['navlinks']['next'] = Url::toRoute(['/intercity/default/transportation', 'cityTo' => $nextCity->code]);
        if($prevCity) $this->view->params['navlinks']['prev'] = Url::toRoute(['/intercity/default/transportation', 'cityTo' => $prevCity->code]);
        *////////////////

        $intercityTags = IntercityTags::find()
            ->andWhere([
                'city_from' => $cityfrom->cityid,
                'city_to' => $cityTo->cityid,
            ])
            ->andWhere(['not', ['category_id'=>null]])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'pageTpl' => TemplateHelper::get('intercity-view', $cityfrom, null, ['city_to' => $cityTo->title]),
            'tags' => $intercityTags
        ]);
    }

    /**
     * Страница(интерфейс главной) где отображаются грузы, соответствующие направлению перевозки
     * Используется с вариантом папок
     * @param CargoCategory $root
     * @param $cityFrom
     * @param $cityTo
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionTransportation2(CargoCategory $root, City $cityFrom, City $cityTo)
    {
        if($cityTo->getCode() == $cityFrom->getCode())
            throw new NotFoundHttpException('Страница не найдена');

        // Надо проверить есть ли контент на этой странице
        // Если нет, то 404
        if (!$this->matrixContentService->isEnoughContent('intercity-view', $cityFrom, $cityTo/*, $root*/))
            throw new NotFoundHttpException('Страница не найдена');

        ///////////////////////////////
        /// Поиск
        $transportDataProvider = (new TransportSearch())
            ->setSortPatternType(TransportSearch::SORT_PATTERN_MAIN_WITH_CATEGORY)
            ->setLocationFrom($cityFrom)
            ->setLocationTo($cityTo)
            ->setCargoCategories($root)
            ->setPageSize(8)
            ->search();

        $tkDataProvider = (new TkSearch())
            ->setLocationFrom($cityFrom)
            ->setLocationTo($cityTo)
            ->setCargoCategories($root)
            ->setPageSize(8)
            ->search();

        $cargoDataProvider = (new CargoSearch())
            ->setLocationFrom($cityFrom)
            ->setLocationTo($cityTo)
            ->setCargoCategories($root)
            ->setPageSize(8)
            ->search();
        ////////////////////////////////

        ////////////////////////////
        // Формируем breadcrumbs
        $this->view->params['breadcrumbs'][] = [
            'label' => $root->category,
            'url' => Url::toRoute([
                '/cargo/transportation/search2',
                'slug' => $root
            ])
        ];

        $this->view->params['breadcrumbs'][] = [
            'label' => "{$cityFrom->getTitle()} - {$cityTo->getTitle()}"
        ];

        // Ссылки следующая и предыдущая
        /* TODO Слишком долго работает поиск следующего городо
        $nextCity = $this->getNextCity($cityFrom, $cityTo);
        $prevCity = $this->getPrevCity($cityFrom, $cityTo);
        if($nextCity) $this->view->params['navlinks']['next'] = Url::toRoute(['/intercity/default/transportation2', 'root' => $root, 'cityFrom' => $cityFrom, 'cityTo' => $nextCity]);
        if($prevCity) $this->view->params['navlinks']['prev'] = Url::toRoute(['/intercity/default/transportation2', 'root' => $root, 'cityFrom' => $cityFrom, 'cityTo' => $prevCity]);
        *//////////////////////////////////

        $intercityTags = IntercityTags::find()
            ->andWhere([
                'city_from' => $cityFrom->getId(),
                'city_to' => $cityTo->getId(),
            ])
            ->andWhere(['not', ['category_id'=>null]])
            ->limit(10)
            ->all();

        // Минимальная цена в наборе
        $priceFrom = 0;//$this->matrixContentService->getContentCount($this->matrixContentService->PRICE_TRANSPORT_FROM, $cityFrom->cityid, $cityTo->cityid, $rootCategory->id);

        $this->layout = "@app/views/layouts/main_page/main";
        $this->viewPath = "@app/views/site";
        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'pageTpl' => TemplateHelper::get('intercity-view', $cityFrom, $root, [
                'city_to' => $cityTo->getTitle(),
                'city_to_region_ex' => $cityTo->getTitleWithRegionForTwig(),
                'count_service' => $cargoDataProvider->totalCount + $transportDataProvider->totalCount,
                'price_from' => $priceFrom
            ]),
            'tags' => $intercityTags,
            'location' => $cityFrom,
            'cityFrom' => $cityFrom,
            'cityTo' => $cityTo,
            'matrixContentService' => $this->matrixContentService,
            'intercityTags2' => [
                'fromLocation' => IntercityTags::getFromLocationTags($cityFrom),
                'toLocation' => IntercityTags::getToLocationTags($cityTo)
            ],
        ]);
    }

    /**
     * Страница(дизайн главной) где отображаются грузы, соответствующие направлению и видам перевозки: город из домена-$cityTo и
     * $slug в качестве вида перевозки
     * @param $cityTo
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionSearch($cityTo, $slug)
    {
        $category = CargoCategory::findOne(['slug' => 'gruzoperevozki']);

        /** @var LocationInterface $cityfrom */
        $cityfrom = Yii::$app->getBehavior('geo')->domainCity;
        $cityTo = City::findOne(['code' => $cityTo]);

        if( !$cityTo || !$cityfrom || ($cityTo == $cityfrom))
            throw new NotFoundHttpException('Страница не найдена');

        // Надо проверить есть ли контент на этой странице
        // Если нет, то 404
        if (!$this->matrixContentService->isEnoughContent('intercity-category-view', $cityfrom, $cityTo, $category)) {
            throw new NotFoundHttpException('Страница не найдена');
        }

        return Yii::$app->getResponse()->redirect('https://'.Yii::getAlias('@domain') .
            Url::toRoute(['/intercity/default/transportation2', 'root' => $category, 'cityFrom' => $cityfrom, 'cityTo' => $cityTo]), 301, false);

        $transportSearchModel = new TransportSearch();
        $tkSearchModel = new TkSearch();
        $cargoSearchModel = new CargoSearch();
        $arSearchModel = new ArticlesSearch();

        //шаблон сортировки
        $transportSearchModel->sortPatternType = TransportSearch::SORT_PATTERN_MAIN_WITH_CATEGORY;

        //Есть теги с указанием вида перевозки
        //Чтобы на странице контент отличался сортируем его в обратном порядке по ИД
        $transportSearchModel->order = $tkSearchModel->order = $cargoSearchModel->order = $arSearchModel->order = SORT_DESC;
        //если "Автомобильная перевозка"
        if($category->id == 85){
            $transportSearchModel->order = SORT_ASC;
            $tkSearchModel->order = SORT_ASC;
            $cargoSearchModel->order = SORT_ASC;
        }

        $transportSearchModel->pageSize = 5;
        $queryParams['TransportSearch'] = [
            'locationFrom' => $cityfrom->cityid,
            'locationTo' => $cityTo->cityid,
            'cargoCategoryIds' => [$category->id],
        ];

        $transportDataProvider = $transportSearchModel->search($queryParams);

        $tkSearchModel->pageSize = 5;
        $tkSearchParams['TkSearch'] = [
            'locationFrom' => $cityfrom->cityid,
            'locationTo' => $cityTo->cityid,
            'cargoCategoryIds' => [$category->id],
        ];
        $tkDataProvider = $tkSearchModel->search($tkSearchParams);

        $cargoSearchModel->pageSize = 5;

        //заполняем параметры поиска груза на основне данных тега
        $queryParams['CargoSearch'] = [
            'locationFrom' => $cityfrom->cityid,
            'locationTo' => $cityTo->cityid,
            'cargoCategoryIds' => [$category->id],
        ];

        // На странице видов перевозки из города в город отображаем грузы по следующему алгоритму
        // Если категория дочерняя, то отображаем грузы у кого данная категория отмечена как главная
        // Если категория родительская, то отображаем грузы по одному из вложенных категорий
        $cargoSearchModel->showMainCargoCategory = true;
        $cargoDataProvider = $cargoSearchModel->search($queryParams);

        $arSearchModel->pageSize = 4;
        //заполняем параметры поиска груза на основне данных тега
        $queryParams['ArticlesSearch'] = [
            'categoryIds' => $category->id
        ];
        $articleDataProvider = $arSearchModel->search($queryParams);

        /*$articles = Articles::find()
            ->active()
            ->categorySlug($slug)
            ->limit(4)
            ->all();*/

        $this->layout = "@app/views/layouts/main_page/main";
        $this->viewPath = "@app/views/site";

        $this->view->params['breadcrumbs'][] = [
            'label' => 'Междугородние перевозки',
            'url' => Url::toRoute('/intercity/')
        ];
        $strCityDirection = GeographicalNamesInflection::getCase($cityfrom->title, Cases::ABLATIVE) .
            ' и ' . GeographicalNamesInflection::getCase($cityTo->title, Cases::ABLATIVE);
        $this->view->params['breadcrumbs'][] = [
            'label' => "Грузоперевозки между {$strCityDirection}",
            'url' => Url::toRoute('/intercity/'.$cityTo->code.'/')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Виды перевозки между {$strCityDirection}",
            'url' => Url::toRoute('/intercity/'.$cityTo->code.'/all/')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => $category->category
        ];

        // Ссылки следующая и предыдущая
        $nextCategory = $this->getNextCategory($cityfrom, $cityTo, $category);
        $prevCategory = $this->getPrevCategory($cityfrom, $cityTo, $category);
        if($nextCategory) $this->view->params['navlinks']['next'] = Url::toRoute(['/intercity/default/search', 'cityTo' => $cityTo->code, 'slug' => $nextCategory->slug]);
        if($prevCategory) $this->view->params['navlinks']['prev'] = Url::toRoute(['/intercity/default/search', 'cityTo' => $cityTo->code, 'slug' => $prevCategory->slug]);
        ///////////////

        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'pageTpl' => TemplateHelper::get('intercity-category-view', $cityfrom, $category, ['city_to' => $cityTo->title]),
            'articlesDataProvider' => $articleDataProvider,
            'slug' => $slug
        ]);
    }

    /**
     * Страница(дизайн главной) где отображаются грузы, соответствующие направлению $cityFrom-любой город
     *
     * @param $location
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws LoaderError
     * @throws SyntaxError
     */
    private function _search2(LocationInterface $location=null){

        ///////////////////////////////
        /// Поиск
        $transportDataProvider = (new TransportSearch())
            ->setSortPatternType(TransportSearch::SORT_PATTERN_MAIN_WITH_CATEGORY)
            ->setLocationFrom($location)
            ->setDiffDirection(true)
            ->setPageSize(8)
            ->search();

        $tkDataProvider = (new TkSearch())
            ->setLocationFrom($location)
            ->setDiffDirection(true)
            ->setPageSize(8)
            ->search();

        $cargoDataProvider = (new CargoSearch())
            ->setLocationFrom($location)
            ->setDiffDirection(true)
            ->setPageSize(8)
            ->search();
        ////////////////////////////////


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
            'label' => 'Междугородние перевозки',
        ];
        ///////////////////////////

        $this->layout = "@app/views/layouts/main_page/main";
        $this->viewPath = "@app/views/site";
        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'pageTpl' => TemplateHelper::get('intercity-list', $location),
            'location' => $location,
            'matrixContentService' => $this->matrixContentService
        ]);
    }

    /**
     * Страница со списком видов перевозки по направлению: город из домена-$cityTo
     * @param $cityTo
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionAlltags($cityTo)
    {
        /** @var LocationInterface $cityFrom */
        $cityFrom = Yii::$app->getBehavior('geo')->domainCity;
        $cityTo = City::findOne(['code' => $cityTo]);

        if( !$cityTo || !$cityFrom || ($cityTo == $cityFrom))
            throw new NotFoundHttpException('Страница не найдена');

        // Надо проверить есть ли контент на этой странице
        // Если нет, то 404
        if (!$this->matrixContentService->isEnoughContent('intercity-view', $cityFrom, $cityTo))
            throw new NotFoundHttpException('Страница не найдена');

        $category = CargoCategory::findOne(['slug' => 'gruzoperevozki']);
        return Yii::$app->getResponse()->redirect('https://'.Yii::getAlias('@domain') .
            Url::toRoute(['/intercity/default/transportation2', 'root' => $category, 'cityFrom' => $cityFrom, 'cityTo' => $cityTo]), 301, false);


        $this->view->params['breadcrumbs'][] = [
            'label' => 'Междугородние перевозки',
            'url' => Url::toRoute('/intercity/')
        ];
        $strCityDirection = GeographicalNamesInflection::getCase($cityFrom->getTitle(), Cases::ABLATIVE) .
            ' и ' . GeographicalNamesInflection::getCase($cityTo->getTitle(), Cases::ABLATIVE);
        $this->view->params['breadcrumbs'][] = [
            'label' => "Грузоперевозки между {$strCityDirection}",
            'url' => Url::toRoute('/intercity/'.$cityTo->getCode().'/')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Виды перевозки между {$strCityDirection}"
        ];

        $intercityTags = IntercityTags::find()
            ->andWhere([
                'city_from' => $cityFrom->getId(),
                'city_to' => $cityTo->getId(),
            ])
            ->andWhere(['not', ['category_id'=>null]])
            ->all();

        return $this->render('alltags', [
            'pageTpl' => TemplateHelper::get('intercity-category-list', $cityFrom,  null, [
                'city_to' => $cityTo->getTitle(),
                'city_to_region_ex' => $cityTo->getTitleWithRegionForTwig(),
            ]),
            'tags' => $intercityTags
        ]);
    }

    /**
     * Получаем следующий город
     */
    private function getPrevCity(City $cityFrom, City $cityTo): ?City
    {
        $fastCities = FastCity::find()
            ->andWhere(['>', 'cityid', $cityTo->getId()])
            ->all();

        $result = null;
        foreach($fastCities as $fs){
            // Исключаем город сам на себя
            if($cityFrom->getId() == $fs->cityid) continue;

            if (!$this->matrixContentService->isEnoughContent('intercity-view', $cityFrom, $fs->city))
                continue;

            $result = $fs->city;
            break;
        }

        return $result;
    }

    /**
     * Получаем предыдущий город
     */
    private function getNextCity(City $cityFrom, City $cityTo): ?City
    {
        $fastCities = FastCity::find()
            ->andWhere(['<', 'cityid', $cityTo->getId()])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $result = null;
        foreach($fastCities as $fs){
            // Исключаем город сам на себя
            if($cityFrom->getId() == $fs->cityid) continue;

            if (!$this->matrixContentService->isEnoughContent('intercity-view', $cityFrom, $fs->city))
                continue;

            $result = $fs->city;
            break;
        }

        return $result;
    }

    /**
     * Получаем следующую категорию
     */
    private function getNextCategory(City $cityFrom, City $cityTo, CargoCategory $category): ?CargoCategory
    {
        // Исключаем город сам на себя
        if($cityFrom->getId() == $cityTo->getId()) return null;

        $categories = CargoCategory::find()
            ->where(['create_tag'=>1])
            ->andWhere(['>', 'id', $category->id])
            ->all();

        $result = null;
        foreach($categories as $cat){
            if( !$this->matrixContentService->isEnoughContent('intercity-category-view', $cityFrom, $cityTo, $cat) )
                continue;

            $result = $cat;
            break;
        }

        return $result;
    }

    /**
     * Получаем предыдущую категорию
     */
    private function getPrevCategory(City $cityFrom, City $cityTo, CargoCategory $category): ?CargoCategory
    {
        // Исключаем город сам на себя
        if($cityFrom->getId() == $cityTo->getId()) return null;

        $categories = CargoCategory::find()
            ->where(['create_tag'=>1])
            ->andWhere(['<', 'id', $category->id])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $result = null;
        foreach($categories as $cat){
            if( !$this->matrixContentService->isEnoughContent('intercity-category-view', $cityFrom, $cityTo, $cat) )
                continue;

            $result = $cat;
            break;
        }

        return $result;
    }
}
