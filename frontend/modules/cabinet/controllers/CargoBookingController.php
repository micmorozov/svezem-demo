<?php

namespace frontend\modules\cabinet\controllers;

use common\components\bookingService\Service;
use common\helpers\PhoneHelpers;
use common\models\Cargo;
use frontend\modules\cabinet\components\Cabinet;
use frontend\modules\cabinet\models\CargoBookingSearch;
use frontend\modules\subscribe\models\Subscribe;
use frontend\modules\subscribe\models\SubscribeRules;
use libphonenumber\NumberParseException;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\di\NotInstantiableException;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class CargoBookingController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index']
                    ],
                    [
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            $bookingService = new Service(Yii::$app->user->id);
                            return $bookingService->canBooking();
                        }
                    ]
                ]
            ],
            'nosubdomain' => [
                'class' => 'common\behaviors\NoSubdomain',
                'except' => ['booking', 'book-status', 'cancel', 'save', 'edit', 'comment-save']
            ]
        ];
    }

    public function actionIndex()
    {
        $bookingService = new Service(Yii::$app->user->id);

        $hasBooking = $bookingService->canBooking();

        if( !$hasBooking ){
            return $this->render('aboutBooking');
        }

        $searchModel = new CargoBookingSearch();

        $queryParams = Yii::$app->request->queryParams;

        // Если параметры фильтра не установлены, берем данные из настройки рассылки текущего пользователя
        // Ими запролняем фильтр
       /* if ( !$queryParams) {
            $subs = Subscribe::findOne(['userid' => Yii::$app->user->id]);
            if ($subs) {
                $rList = SubscribeRules::findAll([
                    'subscribe_id' => $subs->id,
                    'status' => SubscribeRules::STATUS_ACTIVE
                ]);
                $cargoCategoryIds = [];
                foreach ($rList as $rule) {
                    $cargoCategoryIds = array_merge($cargoCategoryIds, $rule->getCategoriesId());
                }
                $queryParams['CargoBookingSearch']['cargoCategoryIds'] = $cargoCategoryIds;
            }
        }*/

        $dataProvider = $searchModel->search($queryParams);

        $openFilter = !empty($queryParams);

        $bookingFilterModel = new CargoBookingSearch();

        $bookingFilterModel->load($queryParams);

        $bookingFilterModel->allCargo = true;
        $new = $bookingFilterModel->searchQuery()->count();

        $bookingFilterModel->status = Cargo::STATUS_WORKING;
        $working = $bookingFilterModel->getFilterQuery()->count();

        $bookingFilterModel->status = Cargo::STATUS_DONE;
        $done = $bookingFilterModel->getFilterQuery()->count();

        // Вкладку "Общие заказы" ограничиваем по количеству моделей
        $maxAllItems = 150; // Количество элементов на вкладке "Общие заказы"
        if ($maxAllItems < $new) {
            $new = $maxAllItems;
            if ($searchModel->allCargo) {
                $dataProvider->totalCount = $maxAllItems;
            }
        }
        ///////////

        $filters = [
            'main' => [
                [
                    'name' => 'Общие заказы',
                    'icon' => '<i style="font-size:18px" class="fas fa-list-alt"></i>',
                    'url' => Url::to(['/cabinet/cargo-booking/index',
                        'locationFrom' => $searchModel->locationFrom,
                        'locationTo' => $searchModel->locationTo,
                        'cargoCategoryIds' => $searchModel->cargoCategoryIds
                    ]),
                    'count' => $new,
                    'select' => !$searchModel->allCargo && !$searchModel->status
                ],
                [
                    'name' => 'В работе',
                    'icon' => '<i style="font-size:18px" class="fas fa-clipboard-list"></i>',
                    'url' => Url::to(['/cabinet/cargo-booking/index',
                        'locationFrom' => $searchModel->locationFrom,
                        'locationTo' => $searchModel->locationTo,
                        'cargoCategoryIds' => $searchModel->cargoCategoryIds,
                        'status' => Cargo::STATUS_WORKING
                    ]),
                    'count' => $working,
                    'select' => $searchModel->status == Cargo::STATUS_WORKING
                ],
                [
                    'name' => 'Выполненные',
                    'icon' => '<i style="font-size:18px" class="fas fa-clipboard-check"></i>',
                    'url' => Url::to(['/cabinet/cargo-booking/index',
                        'locationFrom' => $searchModel->locationFrom,
                        'locationTo' => $searchModel->locationTo,
                        'cargoCategoryIds' => $searchModel->cargoCategoryIds,
                        'status' => Cargo::STATUS_DONE
                    ]),
                    'count' => $done,
                    'select' => $searchModel->status == Cargo::STATUS_DONE
                ]
            ]
        ];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'openFilter' => $openFilter,
            'filters' => $filters,
            'bookingService' => $bookingService
        ]);
    }

    /**
     * @param Cargo $cargo
     * @return array
     * @throws NumberParseException
     * @throws \common\components\bookingService\Exceptions\Exception
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    protected function cargoBookStatus(Cargo $cargo)
    {
        $status = 'not_active';
        $phone = '';
        $phone_format = '';
        $comment = '';
        $price = '';

        $bookingService = new Service(Yii::$app->user->id);
        $bookingCount = $bookingService->getBookingRemain();

        //груз уже взят в броню
        if ($cargo->status == Cargo::STATUS_WORKING && $cargo->booking_by == Yii::$app->user->id) {
            $status = 'working';
            $phone = $cargo->profile->contact_phone;
            $phone_format = PhoneHelpers::formatter($cargo->profile->contact_phone, '');
            $comment = $cargo->bookingCommentByUser(Yii::$app->user->id);
        }

        //груз уже забронирован
        if ($cargo->status == Cargo::STATUS_DONE && $cargo->booking_by == Yii::$app->user->id) {
            $status = 'done';
            $phone = $cargo->profile->contact_phone;
            $phone_format = PhoneHelpers::formatter($cargo->profile->contact_phone, '');
            $price = $cargo->booking_price;
            $comment = $cargo->bookingCommentByUser(Yii::$app->user->id);
        }

        if ($cargo->status == Cargo::STATUS_ACTIVE && !$cargo->isExpired) {
            $status = 'active';
            $comment = $cargo->bookingCommentByUser(Yii::$app->user->id);
        }

        return [
            'status' => $status,
            'phone' => $phone,
            'phone_format' => $phone_format,
            'price' => $price,
            'comment' => $comment,
            'bookingCount' => $bookingCount
        ];
    }

    public function actionBookStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cargo = Cargo::findOne($id);

        return $this->cargoBookStatus($cargo);
    }

    public function actionBooking()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cargo_id = Yii::$app->request->post('cargo_id');
        $web = Yii::$app->request->post('web');

        $result = Cabinet::booking($cargo_id);

        if (isset($result['error'])) {
            return [
                'error' => $result['error']
            ];
        } else {
            if ($web) {
                return [
                    'html' => $this->renderPartial('_price_block', [
                        'model' => $result
                    ])
                ];
            }

            return $this->cargoBookStatus($result);
        }
    }

    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cargo_id = Yii::$app->request->post('cargo_id');
        $price = Yii::$app->request->post('price');
        $web = Yii::$app->request->post('web');

        $result = Cabinet::save($cargo_id, $price);

        if (isset($result['error'])) {
            return [
                'error' => $result['error']
            ];
        } else {
            if ($web) {
                return [
                    'html' => $this->renderPartial('_booking_done', [
                        'model' => $result
                    ])
                ];
            }

            return $this->cargoBookStatus($result);
        }
    }

    public function actionCancel()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cargo_id = Yii::$app->request->post('cargo_id');
        $web = Yii::$app->request->post('web');

        $result = Cabinet::cancel($cargo_id);

        if (isset($result['error'])) {
            return [
                'error' => $result['error']
            ];
        } else {
            if ($web) {
                return [
                    'html' => $this->renderPartial('_booking_btn', [
                        'model' => $result
                    ])
                ];
            }

            return $this->cargoBookStatus($result);
        }
    }

    public function actionEdit()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cargo_id = Yii::$app->request->post('cargo_id');
        $price = Yii::$app->request->post('price');
        $web = Yii::$app->request->post('web');

        $result = Cabinet::edit($cargo_id, $price);

        if (isset($result['error'])) {
            return [
                'error' => $result['error']
            ];
        } else {
            if ($web) {
                return [
                    'html' => $this->renderPartial('_booking_done', [
                        'model' => $result
                    ])
                ];
            }

            return $this->cargoBookStatus($result);
        }
    }

    /**
     * Сохраняем комментарий  кзаказу
     * @return array
     * @throws Exception
     */
    public function actionCommentSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cargo_id = Yii::$app->request->post('cargo_id');
        $comment = trim(Yii::$app->request->post('comment', ''));
        $web = Yii::$app->request->post('web');

        $comment = Cabinet::commentSave($cargo_id, $comment);

        if ($web) {
            return [
                'html' => $this->renderPartial('_comment', [
                    'cargo_id' => $cargo_id,
                    'comment' => $comment
                ])
            ];
        } else {
            return [
                'comment' => $comment
            ];
        }
    }
}
