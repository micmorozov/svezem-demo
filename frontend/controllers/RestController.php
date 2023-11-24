<?php

namespace frontend\controllers;

use common\components\bookingService\Service;
use common\components\version\Version;
use common\helpers\LocationHelper;
use common\models\City;
use common\models\FastCity;
use common\models\LocationInterface;
use Yii;
use yii\filters\ContentNegotiator;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class RestController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ]
        ];
    }

    public function actionAppData()
    {
        $location = LocationHelper::getCurrentLocation();
        $yourCityName = $location ? $location->getTitle() : '(не определен)';

        $curCityId = ($location instanceof City) ? $location->getId() : null;

        $cabinetMenu = [];

       // $bookinService = new Service(Yii::$app->user->id);

      //  $cargoBooking = $bookinService->canBooking();

        if ( !Yii::$app->user->isGuest) {
            $cabinetMenu = [
                [
                    'url' => "https://".Yii::getAlias('@domain').Url::toRoute('/cargo/default/mine/'),
                    'title' => 'Мои грузы'
                ],
                [
                    'url' => "https://".Yii::getAlias('@domain').Url::toRoute('/transport/default/mine/'),
                    'title' => 'Мой транспорт',
                    'separator' => 1
                ],
                [
                    'url' => "https://".Yii::getAlias('@domain').Url::toRoute('/cabinet/cargo-booking/'),
                    'title' => 'Бронирование заказов',
                    'separator' => 1
                ],
                [
                    'url' => "https://".Yii::getAlias('@domain').Url::toRoute('/info/paid-services/'),
                    'title' => 'Платные услуги'
                ],
                [
                    'url' => "https://".Yii::getAlias('@domain').Url::toRoute('/cabinet/payment/history/'),
                    'title' => 'История операций',
                    'separator' => 1
                ],
                [
                    'url' => "https://".Yii::getAlias('@domain').Url::toRoute('/cabinet/settings/'),
                    'title' => 'Мой профиль'
                ]
            ];
        }

        $js = [];
        // ВНИМАНИЕ!! Подключение скриптов гугл перенесли в шаблон //common/_adsbygoogle.php
        // Показываем гугл на страницах
        // /article - Каталог статей, статья и список статей по категории
        // /cargo/[\d] - страница груза
        // /cargo/transportation/ - страница вида перевозки
        // /intercity/ - страница перевозки между городами
        // /transporter/[\d] - страница перевозчика
        // /tk/[\d] - страница ТК
        //if(preg_match('/(\/article)|(\/cargo\/[\d])|(\/cargo\/transportation\/$)|(\/cargo\/success-create)|(\/intercity\/$)|(\/transporter\/[\d])|(\/tk\/[\d])/', Yii::$app->request->referrer))
          //  array_push($js, ['src'=>'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', 'data' => ['adClient' => 'ca-pub-5125116180416717']]);

        $selectedDomain = ($location instanceof LocationInterface) ? $location->getCode() : '';
        $selectedDomain = 'https://' . Yii::getAlias('@domain') . '/' . ($selectedDomain ? $selectedDomain.'/' : '');
        return [
            'vue' => [
                'isGuest' => Yii::$app->user->isGuest,
                'yourCity' => $yourCityName,
               // 'cargoBooking' => $cargoBooking,
                'cabinetMenu' => $cabinetMenu,
                'mainDomain' => Yii::getAlias('@domain'),
                'selectedDomain' => $selectedDomain
            ],
            'app' => [
                'csrf' => Yii::$app->request->csrfToken
            ],
            'cargoForm' => [
                'id' => $curCityId,
                'title' => $curCityId ? $location->getFullTitle() : ''
            ],
            'js' => $js
        ];
    }
}
