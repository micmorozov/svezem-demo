<?php

use common\models\PageTemplates;
use yii\helpers\Html;
use yii\web\View;
use common\models\IntercityTags;

/** @var $this View */
/** @var $pageTpl PageTemplates */
/** @var $tags IntercityTags[] */

// Устанавливаем значение по умолчанию
$title = 'Дешевые междугородние грузоперевозки по России';
$descr = 'Экономьте до 70%. Недорогая грузовая перевозка между городами по России. Объявления частных перевозчиков и услуги транспортных компаний';
$keywords = 'междугородные, перевозки, межгород, груз, грузовые, грузоперевозки, между городами, недорого';
$h1 = 'Перевозка грузов межгород по России';
$text = '';
// Если есть шаблон. устанавливаем его
if($pageTpl) {
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
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <div class="list-page__block">
            <ul class="list-unstyled">
                <?php
                    $countTags = count($tags);
                    foreach ($tags as $tag){
                        echo Html::beginTag('li');
                        echo Html::a($tag->name, $tag->url);
                        echo Html::endTag('li');
                    }
                ?>
            </ul>
        </div>
    </div>
</main>

<?php
if(!$countTags){
    $this->registerMetaTag([
        'name' => 'robots',
        'content' => 'noindex'
    ]);
}
