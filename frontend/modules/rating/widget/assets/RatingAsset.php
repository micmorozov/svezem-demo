<?php

namespace frontend\modules\rating\widget\assets;

use Yii;
use yii\web\AssetBundle;

class RatingAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/resources';

    public $js = [
        'js/rating.js'
    ];
}