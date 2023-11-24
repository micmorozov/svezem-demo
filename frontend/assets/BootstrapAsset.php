<?php


namespace frontend\assets;

use yii\web\AssetBundle;

class BootstrapAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $css = [
        // Переопределяется в ScreenAsset
        //'css/libs/bootstrap.min.css',
    ];

    public $js = [
        'js/libs/bootstrap.min.js',
        'js/libs/bootstrap.offcanvas.min.js'
    ];
}