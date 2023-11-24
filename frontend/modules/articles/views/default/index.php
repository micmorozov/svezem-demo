<?php

use common\models\ArticleTags;
use frontend\widgets\PaginationWidget;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $articleProvider ActiveDataProvider */
/** @var $tags ArticleTags[] */
/** @var $this View */

// Устанавливаем значение по умолчанию
$title = 'Статьи про грузоперевозки';
$descr = 'Статьи о всем, что связано с перевозкой грузов, доставкой товаров и организацией перевозок';
$keywords = 'статьи, грузоперевозка, доставка, транспортировка, перевозка грузов';
$h1 = 'Статьи про грузоперевозки';
// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = trim($pageTpl->title);
    $descr = trim($pageTpl->desc);
    $keywords = trim($pageTpl->keywords);
    $h1 = trim($pageTpl->h1);

    //переда шаблона в layout
    $this->params['pageTpl'] = $pageTpl;
}

////////////////////////////
// Дописываем номера страниц
$numPage = Yii::$app->request->get('page', false);
if ($numPage) {
    $h1 .= " (страница {$numPage})";
    $title .= " (страница {$numPage})";
    $descr .= " (страница {$numPage})";
}
////////////////////////////

$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);

if(!$articleProvider->totalCount){
    $this->registerMetaTag([
        'name' => 'robots',
        'content' => 'noindex'
    ]);
}

$tagCount = 15;
?>

<main class="content">
    <div class="container posts">
        <?= $this->render('//common/_breadcrumbs') ?>
        <?php Pjax::begin(['id' => 'myPjax']) ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
        </div>
        <?php
            if($tags):
        ?>
        <div class="posts__tags tags">
            <div>
                <div class="h4 text-uppercase"><b>Рубрики</b></div>
            </div>
            <div class="tags__container">
                <?php foreach ($tags as $index => $tag) {
                    echo Html::a($tag->name, Url::toRoute($tag->url), [
                        'class' => "tags__item",
                        'data-pjax' => '1',
                        'style' => 'display: '.($index >= $tagCount ? 'none' : '')
                    ]);
                    echo "\n";
                } ?>
                <?php if (count($tags) > $tagCount): ?>
                    <span class="tags__nav">
                <span class="tags__hide"><i class="fa fa-chevron-down" aria-hidden="true"></i> <span class="text">Еще</span></span>
            </span>
                <?php endif ?>
            </div>
        </div>
        <?php
            endif;
        ?>
        <div class="posts__article article clear" id="scrollTo">
            <?= ListView::widget([
                'id' => 'search_items',
                'dataProvider' => $articleProvider,
                'itemView' => '_article_item',
                'itemOptions' => [
                    'tag' => false
                ],
                'options' => [
                    'tag' => 'div'
                ],
                'layout' => "{items}"
            ]);
            ?>
        </div>
        <div class="content__pagination cargo-search__pagination row">
            <?= PaginationWidget::widget([
                'pagination' => $articleProvider->getPagination(),
                'registerLinkTags' => true,
                'scrollTo' => '#scrollTo',
                'searchFade' => 'search_items'
            ]) ?>
        </div>
        <?php Pjax::end() ?>
    </div>
</main>

<?= $this->render('//common/_feedot-loader') ?>
<?php
$this->registerJs("
var tagCount = $tagCount; 

hidenews = '<i class=\'fa fa-chevron-up\' aria-hidden=\'true\'></i><span class=\'text\'>Скрыть</span>';
shownews = '<i class=\'fa fa-chevron-down\' aria-hidden=\'true\'></i><span class=\'text\'>Еще</span>';

$(document).on('click', '.tags__container .tags__nav', function (e){
  e.preventDefault();
  if( $('.tags__container .tags__item:eq('+tagCount+')').is(':hidden') )
  {
    $('.tags__container .tags__item:hidden').fadeIn('slow');
    $('.tags__container .tags__nav').html( hidenews );
  }
  else
  {
    $('.tags__container .tags__item:not(:lt('+tagCount+'))').fadeOut('slow');
    $('.tags__container .tags__nav').html( shownews );
  }
});
");
