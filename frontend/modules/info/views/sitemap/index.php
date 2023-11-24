<?php

use common\helpers\PhoneHelpers;
use common\models\Contacts;
use ferrumfist\yii2\recaptcha\ReCaptcha;
use frontend\modules\info\assets\ContactsAsset;
use frontend\modules\info\models\Feedback;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/** @var $sitemapList [] */
// Устанавливаем значение по умолчанию
$title = 'Карта сайта';
$descr = 'Карта сайта';
$keywords = 'Карта сайта';
$h1 = 'Карта сайта';
$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);
?>
<main class="content">
    <div class="container">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
        </div>

        <?php
            if($sitemapList):
        ?>
        <div class="list-page__block">
            <ul class="list-unstyled">
            <?php
            foreach($sitemapList as $sitemap ){
                if($sitemap){
                    echo '<li>'.Html::a($sitemap, $sitemap).'</li>';
                }
            }
            ?>
            </ul>
        </div>
        <?php
            endif;
        ?>
    </div>
</main>
