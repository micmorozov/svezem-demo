<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class AxiosAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/resources';

    public $js = [
        'js/libs/axios.min.js'
    ];
}