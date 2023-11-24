<?php

namespace frontend\modules\cargo\assets;

use frontend\assets\GMapAsset;
use yii\web\AssetBundle;

class SearchViewAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        GMapAsset::class
    ];

    public $js = [
        'js/search_view.js'
    ];
}
