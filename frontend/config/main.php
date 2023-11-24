<?php

use frontend\behaviors\GeoBehavior;
use frontend\behaviors\InitBehavior;
use frontend\widgets\reviews\CommentModel;
use yii\web\AssetConverter;
use yii2mod\comments\controllers\DefaultController;

$common = require(__DIR__.'/../../common/config/main.php');
$local = require(__DIR__.'/local/main.php');
$params = require(__DIR__.'/params.php');
$urlManager = require(__DIR__.'/../../common/config/urlManager.php');

return yii\helpers\ArrayHelper::merge([
    'id' => 'svezem-frontend',
    'basePath' => $rootPath.'/frontend',
    'bootstrap' => ['log', 'Notify'],
    'controllerNamespace' => 'frontend\controllers',
    'as geo' => GeoBehavior::class,
    'as init' => InitBehavior::class,
    'components' => [
        'urlManager' => $urlManager,
        'user' => [
            'class' => 'common\components\User',
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/account/login']
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 4 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['Osrm.query'],
                    'logFile' => '@runtime/logs/osrm.log'
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['yii\web\HttpException:404'],
                    'logFile' => '@runtime/logs/404.log'
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['application.CargoCarriageModel.createCargo'],
                    'logFile' => '@runtime/logs/createCargo.log'
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['mixplat'],
                    'logFile' => '@runtime/logs/mixplat.log'
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['Telegram.*'],
                    'logFile' => '@runtime/logs/Telegram.log'
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'yii\web\HttpException:404', 'Osrm.query', 'application.CargoCarriageModel.createCargo', 'mixplat',
                        'yii\base\ErrorException:16384', 'Telegram.*'
                    ],
                ],
               /* [
                    'class' => 'common\components\targets\ElkTarget',
                    'levels' => ['error', 'warning'],
                    'prefixName' => 'frontend'
                ]*/
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'assetManager' => [
            'baseUrl' => 'https://'.Yii::getAlias('@assetsDomain/assets'),
            'forceCopy' => YII_ENV_DEV,
            'appendTimestamp' => !YII_ENV_DEV,
            'converter' => [
                'class' => AssetConverter::class,
                'commands' => [
                    'scss' => ['css', 'sass {from} {to} --source-map'],
                ],
            ],
            'bundles' => YII_ENV_DEV ? [
                'yii\web\JqueryAsset' => [
                    'js' => ['jquery.js'],
                    'sourcePath' => '@frontend/assets/resources/js/libs'
                ]
            ] : require 'assets.prod.php'
        ],
        'session' => [
            'cookieParams' => [
                'domain' => '.'.Yii::getAlias('@domain'),
                'httpOnly' => true,
                'secure' => true,
                //'sameSite' => PHP_VERSION_ID >= 70300 ? yii\web\Cookie::SAME_SITE_LAX : null
            ]
        ],
        'i18n' => [
            'translations' => [
                'yii2mod.comments' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@yii2mod/comments/messages',
                ]
            ],
        ],
        //форматирование вывода
        'formatter' => [
            'dateFormat' => 'dd.MM.yyyy',
            'decimalSeparator' => '.',
            'thousandSeparator' => ' '
        ],
        'Notify' => [
            'class' => 'common\modules\Notify\Component'
        ],
        'opengraph' => [
            'class' => 'umanskyi31\opengraph\OpenGraph',
            'site_name' => 'Svezem.ru - Сервис по перевозке грузов'
        ]
    ],
    'modules' => [
        'cabinet' => [
            'class' => 'frontend\modules\cabinet\Module',
        ],
        'cargo' => [
            'class' => 'frontend\modules\cargo\Module',
        ],
        'transport' => [
            'class' => 'frontend\modules\transport\Module',
        ],
        'transporter' => [
            'class' => 'frontend\modules\transporter\Module',
        ],
        'info' => [
            'class' => 'frontend\modules\info\Module',
        ],
        'articles' => [
            'class' => 'frontend\modules\articles\Module',
        ],
        'account' => [
            'class' => 'frontend\modules\account\Module',
        ],
        'tk' => [
            'class' => 'frontend\modules\tk\Module',
        ],
        'intercity' => [
            'class' => 'frontend\modules\intercity\Module',
        ],
        'sub' => [
            'class' => 'frontend\modules\subscribe\Module',
        ],
        'payment' => [
            'class' => 'frontend\modules\payment\Module',
        ],
        'notify' => [
            'class' => 'common\modules\Notify\Module',
        ],
        //Модуль используется на сайте и в админке
        'comment' => [
            'class' => 'yii2mod\comments\Module',
            // when admin can edit comments on frontend
            'enableInlineEdit' => true,
            'commentModelClass' => CommentModel::class,
            'controllerMap' => [
                'default' => [
                    'class' => 'yii2mod\comments\controllers\DefaultController',
                    'on '.DefaultController::EVENT_AFTER_CREATE => [
                        '\frontend\widgets\reviews\MessageEvent',
                        'sendMessage'
                    ]
                ]
            ]
        ],
        'rating' => [
            'class' => 'frontend\modules\rating\Module'
        ],
        'telegram' => [
            'class' => frontend\modules\telegram\Module::class
        ],
        'locationselector' => [
            'class' => frontend\modules\locationselector\Module::class,
        ]
    ],
    'params' => $params
], $common, $local);
