<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 13.10.17
 * Time: 11:09
 */

namespace frontend\assets;

use yii\web\AssetBundle;

class GMapCDNAsset extends AssetBundle
{
    public $sourcePath = null;

    public $depends = [
        AppAsset::class,
        DotTplAsset::class
    ];

    public $js = [
        "https://maps.googleapis.com/maps/api/js?key=AIzaSyB3Y-lnNKe9D00ql57wTaFayk22cMssQQ4",
        'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js',
    ];
}