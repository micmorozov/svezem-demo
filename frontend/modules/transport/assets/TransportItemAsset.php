<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 19.11.18
 * Time: 16:40
 */

namespace frontend\modules\transport\assets;

use frontend\assets\SweetAlertAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class TransportItemAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $depends = [
        JqueryAsset::class,
        SweetAlertAsset::class
    ];

    public $js = [
        'js/transport_item.js'
    ];
}