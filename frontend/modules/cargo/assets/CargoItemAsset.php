<?php

namespace frontend\modules\cargo\assets;

use frontend\assets\SweetAlertAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class CargoItemAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/cargo_item.js'
    ];

    public $depends = [
        JqueryAsset::class,
        SweetAlertAsset::class
    ];
}