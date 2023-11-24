<?php

namespace frontend\modules\cargo\assets;

use frontend\assets\GMapAsset;
use yii\web\AssetBundle;

class PassingViewAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        GMapAsset::class
    ];

    public $js = [
        'js/passing_view.js'
    ];
}