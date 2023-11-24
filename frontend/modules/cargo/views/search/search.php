<?php

use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CargoSearchTags;
use common\models\PageTemplates;
use frontend\components\Offer;
use frontend\components\schema\AggregateOffer;
use frontend\modules\cargo\assets\SearchViewAsset;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\rating\helpers\RatingHelper;
use frontend\modules\rating\models\Rating;
use frontend\widgets\PaginationWidget;
use simialbi\yii2\schemaorg\helpers\JsonLDHelper;
use simialbi\yii2\schemaorg\models\AggregateRating;
use simialbi\yii2\schemaorg\models\Product;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\Pjax;
use yii\widgets\ListView;
use common\models\LocationInterface;

/* @var $model CargoSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $filters CargoSearchTags[] */
/* @var $this View */
/* @var $tags CargoSearchTags[] */
/* @var $pageTpl PageTemplates */
/** @var bool $showPagination */

// Устанавливаем значение по умолчанию
$title = 'Поиск грузов';
$descr = 'Поиск грузов';
$keywords = 'Поиск грузов';
$h1 = 'Поиск грузов';
$text = 'Поиск грузов';
// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = trim($pageTpl->title);
    $descr = trim($pageTpl->desc);
    $keywords = trim($pageTpl->keywords);
    $h1 = trim($pageTpl->h1);
    $text = nl2br($pageTpl->text);
}

////////////////////////////
// Дописываем номера страниц
$numPage = $model->getPage();
if ($numPage > 1) {
    $h1 .= " (страница {$numPage})";
    $title .= " (страница {$numPage})";
    $descr .= " (страница {$numPage})";
}
////////////////////////////

$this->title = $title;
//передача шаблона в layout
$this->params['pageTpl'] = $pageTpl;

$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);

// Если нет данных, запрещаем индексацию страницы
if(!$dataProvider->models){
    $this->registerMetaTag([
        'name' => 'robots',
        'content' => 'noindex, nofollow'
    ]);
}else {
    // Микроразметка
    $product = new Product();
    $product->name = $title;
    $product->description = $descr;
    $product->brand = 'Svezem.ru';
    if ($pageTpl) {
        $product->sku = $pageTpl->id;
        $product->image = CargoCategory::getIconPng($pageTpl->category_id, true);
    }
    $ratingName = RatingHelper::ratingName($pageTpl);

    $ratingModel = Rating::find($ratingName);
    $rating = new AggregateRating();
    $rating->ratingValue = $ratingModel->score;
    $rating->ratingCount = $ratingModel->sum;

    if ($rating->ratingValue) {
        $product->aggregateRating = $rating;
    }

    $aggr = new AggregateOffer();
    $aggr->name = $title;
    $product->offers = $aggr;

    $models = $dataProvider->models;

    if ($dataProvider->count) {
        $aggr->lowPrice = 0;
        $aggr->priceCurrency = 'RUB';
        $aggr->availability = "http://schema.org/InStock";
    }

    $offers = array_map(function ($cargo) use ($pageTpl) {
        /** @var Cargo $cargo */
        $offer = new Offer();
        $offer->name = $cargo->title($pageTpl);
        $offer->url = $cargo->url;
        $offer->image = $cargo->iconPng;
        $offer->eligibleRegion = $cargo->cityFrom->getFQTitle();

        return $offer;
    }, $models);

    $aggr->offers = $offers;
    $aggr->offerCount = count($offers);

    JsonLDHelper::add($product);
}

SearchViewAsset::register($this);
$this->registerJs("cargo_search_init();", View::POS_END);
?>
<main class="content">
    <div class="container">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <div class="cargo-search">
            <?php Pjax::begin([
                'timeout' => 3000
            ]); ?>
            <div class="row display-flex">
                <div class="col-xs-12 col-sm-12 col-md-4 col-md-push-8 ">
                    <?= $this->render('_search_form', [
                        'model' => $model,
                        'tags' => $tags
                    ]); ?>
                </div>
                <div id="scrollTo" class="col-xs-12 col-sm-12 col-md-8 col-md-pull-4">
                    <div class="content-divider"></div>
                    <div class="cargo-search__found transportation">
                        <div class="cargo-search__title-wrap hidden">
                            <h2 class="content__title">Найдены грузы</h2>
                        </div>
                        <?= ListView::widget([
                            'id' => 'search_items',
                            'dataProvider' => $dataProvider,
                            'itemView' => '_cargo_item',
                            'itemOptions' => [
                                'tag' => false
                            ],
                            'viewParams' => [
                                'pageTpl' => $pageTpl
                            ],
                            'options' => [
                                'tag' => 'div'
                            ],
                            'layout' => "{items}"
                        ]);
                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="content__pagination cargo-search__pagination">
                        <?php if ($showPagination): ?>
                            <?= PaginationWidget::widget([
                                'pagination' => $dataProvider->getPagination(),
                                'registerLinkTags' => true,
                                'scrollTo' => '#scrollTo',
                                'searchFade' => 'search_items'
                            ]) ?>
                        <?php elseif($dataProvider->totalCount > $dataProvider->pagination->pageSize): ?>
                            <div class="panel panel-default">
                                <div class="panel-body btn-search" style="cursor:pointer;" onclick="searchOther($('#searchForm'))">
                                    <div class="text-center">
                                        <span class="h4">Показать еще</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php Pjax::end() ?>
    <div id="cargo-search__map" class="cargo-search__map"></div>
</main>
