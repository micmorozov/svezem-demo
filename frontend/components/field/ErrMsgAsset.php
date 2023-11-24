<?php

namespace frontend\components\field;

use yii\web\AssetBundle;

class ErrMsgAsset extends AssetBundle
{
    public $js = [
        'js/errMsg.js'
    ];

    public $depends = [
        'yii\web\JqueryAsset'
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if( YII_ENV_DEV != 'dev' ){
            //на продакшене скрипт слит в main.js из AppAsset
            $this->js = [];

            $this->depends = [
                'frontend\assets\AppAsset'
            ];
        }

        $this->sourcePath = __DIR__."/resources";
        parent::init();
    }
}
