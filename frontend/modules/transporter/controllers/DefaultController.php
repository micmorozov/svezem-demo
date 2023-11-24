<?php

namespace frontend\modules\transporter\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\LocationHelper;
use common\models\CargoCategory;
use common\models\FetchPhoneLog;
use common\models\Profile;
use common\models\Transport;
use common\models\TransporterTags;
use common\models\User;
use frontend\widgets\phoneButton\FetchPhoneAction;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\filters\AccessControl;
use yii\filters\PageCache;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['view'],
                'rules' => [
                    [
                        'actions' => ['view'],
                        'allow' => true,
                    ],
                ],
            ],
            'nosubdomain' => [
                'class' => NoSubdomain::class,
                'only' => ['view2']
            ],

            'pageCache' => [
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

    public function actions()
    {
        return [
            'fetch-phone' => [
                'class' => FetchPhoneAction::class,
                'object' => FetchPhoneLog::OBJECT_TRANSPORTER
            ]
        ];
    }

    public function actionView($id)
    {
        $profile = Profile::find()
            ->joinWith('transport')
            ->joinWith('city')
            ->joinWith([
                'createdBy' => function ($q){
                    /** @var $q ActiveQuery */
                    $q->alias('user')
                        ->where([
                            'user.status' => [
                                User::STATUS_ACTIVE,
                                User::STATUS_PENDING
                            ]
                        ]);
                }
            ])
            ->where([
                'and',
                ['profile.id' => $id],
                ['not', ['type' => Profile::TYPE_SENDER]]
            ])
            ->one();


        if ($profile !== null) {

            //////////////////////////////////
            // РЕДИРЕКТ НА НОВУЮ СТРУКТУРУ
            // Гет параметры надо тоже отправить в редиректе
            $route = array_merge(Yii::$app->request->queryParams, ['/transporter/default/view2', 'id' => $id, 'slug' => $profile->slug]);

            return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') . Url::toRoute($route), 301, false);
            //////////////////////////////////

            $cityCode = $profile->city->code;

            if ( !$cityCode) {
                throw new InvalidArgumentException('Не определен поддомен перевозчика');
            }

            // Проверяем, что $city равен тому что в БД, иначе редирект 301
            $cityTransporterSlug = $cityCode;
            $domainCity = LocationHelper::getCityFromDomain();

            //если не совпадает город в поддомене или slug
            //делаем редирект
            if ($cityTransporterSlug != $domainCity) {
                return $this->redirect(Url::toRoute([
                    '/transporter/default/view',
                    'id' => $id,
                    'city' => $cityTransporterSlug
                ]), 301);
            }

            //ОБЪЯВЛЕНИЯ ПЕРЕВОЗЧИКА
            $transportsQuery = Transport::find()
                ->with(['fullCargoCategories', 'cityFrom', 'cityTo'])
                ->where([
                    'and',
                    ['profile_id' => $profile->id],
                    ['status' => Transport::STATUS_ACTIVE]
                ]);

            $pageSize = Yii::$app->session->get('per-page', Yii::$app->params['itemsPerPage']['defaultPageSize']);
            $trProvider = new ActiveDataProvider([
                'query' => $transportsQuery,
                'pagination' => [
                    'defaultPageSize' => $pageSize,
                    'forcePageParam' => false
                ],
            ]);

            //////////////////////
            //Похожие предложения
            $trCat = [];
            foreach($transportsQuery->all() as $tr){
                $trCat = array_merge($trCat, $tr->cargoCategoryIds);
            }

            // Ищем категории транспорта пользователя
            $similarOffer = Transport::find()
                ->joinWith(['fullCargoCategories fcc'], true)
                ->where([
                    'and',
                    ['city_from' => $profile->city_id],
                    ['status' => Transport::STATUS_ACTIVE]
                ])
                ->andWhere(['in', 'fcc.id', $trCat])
                ->andWhere(['<>', 'profile_id', $profile->id]) // Исключаем объявления данного перевозчика
                ->orderBy(['id' => SORT_DESC]);


            $similarProvider = new ActiveDataProvider([
                'query' => $similarOffer,
                'pagination' => [
                    'defaultPageSize' => 10,
                    'forcePageParam' => false
                ]
            ]);
            //////////////////////////////

            $this->view->params['breadcrumbs'][] = [
                'label' => 'Поиск перевозчиков',
                'url' => Url::toRoute('/transport/default/search')
            ];
            $this->view->params['breadcrumbs'][] = [
                'label' => "Предложение #{$profile->id}"
            ];

            // Ссылки следующая и предыдущая
            $nextProfile = $profile->getNext();
            $prevProfile = $profile->getPrev();
            if($nextProfile) $this->view->params['navlinks']['next'] = Url::toRoute(['/transporter/default/view', 'id' => $nextProfile->id, 'city' => $nextProfile->city->code]);
            if($prevProfile) $this->view->params['navlinks']['prev'] = Url::toRoute(['/transporter/default/view', 'id' => $prevProfile->id, 'city' => $prevProfile->city->code]);
            ///////////////

            return $this->render('view', [
                'profile' => $profile,
                'trProvider' => $trProvider,
                'similarProvider' => $similarProvider,
                'tags' => TransporterTags::find()->where(['profile_id' => $profile->id])->all()
            ]);
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }
    }

    public function actionView2($id, $slug)
    {
        $profile = Profile::find()
            ->joinWith('transport')
            ->joinWith('city')
            ->joinWith([
                'createdBy' => function ($q){
                    /** @var $q ActiveQuery */
                    $q->alias('user')
                        ->where([
                            'user.status' => [
                                User::STATUS_ACTIVE,
                                User::STATUS_PENDING
                            ]
                        ]);
                }
            ])
            ->where([
                'and',
                ['profile.id' => $id],
                ['not', ['type' => Profile::TYPE_SENDER]]
            ])
            ->one();

        if (!$profile)
            throw new NotFoundHttpException('Запрашиваемая страница не найдена');

        //если не совпадает slug делаем редирект
        if ($profile->slug != $slug) {

            return Yii::$app->getResponse()->redirect(Url::toRoute([
                '/transporter/default/view2',
                'id' => $id,
                'slug' => $profile->slug
            ]), 301, false);
        }

        //ОБЪЯВЛЕНИЯ ПЕРЕВОЗЧИКА
        $transportsQuery = Transport::find()
            ->with(['fullCargoCategories', 'cityFrom', 'cityTo'])
            ->where([
                'and',
                ['profile_id' => $profile->id],
                ['status' => Transport::STATUS_ACTIVE]
            ]);

        $pageSize = Yii::$app->session->get('per-page', Yii::$app->params['itemsPerPage']['defaultPageSize']);
        $trProvider = new ActiveDataProvider([
            'query' => $transportsQuery,
            'pagination' => [
                'defaultPageSize' => $pageSize,
                'forcePageParam' => false
            ],
        ]);

        //////////////////////
        //Похожие предложения
        $trCat = [];
        foreach($transportsQuery->all() as $tr){
            $trCat = array_merge($trCat, $tr->cargoCategoryIds);
        }

        // Ищем категории транспорта пользователя
        $similarOffer = Transport::find()
            ->joinWith(['fullCargoCategories fcc'], true)
            ->where([
                'and',
                ['city_from' => $profile->city_id],
                ['status' => Transport::STATUS_ACTIVE]
            ])
            ->andWhere(['in', 'fcc.id', $trCat])
            ->andWhere(['<>', 'profile_id', $profile->id]) // Исключаем объявления данного перевозчика
            ->orderBy(['id' => SORT_DESC]);


        $similarProvider = new ActiveDataProvider([
            'query' => $similarOffer,
            'pagination' => [
                'defaultPageSize' => 10,
                'forcePageParam' => false
            ]
        ]);
        //////////////////////////////


        $this->view->params['breadcrumbs'][] = [
            'label' => 'Поиск перевозчиков',
            'url' => Url::toRoute('/transport/default/search')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Предложение #{$profile->id}"
        ];

        // Ссылки следующая и предыдущая
        $nextProfile = $profile->getNext();
        $prevProfile = $profile->getPrev();
        if($nextProfile) $this->view->params['navlinks']['next'] = Url::toRoute(['/transporter/default/view2', 'id' => $nextProfile->id, 'slug' => $nextProfile->slug]);
        if($prevProfile) $this->view->params['navlinks']['prev'] = Url::toRoute(['/transporter/default/view2', 'id' => $prevProfile->id, 'slug' => $prevProfile->slug]);
        ///////////////

        return $this->render('view', [
            'profile' => $profile,
            'trProvider' => $trProvider,
            'similarProvider' => $similarProvider,
            'tags' => TransporterTags::find()->where(['profile_id' => $profile->id])->orderBy(['count' => SORT_DESC])->limit(12)->all()
        ]);
    }
}
