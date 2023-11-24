<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class VueAppAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/resources';

    public $js = [
        'js/specials/devLoad.js',
        'js/specials/vue.js'
    ];

    public $depends = [
        VueAsset::class
    ];
}
