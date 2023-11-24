<?php

use common\models\CargoCategory;
use common\models\PageTemplates;
use common\models\Transport;
use common\models\TransportSearchTags;
use frontend\components\Offer;
use frontend\components\schema\AggregateOffer;
use frontend\modules\rating\helpers\RatingHelper;
use frontend\modules\rating\models\Rating;
use frontend\modules\transport\models\TransportSearch;
use frontend\widgets\PaginationWidget;
use simialbi\yii2\schemaorg\helpers\JsonLDHelper;
use simialbi\yii2\schemaorg\models\AggregateRating;
use simialbi\yii2\schemaorg\models\Product;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\Pjax;
use yii\widgets\ListView;

/** @var $model TransportSearch */
/** @var $dataProvider ActiveDataProvider */
/** @var $tags TransportSearchTags[] */
/** @var $pageTpl PageTemplates */
/** @var $this View */
/** @var bool $showPagination */

// Устанавливаем значение по умолчанию
$title = 'Поиск транспорта';
$descr = 'Поиск транспорта';
$keywords = 'Поиск транспорта';
$h1 = 'Поиск транспорта';
$text = 'Поиск транспорта';
// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = trim($pageTpl->title);
    $descr = trim($pageTpl->desc);
    $keywords = trim($pageTpl->keywords);
    $h1 = trim($pageTpl->h1);
    $text = nl2br($pageTpl->text);

    //переда шаблона в layout
    $this->params['pageTpl'] = $pageTpl;
}

////////////////////////////
// Дописываем номера страниц
$numPage = $model->getPage();
if ($numPage>1) {
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

// Если нет данных, запрещаем индексацию страницы
if(!$dataProvider->models){
    $this->registerMetaTag([
        'name' => 'robots',
        'content' => 'noindex, nofollow'
    ]);
}else {
    // разметка
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
    usort($models, function ($a, $b) {
        /** @var  Transport $a */
        /** @var  Transport $b */
        if ($a->price_from == $b->price_from) {
            return 0;
        }
        return ($a->price_from > $b->price_from) ? -1 : 1;
    });

    if (!empty($models)) {
        $aggr->lowPrice = $models[count($models) - 1]->price_from;
        $aggr->highPrice = $models[0]->price_from;
        $aggr->priceCurrency = 'RUB';
        $aggr->availability = "http://schema.org/InStock";
    }

    $offers = array_map(function ($tr) use ($pageTpl) {
        /** @var Transport $tr */
        $offer = new Offer();
        $offer->name = Transport::titleItemByTemplate($tr, $pageTpl);
        $offer->url = $tr->url;
        $offer->image = $tr->getImagePath(null, true);
        $offer->eligibleRegion = $tr->cityFrom->getFQTitle();

        return $offer;
    }, $models);

    $aggr->offers = $offers;

    $aggr->offerCount = count($offers);

    JsonLDHelper::add($product);
}
?>
<main class="content">
    <div class="container carrier-search">
        <?= $this->render('//common/_breadcrumbs') ?>
        <?php Pjax::begin([
            'timeout' => 3000
        ]) ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <div class="row display-flex">
            <div class="col-xs-12 col-sm-12 col-md-4 col-md-push-8">
                <?= $this->render('_search_form', [
                    'model' => $model,
                    'tags' => $tags
                ]); ?>
            </div>
            <div id="scrollTo" class="col-xs-12 col-sm-12 col-md-8 col-md-pull-4 ">
                <div class="transportation__head hidden">
                    <div class="carrier-search__btn-wrap"></div>
                    <div class="cargo-search__title-wrap">
                        <h2 class="content__title">НАЙДЕННЫЕ ПЕРЕВОЗЧИКИ</h2>
                    </div>
                </div>
                <?= ListView::widget([
                    'id' => 'search_items',
                    'dataProvider' => $dataProvider,
                    'itemView' => '_transport_item',
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
        <div class="content__pagination carrier-search__pagination" style="text-align: center">
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
        <?php Pjax::end() ?>
    </div>
</main>
