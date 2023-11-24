<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class SocketIOAsset extends AssetBundle
{
    public $sourcePath = __DIR__ .'/resources';

    public $js = [
        'js/libs/socket.io.js'
    ];
}
