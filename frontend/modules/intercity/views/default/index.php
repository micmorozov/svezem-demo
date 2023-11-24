<?php

use common\models\IntercityTags;
use common\models\PageTemplates;
use yii\helpers\Html;
use yii\web\View;

/** @var $this View */
/** @var $tagTpl PageTemplates */
/** @var $pageTpl PageTemplates */
/** @var $tags IntercityTags */

// Устанавливаем значение по умолчанию
$title = 'Дешевые междугородние грузоперевозки';
$descr = 'Экономьте до 70%. Недорогая грузовая перевозка между городами. Объявления частных перевозчиков и услуги транспортных компаний';
$keywords = 'междугородные, перевозки, межгород, груз, грузовые, грузоперевозки, между городами, недорого';
$h1 = 'Перевозка грузов межгород';
$text = '';
// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = $pageTpl->title;
    $descr = $pageTpl->desc;
    $keywords = $pageTpl->keywords;
    $h1 = $pageTpl->h1;
    $text = nl2br($pageTpl->text);

    //переда шаблона в layout
    $this->params['pageTpl'] = $pageTpl;
}
$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);
?>
<main class="content list-page-wrap">
    <div class="container list-page">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
        </div>
        <div class="list-page__block">
            <ul class="list-unstyled">
                <?php
                foreach ($tags as $tag) {
                    echo Html::beginTag('li');
                    echo Html::a($tag->name, $tag->url);
                    echo Html::endTag('li');
                }
                ?>
            </ul>
        </div>

        <div>
            <p><?=$text?></p>
        </div>
    </div>
</main>
