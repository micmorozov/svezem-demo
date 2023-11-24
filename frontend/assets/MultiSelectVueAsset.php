<?php

namespace frontend\assets;

use frontend\widgets\assets\MultipleSelectAsset;
use yii\web\AssetBundle;

class MultiSelectVueAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/specials/multiSelectVue.js'
    ];

    public $depends = [
        MultipleSelectAsset::class
    ];
}
