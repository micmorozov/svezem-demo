<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 16.11.18
 * Time: 15:53
 */

namespace frontend\modules\payment\widget;

use frontend\assets\ButtonLoaderAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class PromoPaymentAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/resources';

    public $depends = [
        JqueryAsset::class,
        ButtonLoaderAsset::class
    ];

    public $js = [
        'js/promo-payment.js'
    ];
}
