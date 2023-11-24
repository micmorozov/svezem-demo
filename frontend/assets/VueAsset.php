<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class VueAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/resources';

    public $js = [
        'js/libs/vue.min.js'
    ];

    public $depends = [
        AxiosAsset::class
    ];
}