<?php

namespace frontend\assets;

use common\components\version\Version;
use Yii;
use yii\web\AssetBundle;

class ScreenAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $css = [
        'scss/screen.scss'
    ];
}
