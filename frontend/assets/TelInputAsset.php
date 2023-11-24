<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class TelInputAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/main/intlTelInput.js'
    ];

    public $css = [
        'css/intlTelInput.css'
    ];

    public $depends = [
        JqueryAsset::class,
        VueAsset::class
    ];
}
