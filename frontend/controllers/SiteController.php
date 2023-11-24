<?php
namespace frontend\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\LocationHelper;
use common\models\Cargo;
use common\models\City;
use common\models\LocationInterface;
use common\models\Payment;
use common\models\Region;
use common\models\Transport;
use common\models\VkAppForm;
use frontend\modules\articles\models\ArticlesSearch;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Twig\Error\LoaderError;
use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;
use yii\caching\ChainedDependency;
use yii\caching\DbDependency;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\filters\PageCache;
use yii\helpers\Url;
use yii\web\Response;
use common\helpers\TemplateHelper;
use common\models\CargoCategory;
use common\models\FastCity;
use Yii;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\tk\models\TkSearch;
use frontend\modules\transport\models\TransportSearch;
use yii\web\Controller;

/**
 * Site controller
 */
class SiteController extends Controller
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

        $indexDependencyTags = [
            Cargo::tableName(),
            Transport::tableName(),
            Payment::tableName()
        ];
        if($location) {
            $indexDependencyTags = [
                Cargo::tableName().'-from-'.$location->getCode(),
                Cargo::tableName().'-to-'.$location->getCode(),
                Transport::tableName().'-from-'.$location->getCode(),
                Transport::tableName().'-to-'.$location->getCode(),
                Payment::tableName()
            ];
        }

        return [
            [
                // https://ru.yougile.com/board/u5qm752atccf#chat:b8d8b504a047
                'class' => NoSubdomain::class
            ],

            [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest,// && !LocationHelper::getCityFromDomain(),
                'only' => ['index'],
                'duration' => 3600,
                'dependency' => new TagDependency(['tags' => $indexDependencyTags]),
                'variations' => [
                    $location ? $location->getCode() : null
                ]
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ]
        ];
    }

    /**
     * @param LocationInterface $location
     * @return string|Response
     * @throws LoaderError
     */
    public function actionIndex(LocationInterface $location = null)
    {
        ///////////////////
        // Редирект на папки для старой версии
        /** @var $dCity LocationInterface */
        // https://ru.yougile.com/board/u5qm752atccf#chat:b8d8b504a047
    /*    $dCity = Yii::$app->getBehavior('geo')->domainCity;
        if($dCity){
            // проверка на наличие контента
          /* https://ru.yougile.com/board/u5qm752atccf#chat:a06dc215ab8e
            if(!$this->matrixContentService->isEnoughContentAnyDirection('main', $dCity)){
                throw new NotFoundHttpException();
            }*/

            // Редирект поддоменов на главный сайт
      /*      $url = 'https://' . Yii::getAlias('@domain') . '/' . $dCity->code . '/';
            return Yii::$app->getResponse()->redirect($url, 301, false);
        }*/
        //////////////////

        // проверка на наличие контента
        /* https://ru.yougile.com/board/u5qm752atccf#chat:a06dc215ab8e
        if($location && !$this->matrixContentService->isEnoughContentAnyDirection('main', $location)){
            throw new NotFoundHttpException();
        }*/

        /////////////////////
        /// Поиск
        $transportDataProvider = (new TransportSearch())
            ->setLocationFrom($location)
            ->setDirection(true)
            ->setSortPatternType(TransportSearch::SORT_PATTERN_MAIN_WITHOUT_CATEGORY)
            ->setPageSize(8)
            ->search();

        $tkDataProvider = (new TkSearch())
            ->setLocationFrom($location)
            ->setPageSize(8)
            ->search();

        $cargoDataProvider = (new CargoSearch())
            ->setLocationFrom($location)
            ->setDirection(true)
            ->setPageSize(8)
            ->search();
        /////////////////////////

        /////////////////////
        /// Статьи
        $articleDataProvider = null;
        if(is_null($location)) {
            $arSearchModel = new ArticlesSearch();
            $arSearchModel->pageSize = 4;
            $articleDataProvider = $arSearchModel->search();
        }
        ////////////////////

        /////////////////////////////////////
        // Формирование breadcrumbs
        if($location) {
            if ($location instanceof City && $location->region) {
                $this->view->params['breadcrumbs'][] = [
                    'label' => $location->region->getTitle(),
                    'url' => Url::toRoute([
                        '/cargo/transportation/search2',
                        'location' => $location->region
                    ])
                ];
            }

            $this->view->params['breadcrumbs'][] = [
                'label' => $location->getTitle()
            ];

            // Ссылки следующая и предыдущая
            $nextLocation = $this->getNextLocation($location);
            $prevLocation = $this->getPrevLocation($location);
            if ($nextLocation) {
                $this->view->params['navlinks']['next'] = Url::toRoute([
                    '/cargo/transportation/search2',
                    'location' => $nextLocation
                ]);
            }
            if ($prevLocation) {
                $this->view->params['navlinks']['prev'] = Url::toRoute([
                    '/cargo/transportation/search2',
                    'location' => $prevLocation
                ]);
            }
        }
        //////////////////////////////////

        $mainPageCategories = $regionCities = $rootCategories = null;
        $isMainPage = is_null($location);
        if($isMainPage || $location) {
            $root_cat = ['gruzoperevozki', 'pereezd', 'arenda-avto', 'arenda-spectehniki', 'vyvoz'];
            $mainPageCategories = CargoCategory::find()
                ->where(['slug' => $root_cat])
                ->orderBy(new Expression('FIELD(slug, "' . implode('","', $root_cat) . '")'))
                ->all();

            if($location instanceof City){
                $rootCategories = $mainPageCategories;
                $mainPageCategories = null;
            }

            if($location instanceof Region){
                // Строим список городов в регионе
                $regionCities = FastCity::find()
                    ->where(['regionid' => $location->getId()])
                    ->all();
            }
        }

        $this->layout = "@app/views/layouts/main_page/main";
        return $this->render('index', [
            'transportDataProvider' => $transportDataProvider,
            'tkDataProvider' => $tkDataProvider,
            'cargoDataProvider' => $cargoDataProvider,
            'articlesDataProvider' => $articleDataProvider,
            'pageTpl' => TemplateHelper::get('main', $location, null, [
                'count_service' => $cargoDataProvider->totalCount + $transportDataProvider->totalCount,
                'price_from' => 0
            ]),
            'mainPageCategories' => $mainPageCategories,
            'rootCategories' => $rootCategories,
            'location' => $location,
            'regionCities' => $regionCities,
            'isMainPage' => $isMainPage,
            'matrixContentService' => $this->matrixContentService
        ]);
    }

    /**
     * Вход под другим пользователем из под админки
     * После авторизации делаем редирект на главную страницу
     */
    public function actionAuthByAdmin()
    {
        return $this->redirect('/');
    }

    /**
     * Получаем access_token по коду для публикации грузов в сторонние сообщества
     * @param $code
     */
    public function actionVk($code='')
    {
        $redirect_uri = 'https://oauth.vk.com/blank.html';
       // $redirect_uri = Yii::$app->request->hostInfo . '/' . Yii::$app->request->pathInfo;
        if($code){
            $oauth = new VKOAuth();
            $client_id = (int)Yii::$app->session->get('app_id');
            $client_secret = (string)Yii::$app->session->get('private_key');

            $response = $oauth->getAccessToken($client_id, $client_secret, $redirect_uri, $code);
            print_r($response);
            die;
        }

        $model = new VkAppForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            Yii::$app->session->set('app_id', $model->app_id);
            Yii::$app->session->set('private_key', $model->private_key);

            $oauth = new VKOAuth();
            $scope = [
                VKOAuthUserScope::WALL,
                VKOAuthUserScope::OFFLINE
                //VKOAuthUserScope::GROUPS
            ];

            $browser_url = $oauth->getAuthorizeUrl(
                VKOAuthResponseType::TOKEN,
                (int)$model->app_id,
                $redirect_uri,
                VKOAuthDisplay::PAGE,
                $scope
            );

            return $this->redirect($browser_url);
        }
        return $this->render('vk_app',[
            'model' => $model
        ]);
    }

    /**
     * Получаем следующую категорию
     */
    private function getNextLocation(LocationInterface $location = null): ?LocationInterface
    {
        $locations = [];
        if($location instanceof City){
            $locations = FastCity::find()->where(['>', 'cityid', $location->getId()])->all();
        }elseif($location instanceof Region){
            $locations = Region::find()->where(['>', 'id', $location->getId()])->all();
        }

        foreach($locations as $location){
            if($location instanceof FastCity) $location = $location->city;

            if($this->matrixContentService->isEnoughContentAnyDirection('main', $location))
                return $location;
        }

        return null;
    }

    /**
     * Получаем предыдущую категорию
     */
    private function getPrevLocation(LocationInterface $location = null): ?LocationInterface
    {
        $locations = [];
        if($location instanceof City){
            $locations = FastCity::find()->where(['<', 'cityid', $location->getId()])->orderBy(['id' => SORT_DESC])->all();
        }elseif($location instanceof Region){
            $locations = Region::find()->where(['<', 'id', $location->getId()])->orderBy(['id' => SORT_DESC])->all();
        }

        foreach($locations as $location){
            if($location instanceof FastCity) $location = $location->city;

            if($this->matrixContentService->isEnoughContentAnyDirection('main', $location))
                return $location;
        }

        return null;
    }
}
