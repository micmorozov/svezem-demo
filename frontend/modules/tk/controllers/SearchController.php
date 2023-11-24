<?php

namespace frontend\modules\tk\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\CityHelper;
use common\helpers\TemplateHelper;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\Region;
use common\models\TkSearchTags;
use frontend\modules\tk\models\TkSearch;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class SearchController extends Controller
{
    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    public function actionIndex(LocationInterface $location = null, $slug = null)
    {
        ///////////////////
        // Редирект на папки для старой версии
        /** @var $dCity LocationInterface */
        $dCity = Yii::$app->getBehavior('geo')->domainCity;
        if($dCity){
            return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') .
                Url::toRoute(['/tk/search/index', 'location' => $dCity, 'slug' => $slug]), 301, false);
        }
        //////////////////


        $searchModel = (new TkSearch())
            //если нет параметров поиска и есть поддомен,
            //то ищим тк, в котором присутсвует город поддомена
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

        $category = null;
        $tplSubName = '';

        if ($slug) {
            $filter = TkSearchTags::findOne(['slug' => $slug/*, 'domain_id' => $domainCityId*/]);

            if ( !$filter) {
                throw new NotFoundHttpException('Страница не найдена');
            }

            $category = $filter->category;
            $tplSubName = '-category';

            //формируем параметры поиска
            $searchModel
                ->setLocationFrom($filter->cityFrom)
                ->setCargoCategories($category);
        } else {
            $page = $searchModel->getPage();
            if($page > 1) {
                $this->view->params['breadcrumbs'][] = [
                    'label' => 'Поиск транспортных компаний',
                    'url' => Url::toRoute(['/tk/search/index', 'location'=>$location])
                ];
                $this->view->params['breadcrumbs'][] = [
                    'label' => "Страница {$page}"
                ];
            }else {
                $this->view->params['breadcrumbs'][] = [
                    'label' => 'Поиск транспортных компаний'
                ];
            }
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

        $this->view->params['breadcrumbs_show'] = true;

        return $this->render('search', [
            'tkSearch' => $searchModel,
            'dataProvider' => $dataProvider,
            //'tags' => $tagsQuery->limit(10)->all(),
            'pageTpl' => TemplateHelper::get("tk-search{$tplSubName}-view", $location, $category, [
                'count_service' => $dataProvider->totalCount
            ]),
            'showPagination' => $showPagination
        ]);
    }

    /**
     * Отображение ссылок фильтра
     * @return string
     */
    public function actionAll()
    {
        $curcity = Yii::$app->getBehavior('geo')->domainCity;

        $this->layout = '@frontend/views/layouts/main.php';
        return $this->render('all', [
            'tags' => TkSearchTags::find()->where(['domain_id' => $curcity ? $curcity->id : null])->all(),
            'matrixContentService' => $this->matrixContentService
        ]);
    }
}
