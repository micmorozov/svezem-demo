<?php

use common\models\CargoSearchTags;
use common\models\PageTemplates;
use yii\helpers\Html;
use yii\web\View;

/** @var $this View */
/** @var $pageTpl PageTemplates */
/** @var $tags CargoSearchTags[] */

$title = '';
$descr = '';
$keywords = '';
$h1 = '';
$text = '';

// Если есть шаблон. устанавливаем его
if($pageTpl) {
    $title = $pageTpl->title;
    $descr = $pageTpl->desc;
    $keywords = $pageTpl->keywords;
    $h1 = $pageTpl->h1;
    $text = $pageTpl->text;

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
        <div class="page-title" id="scrollTo">
            <h1 class="h3 text-uppercase"><b><?=$h1?></b></h1>
        </div>
        <div class="list-page__block">
            <ul class="list-unstyled">
                <?php
                    foreach($tags as $tag){
                        $link = Html::a($tag->name, $tag->url);
                        echo Html::tag('li', $link);
                    }
                ?>
            </ul>
        </div>
    </div>
</main>

