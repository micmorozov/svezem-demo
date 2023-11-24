<?php
/**
 * Configuration file for the "yii asset" console command.
 */

// In the console environment, some path aliases may not exist. Please define these:
use yii\web\AssetConverter;

Yii::setAlias('@webroot', __DIR__ . '/../web');
Yii::setAlias('@web', '/');

return [
    // Adjust command/callback for JavaScript files compressing:
    'jsCompressor' => 'java -jar bin/compiler.jar --js {from} --js_output_file {to}',
    // Adjust command/callback for CSS files compressing:
    'cssCompressor' => 'java -jar bin/yiicompressor.jar --type css {from} -o {to}',
    // Whether to delete asset source after compression:
    'deleteSource' => false,
    // The list of asset bundles to compress:
    'bundles' => [
        yii\widgets\PjaxAsset::class,
        yii\widgets\ActiveFormAsset::class,
        yii\validators\ValidationAsset::class,

        // /city/ выбор региона
        frontend\assets\CitySelectAsset::class,

        frontend\assets\AppAsset::class,
        common\modules\Notify\assets\NotifyAsset::class,

        // Форма добавления груза
        frontend\modules\cargo\widgets\WidgetAsset::class,
        /////////////////////////

        frontend\modules\cargo\assets\CargoItemAsset::class,
        frontend\modules\cargo\assets\CargoViewAsset::class,
        frontend\modules\cargo\assets\PassingViewAsset::class,
        frontend\modules\cargo\assets\SearchViewAsset::class,

        frontend\modules\transport\assets\TransportItemAsset::class,
        frontend\modules\transporter\assets\TransporterViewAsset::class,
        frontend\modules\account\assets\TransportValidateAsset::class,

        frontend\modules\tk\assets\TkViewAsset::class,

        // Рейтинг в футере
        frontend\modules\rating\widget\assets\RatingAsset::class,
        yii2mod\rating\StarRatingAsset::class,
        //////////////////////////

        // Страница подписки
        frontend\modules\subscribe\assets\SubscribeFormVueAssets::class,

        frontend\modules\info\assets\ContactsAsset::class,
        frontend\modules\payment\widget\PromoPaymentAsset::class,

        frontend\assets\ScreenAsset::class // Должна быть последней
    ],
    // Asset bundle for compression output:
    'targets' => [
        'all' => [
            'class' => 'yii\web\AssetBundle',
            'basePath' => '@webroot',
            'baseUrl' => 'https://'.Yii::getAlias('@assetsDomain'),
            'js' => 'js/all.js',
            'css' => 'css/screen.css'
        ]
    ],
    // Asset manager configuration:
    'assetManager' => [
        'basePath' => '@webroot/assets',
        'baseUrl' => '@web/assets',
        'converter' => [
            'class' => AssetConverter::class,
            'commands' => [
                'scss' => ['css', 'sass {from} {to} --source-map'],
            ],
        ],
        'bundles' => [
            'yii\web\JqueryAsset' => [
                'js' => ['jquery.js'],
                'sourcePath' => '@frontend/assets/resources/js/libs'
            ]
        ]
    ],
];