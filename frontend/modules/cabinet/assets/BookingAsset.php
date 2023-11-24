<?php

namespace frontend\modules\cabinet\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class BookingAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/resources';

    public $js = [
        'js/booking.js'
    ];

    public $depends = [
        JqueryAsset::class
    ];
}