<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 13.10.17
 * Time: 11:09
 */

namespace frontend\assets;

use yii\web\AssetBundle;

class GMapAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        GMapCDNAsset::class
    ];

    public $js = [
        'js/specials/gMap.js'
    ];
}