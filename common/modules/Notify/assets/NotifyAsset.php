<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 13.02.19
 * Time: 14:55
 */

namespace common\modules\Notify\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class NotifyAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/notify.js'
    ];

    public $css = [
        'css/animate.css'
    ];

    public $depends = [
        JqueryAsset::class,
        BootstrapNotifyAsset::class,
    ];
}
