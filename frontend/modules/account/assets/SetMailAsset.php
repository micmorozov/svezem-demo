<?php

namespace frontend\modules\account\assets;

use frontend\assets\AppAsset;
use yii\web\AssetBundle;

class SetMailAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/setMail.js'
    ];

    public $depends = [
        AppAsset::class
    ];
}
