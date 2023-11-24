<?php

namespace frontend\modules\locationselector\widgets;

use frontend\assets\AppAsset;
use frontend\assets\Select2Asset;
use yii\web\AssetBundle;

class WidgetAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/location-form.js'
    ];

    public $depends = [
        AppAsset::class,
        Select2Asset::class
    ];
}
