<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class ButtonLoaderAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/main/buttonLoader.js',
    ];

    public $depends = [
        JqueryAsset::class
    ];
}
