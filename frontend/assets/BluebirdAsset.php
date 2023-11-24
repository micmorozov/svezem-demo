<?php

namespace frontend\assets;

use execut\yii\web\AssetBundle;

class BluebirdAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/libs/bluebird.min.js'
    ];
}