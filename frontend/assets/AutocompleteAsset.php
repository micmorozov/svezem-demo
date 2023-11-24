<?php
namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class AutocompleteAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/libs/jquery.autocomplete.min.js'
    ];

    public $depends = [
        JqueryAsset::class
    ];
}