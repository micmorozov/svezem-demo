<?php

namespace frontend\modules\info\assets;

use frontend\assets\GMapAsset;
use yii\web\AssetBundle;

class ContactsAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $js = [
        'js/contacts.js'
    ];

    public $depends = [
        GMapAsset::class
    ];
}