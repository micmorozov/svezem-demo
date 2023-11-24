<?php

namespace frontend\modules\cargo\assets;

use frontend\assets\GMapAsset;
use frontend\widgets\phoneButton\phoneButtonAsset;
use yii\web\AssetBundle;

class CargoViewAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        GMapAsset::class,
        phoneButtonAsset::class,
    ];

    public $js = [
        'js/cargo_view_action.js'
    ];
}
