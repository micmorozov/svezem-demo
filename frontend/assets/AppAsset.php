<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class AppAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/main/main.js'
    ];

    public $depends = [
        //BluebirdAsset::class,
        JqueryAsset::class,
        BootstrapAsset::class,
        SweetAlertAsset::class,
        VueAppAsset::class,
    ];
}
