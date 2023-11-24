<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class Select2Asset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $css = [
        'css/libs/select2.min.css'
    ];

    public $js = [
        'js/libs/select2/select2.full.min.js',
        'js/libs/select2/i18n/ru.js'
    ];

    public $depends = [
        JqueryAsset::class
    ];
}
