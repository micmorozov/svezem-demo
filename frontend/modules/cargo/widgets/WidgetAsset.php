<?php

namespace frontend\modules\cargo\widgets;

use frontend\assets\AppAsset;
use frontend\assets\Select2Asset;
use frontend\assets\TelInputAsset;
use yii\web\AssetBundle;

class WidgetAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/cargo-carriage.js'
    ];

    public $depends = [
        AppAsset::class,
        TelInputAsset::class,
        Select2Asset::class
    ];
}