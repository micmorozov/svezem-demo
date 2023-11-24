<?php
namespace frontend\assets;

use yii\web\AssetBundle;

class SweetAlertAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        //Библиотека для sweetalert2 на древних браузерах
        'js/libs/browser.js',
        'js/libs/sweetalert2.all.min.js'
    ];
}
