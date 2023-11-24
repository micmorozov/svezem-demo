<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 19.06.18
 * Time: 15:11
 */

namespace frontend\modules\subscribe\assets;

use frontend\assets\AppAsset;
use frontend\assets\ButtonLoaderAsset;
use frontend\assets\CityRegionSelectVueAsset;
use frontend\assets\MultiSelectVueAsset;
use frontend\assets\Select2Asset;
use yii\web\AssetBundle;

class SubscribeFormVueAssets extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        AppAsset::class,
        Select2Asset::class,
        CityRegionSelectVueAsset::class,
        MultiSelectVueAsset::class,
        ButtonLoaderAsset::class
    ];

    public $js = [
        'js/subscribe_form_vue.js'
    ];
}