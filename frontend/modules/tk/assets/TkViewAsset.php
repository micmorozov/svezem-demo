<?php

namespace frontend\modules\tk\assets;

use frontend\widgets\phoneButton\phoneButtonAsset;
use yii\web\AssetBundle;

class TkViewAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/tk_view.js'
    ];

    public $depends = [
        phoneButtonAsset::class
    ];
}
