<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 13.02.19
 * Time: 11:50
 */

namespace common\modules\Notify\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class BootstrapNotifyAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/bootstrap-notify.min.js'
    ];

    public $depends = [
        JqueryAsset::class
    ];
}
