<?php

namespace frontend\modules\cargo\controllers;

use common\helpers\LocationHelper;
use common\helpers\Convertor;
use common\helpers\SlugHelper;
use common\helpers\TemplateHelper;
use common\models\Cargo;
use common\models\CargoImage;
use common\models\CargoTags;
use common\models\Deal;
use common\models\FastCity;
use common\models\FetchPhoneLog;
use common\models\LocationInterface;
use Exception;
use frontend\modules\cargo\models\CargoOwnerSearch;
use frontend\modules\cargo\models\CargoPassing;
use frontend\modules\tk\models\TkSearch;
use frontend\modules\transport\models\TransportSearch;
use frontend\widgets\phoneButton\FetchPhoneAction;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\PageCache;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post']
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => [
                    'update',
                    'delete',
                    'view',
                    'fetch-phone',
                    'mine'
                ],
                'rules' => [
                    [
                        'actions' => [
                            'update',
                            'delete',
                            'fetch-phone',
                            'mine'
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['view'],
                        'allow' => true,
                    ],
                ]
            ],

            [
                'class' => 'common\behaviors\NoSubdomain',
                'only' => ['passing', 'mine', 'view2']
            ],

            [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest && !LocationHelper::getCityFromDomain(),
                'only' => ['view2'],
                'duration' => 86400,
                'variations' => [
                    Yii::$app->request->get('id')
                ]
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['fetch-phone'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actions()
    {
        return [
            'shortcreate' => [
                'class' => 'frontend\modules\cargo\widgets\actions\CreateAction'
            ],
            'fetch-phone' => [
                'class' => FetchPhoneAction::class,
                'object' => FetchPhoneLog::OBJECT_CARGO
            ]
        ];
    }

    /**
     * Редирект с короткого урла
     *
     * @param $id
     * @return Response
     */
    public function actionViewRedir($id)
    {
        return $this->redirect(Url::toRoute(['view', 'id' => $id]), 301);
    }

    /**
     * @param $id - ИД груза
     * @param $slug -  ЧПУ
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionView($id)
    {
        $this->layout = '@frontend/views/layouts/main.php';

        /* @var $model Cargo */
        $model = Cargo::find()->where([
            'AND',
            ['id' => $id],
            ['<>', 'status', Cargo::STATUS_BANNED]
        ])
            ->with(['profile', 'cargoCategory'])
            ->one();
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');
        }

        //////////////////////////////////
        // РЕДИРЕКТ НА НОВУЮ СТРУКТУРУ
        // Гет параметры надо тоже отправить в редиректе
        $route = array_merge(Yii::$app->request->queryParams, ['/cargo/default/view2', 'id' => $id, 'slug' => $model->slug]);
        return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') . Url::toRoute($route), 301, false);
        //////////////////////////////////


        /** @var LocationInterface $city */
        $city = LocationHelper::getCityFromDomain();

        // Проверяем, что $city равен тому что в БД, иначе редирект 301
        $cityFromCargo = $model->cityFrom->code;

        if ( !$cityFromCargo) {
            throw new InvalidArgumentException('Не определен поддомен груза');
        }

        //если не совпадает город в поддомене
        //то делаем редирект на корректные данные
        if ($city && $cityFromCargo != $city->getCode()) {
            // Гет параметры надо тоже отправить в редиректе
            $route = array_merge(['/cargo/default/view', 'id' => $id, 'city' => $cityFromCargo], Yii::$app->request->queryParams);

            return Yii::$app->getResponse()->redirect(Url::toRoute($route), 301, false);
        }

        // Считаем количество просмотров
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->id != $model->created_by) {
            $model->updateCounters(['views_count' => 1]);
        }

        // Хлебные крошки
        $this->view->params['breadcrumbs'][] = [
            'label' => 'Поиск грузов',
            'url' => Url::toRoute('/cargo/default/search')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Заказ #{$model->id}"
        ];

        // Ссылки следующая и предыдущая
        $nextCargo = $model->getNext($model->cityFrom->id);
        $prevCargo = $model->getPrev($model->cityFrom->id);
        if($nextCargo) $this->view->params['navlinks']['next'] = Url::toRoute(['/cargo/default/view', 'id' => $nextCargo->id, 'city' => $nextCargo->cityFrom->code]);
        if($prevCargo) $this->view->params['navlinks']['prev'] = Url::toRoute(['/cargo/default/view', 'id' => $prevCargo->id, 'city' => $prevCargo->cityFrom->code]);
        ///////////////

        return $this->render('view', [
            'cargo' => $model,
            'tags' => CargoTags::findAll(['cargo_id' => $id]),
            'passing' => $this->actionPassingItems($id)
        ]);
    }

    /**
     * @param $id - ИД груза
     * @param $slug -  ЧПУ
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionView2($id, $slug)
    {
        $this->layout = '@frontend/views/layouts/main.php';

        /* @var $model Cargo */
        $model = Cargo::find()->where([
            'AND',
            ['id' => $id],
            ['<>', 'status', Cargo::STATUS_BANNED]
        ])
            ->with(['profile', 'cargoCategory'])
            ->one();
        if ($model == null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');
        }

        // сверяем slug
        if($model->slug != $slug){
            // Гет параметры надо тоже отправить в редиректе
            $route = array_merge(Yii::$app->request->queryParams, ['/cargo/default/view2', 'id' => $id, 'slug' => $model->slug]);

            return $this->redirect(Url::toRoute($route), 301);
        }

        // Считаем количество просмотров
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->id != $model->created_by) {
            $model->updateCounters(['views_count' => 1]);
        }

        // Хлебные крошки
        $this->view->params['breadcrumbs'][] = [
            'label' => 'Поиск грузов',
            'url' => Url::toRoute('/cargo/default/search')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Заказ #{$model->id}"
        ];

        // Ссылки следующая и предыдущая
        $nextCargo = $model->getNext($model->cityFrom);
        $prevCargo = $model->getPrev($model->cityFrom);
        if($nextCargo) $this->view->params['navlinks']['next'] = Url::toRoute(['/cargo/default/view2', 'id' => $nextCargo->id, 'slug' => $nextCargo->slug]);
        if($prevCargo) $this->view->params['navlinks']['prev'] = Url::toRoute(['/cargo/default/view2', 'id' => $prevCargo->id, 'slug' => $prevCargo->slug]);
        ///////////////

        return $this->render('view', [
            'cargo' => $model,
            'tags' => CargoTags::findAll(['cargo_id' => $id]),
            'passing' => $this->actionPassingItems($id)
        ]);
    }

    /**
     * @param $id
     * @return array|string|Response
     * @throws NotFoundHttpException
     * @throws ErrorException
     */
    public function actionUpdate($id)
    {
        $model = Cargo::findOne([
            'id' => $id,
            'created_by' => Yii::$app->user->id
        ]);

        if ( !$model) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');
        }

        if ( !in_array($model->status, [Cargo::STATUS_ACTIVE, Cargo::STATUS_ARCHIVE])) {
            Yii::$app->session->setFlash('warning', 'Редактирование данной заявки невозможно.');
            return $this->redirect(['/cargo/mine']);
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            // Если модель была удалена, то после редактирования ее восстанавливаем
            $model->status = Cargo::STATUS_ACTIVE;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Данные сохранены.');
                return $this->redirect(['/cargo/mine']);
            }
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    /**
     * Deletes an existing Cargo model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param $id
     * @return Response
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionDelete($id)
    {
        $msg = Yii::$app->request->post('msg');

        $model = $this->findModel($id);
        if ($model->created_by != Yii::$app->user->identity->id) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');
        }
        $model->status = Cargo::STATUS_ARCHIVE;
        $model->delete_reason = $msg;
        $model->save();

        Yii::$app->session->setFlash('success', 'Ваш груз успешно удален');

        return $this->redirect(['/cargo/mine']);
    }

    /**
     * Finds the Cargo model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Cargo the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Cargo::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');
        }
    }

    public function actionPassing()
    {
        $model = new CargoPassing();

        /** @var FastCity $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;

        if ( !empty(Yii::$app->request->queryParams)) {
            $dataProvider = $model->search(Yii::$app->request->queryParams);
        }

        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'model' => $model,
            'data' => [
                'ip' => Yii::$app->request->remoteIP,
                'userid' => (!Yii::$app->user->isGuest ? Yii::$app->user->id : Yii::$app->session->id),
                'domainId' => $domainCity->id ?? 0,
                'domainCode' => $domainCity ? $domainCity->code : 'main'
            ]
        ]);

        return $this->render('passing', [
            'model' => $model,
            'dataProvider' => isset($dataProvider) ? $dataProvider : null,
            'pageTpl' => TemplateHelper::get("cargo-search-passing-view", $domainCity)
        ]);
    }

    /**
     * Попутные грузы для указанного груза
     * @param $id
     * @return string
     */
    public function actionPassingItems($id)
    {
        $cargo = Cargo::findOne($id);

        $passingModel = new CargoPassing();
        $passingModel->excludeCargo = $cargo->id;

        $passingModel->city_from = $cargo->city_from;
        $passingModel->city_to = $cargo->city_to;
        $passingModel->radius = 30;

        //ИД категорий текущего груза
        $cat_ids = array_map(function ($item){
            return $item->id;
        }, $cargo->moderCategories);

        $passingModel->cargoCategoryIds = $cat_ids;
        $query = $passingModel->passingQuery();

        $query->limit(10);

        // если нет попутных грузов показываем похожие
        if(!$query->all()){
            $query = Cargo::find()
                ->where(['AND',
                    ['status' => Cargo::STATUS_ACTIVE],
                    ['city_from' => $cargo->city_from]
                ]);
            if($cat_ids){
                $query->andWhere(['in', 'cargo_category_id', $cat_ids]);
            }

            $query->orderBy(['id' => SORT_DESC])
                ->limit(10)
                ->all();
        }

        //$models = $query->all();

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => 10,
                'forcePageParam' => false
            ],
        ]);
        /*return $this->renderPartial('passing-items', [
            'models' => $models
        ]);*/
    }

    public function actionCargoMap()
    {
        return Yii::$app->redisTemp->get('cargoMap');
    }

    public function actionCargoMapDetails($ids)
    {
        /** @var Cargo[] $cargos */
        $cargos = Cargo::find()->where(['id' => explode(',', $ids)])->all();

        $response = [];
        foreach ($cargos as $cargo) {
            $city = strtolower(SlugHelper::rus2translit($cargo->cityFrom->title_ru));
            $item = [
                'id' => $cargo->id,
                'category' => (isset($cargo->cargoCategory) ? $cargo->cargoCategory->category : ''),
                'cityFrom' => $cargo->cityFrom->title_ru,
                'cityFromId' => $cargo->cityFrom->id,
                'cityTo' => $cargo->cityTo->title_ru,
                'description' => $cargo->description,
                'icon' => $cargo->icon,
                'countyFrom' => $cargo->cityFrom->country->code,
                'countyTo' => $cargo->cityTo->country->code,
                'distance' => $cargo->distance ? Convertor::distance($cargo->distance) : false,
                'duration' => Convertor::time($cargo->duration),
                'link' => Url::toRoute(['/cargo/default/view', 'city' => $city, 'id' => $cargo->id])
            ];

            $response[] = $item;
        }

        $response = json_encode($response);

        return $response;
    }

    public function actionMine()
    {
        $query = Cargo::find()
            ->with(['cityFrom.country', 'cityTo.country', 'cargoCategory'])
            ->where(['created_by' => Yii::$app->user->id])
            ->andWhere(['status' => Cargo::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => Yii::$app->session->get('per-page',
                    Yii::$app->params['itemsPerPage']['defaultPageSize'])
            ]
        ]);

        return $this->render('mine', [
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionSuccessCreate($cargo_id)
    {
        $cargo = Cargo::findOne($cargo_id);

        if ( !$cargo) {
            throw new NotFoundHttpException('Страница не найдена');
        }

        // ======= Перевозчики =======
        $transportSearchModel = new TransportSearch();
        $transportSearchModel->pageSize = 5;
        $transportSearchModel->sortPatternType = TransportSearch::SORT_PATTERN_RECOMMENDATION;

        $queryParams['TransportSearch'] = [
            'locationFrom' => $cargo->city_from,
            'locationTo' => $cargo->city_to,
            'cargoCategoryIds' => $cargo->categoriesId
        ];

        $transportDataProvider = $transportSearchModel->search($queryParams);

        $transportSearchButtonUrl = Url::to([
            '/transport/default/search/',
            'TransportSearch[locationFrom]' => $cargo->city_from,
            'TransportSearch[locationTo]' => $cargo->city_to,
            'TransportSearch[cargoCategoryIds][]' => $cargo->cargo_category_id
        ]);

        // ======= ТК =======
        $tkSearchModel = new TkSearch();
        $tkSearchModel->pageSize = 5;

        $queryParams['TkSearch'] = [
            'locationFrom' => $cargo->city_from,
            'cargoCategoryIds' => [$cargo->cargo_category_id]
        ];

        $tkDataProvider = $tkSearchModel->search($queryParams);

        $tkSearchButtonUrl = Url::to([
            '/tk/default/search/',
            'TkSearch[locationFrom]' => $cargo->city_from,
            'TkSearch[cargoCategoryIds][]' => $cargo->cargo_category_id
        ]);

        return $this->render('success-create', [
            'transportDataProvider' => $transportDataProvider,
            'transportSearchButtonUrl' => $transportSearchButtonUrl,
            'tkDataProvider' => $tkDataProvider,
            'tkSearchButtonUrl' => $tkSearchButtonUrl,
            'cargo' => $cargo
        ]);
    }
}
