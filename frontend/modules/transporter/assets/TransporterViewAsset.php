<?php

namespace frontend\modules\transporter\assets;

use frontend\widgets\phoneButton\phoneButtonAsset;
use yii\web\AssetBundle;

class TransporterViewAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        phoneButtonAsset::class
    ];

    public $js = [
        'js/transporter_view.js'
    ];
}
