<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 20.10.17
 * Time: 9:32
 */

namespace frontend\modules\tk\assets;

use frontend\assets\ButtonLoaderAsset;
use frontend\assets\SocketIOAsset;
use frontend\assets\VueAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class TkCompareAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/tk_compare.js'
    ];

    public $depends = [
        VueAsset::class,
        SocketIOAsset::class,
        JqueryAsset::class,
        ButtonLoaderAsset::class
    ];
}