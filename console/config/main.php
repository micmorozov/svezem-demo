<?php

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);

$common = require(__DIR__ . '/../../common/config/main.php');
$local = require(__DIR__ . '/local/main.php');
$urlManager = require(__DIR__ . '/../../common/config/urlManager.php');

return yii\helpers\ArrayHelper::merge([
    'id' => 'svezem-console',
    'basePath' => $rootPath . '/console',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'components' => [
        'urlManager' => $urlManager,
        'log' => [
            //чтобы при логировании Gearman сообщения
            //сразу попадали в лог
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/error.log',
                    'levels' => ['error'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/warning.log',
                    'levels' => ['warning'],
                ],
                /* Для чего логгировать все вподряд, в том числе и системные вызовы?
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/other.log',
                    'levels' => ['info', 'trace', 'profile'],
                ]*/
                [
                    'class' => 'common\components\targets\ElkTarget',
                    'levels' => ['error', 'warning'],
                    'prefixName' => 'console'
                ]
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ]
    ],
	'controllerMap' => [
		'gearman' => [
			'class' => 'micmorozov\yii2\gearman\GearmanController',
			'gearmanComponent' => 'gearman'
		]
	],
    'params' => $params,
], $common, $local);
