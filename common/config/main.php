<?php

use common\helpers\LoggerBuilderHelper;
use console\controllers\sitemap\SitemapLastIdRedisStorage;
use Monolog\Handler\NullHandler;
use Svezem\Services\LockerService\Locker\PidLocker;
use Svezem\Services\MatrixContentService\Storage\RedisStorage;
use Svezem\Services\PaymentService\Gates\Sberbank\SberbankGate;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Svezem\Services\LockerService\LockerService;

$paymentConfig = require(__DIR__.'/local/payment.php');
$local = require(__DIR__.'/local/main.php');
$params = require(__DIR__.'/params.php');

return yii\helpers\ArrayHelper::merge([
    'name' => 'Svezem.ru - сервис по поиску перевозчиков и грузов',
    'language' => 'ru-RU',
    'vendorPath' => dirname(dirname(__DIR__)).'/vendor',
    'params' => $params,
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@vendor/bower' => '@vendor/bower-asset',
    ],
    'components' => [
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'i18n' => [
            'translations' => [
                'yii2mod.comments' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@yii2mod/comments/messages',
                ]
            ],
        ],
        'morphy' => [
            'class' => 'common\components\morphy\Morphy'
        ]
    ],
    'container' => [
        'singletons' => [
            Monolog\Logger::class => function ($container) {
                $logger = new Monolog\Logger('common');
/* TODO Временно отключаем публикацию логов в ELK
                try {
                    $logHandler = $container->get(Monolog\Handler\AmqpHandler::class);
                } catch (Exception $e) {
                    Yii::error(
                        'Не удалось подключится к AMQP:' . $e->getMessage(),
                        'application.error'
                    );
                    $logHandler = new NullHandler();
                }
*/
                $logHandler = new NullHandler();

                $logger->pushHandler($logHandler);
                return $logger;
            },

            // Настройки платежного шлюза от Сбербанка
            SberbankGate::class => function() use ($paymentConfig){
                $cfg = $paymentConfig[SberbankGate::class] ?? [];
                foreach(['userName', 'password', 'secretKey'] as $reqField) {
                    if (!array_key_exists($reqField, $cfg)) {
                        throw new Exception("Wrong config params '{$reqField}' for " . SberbankGate::class);
                    }
                }

                $logger = LoggerBuilderHelper::getPaymentLogger('SberbankGate');

                return (new SberbankGate($logger))
                    ->setLogin($cfg['userName'])
                    ->setPassword($cfg['password'])
                    ->setSecretKey($cfg['secretKey'])
                    ->setTestMode($cfg['testMode'] ?? true)
                ;
            },

            MatrixContentService::class => function(){
                $logger = LoggerBuilderHelper::getMatrixContentLogger('MatrixContent');

                $storage = new RedisStorage(Yii::$app->redisTemp->getClient());
                return new MatrixContentService($storage, $logger);
            },

            SitemapLastIdRedisStorage::class => function(){
                return new SitemapLastIdRedisStorage(Yii::$app->redisTemp->getClient());
            },

            'cronLockerService' => function(){
                return new LockerService(new PidLocker());
            }
        ]
    ]
], $local);
