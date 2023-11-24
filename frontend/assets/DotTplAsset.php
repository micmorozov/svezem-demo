<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class DotTplAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        JqueryAsset::class
    ];

    public $js = [
        'js/libs/dot/doT.min.js',
        'js/libs/dot/plugin.js'
    ];
}
