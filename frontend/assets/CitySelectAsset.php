<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 05.02.19
 * Time: 14:15
 */

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class CitySelectAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/specials/citySelect.js'
    ];

    public $depends = [
        JqueryAsset::class,
        AutocompleteAsset::class
    ];
}