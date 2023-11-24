<?php

use common\models\CargoCategory;
use common\models\PageTemplates;
use frontend\components\Offer;
use frontend\components\schema\AggregateOffer;
use frontend\modules\rating\helpers\RatingHelper;
use frontend\modules\rating\models\Rating;
use frontend\modules\tk\models\Tk;
use frontend\modules\tk\models\TkSearch;
use frontend\widgets\PaginationWidget;
use simialbi\yii2\schemaorg\helpers\JsonLDHelper;
use simialbi\yii2\schemaorg\models\AggregateRating;
use simialbi\yii2\schemaorg\models\Product;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use common\models\LocationInterface;

/** @var $this View */
/** @var $tkSearch TkSearch */
/** @var $dataProvider ActiveDataProvider */
/** @var $pageTpl PageTemplates */
/** @var bool $showPagination */

// Устанавливаем значение по умолчанию
$title = 'Найти транспортную компанию для грузоперевозок по России и за рубеж';
$descr = 'Сравните стоимость грузоперевозки и сделайте выбор транспортной компании с наименьшими ценами';
$keywords = 'транспортные компании цены, найти транспортную компанию, транспортные компании россии цены, выбор транспортной компании, услуги транспортной компании цены, поиск транспортной компании, грузоперевозки по россии транспортные компании цена, сравнение транспортных компаний, сравнить цены транспортных компаний';
$h1 = 'Поиск перевозчиков';
$text = '';
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
$numPage = $tkSearch->getPage();
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
} else {
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

    if (!empty($models)) {
        $aggr->lowPrice = 0;
        $aggr->priceCurrency = 'RUB';
        $aggr->availability = "http://schema.org/InStock";
    }

    $offers = array_map(function ($sphinxTk) use ($pageTpl, $tkSearch) {
        /** @var Tk $tk */
        $tk = $sphinxTk->tk;

        $offer = new Offer();
        $offer->name = $tk->title($pageTpl);
        $offer->url = $tk->url;
        $offer->image = $tk->iconPath('preview_86', false, true);
        $offer->eligibleRegion = $tk->getCityAddress($tkSearch->getLocationFrom());
        //$offer->eligibleRegion = $tkSearch->getLocationFrom();

        return $offer;
    }, $models);

    $aggr->offers = $offers;

    $aggr->offerCount = count($offers);

    JsonLDHelper::add($product);
}

?>
<main class="content">
    <div class="container transportation-search">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <?php Pjax::begin([
            'timeout' => 3000
        ]) ?>
        <div class="row display-flex companies">
            <div class="col-xs-12 col-sm-12 col-md-4 col-md-push-8"><!--col-lg-12 col-lg-push-0-->
                <?= $this->render('_search_form', [
                    'model' => $tkSearch
                ]) ?>
            </div>
            <div id="scrollTo" class="col-xs-12 col-sm-12 col-md-8 col-md-pull-4 " ><!--col-lg-12 col-lg-pull-0-->
                <?= ListView::widget([
                    'id' => 'search_items',
                    'dataProvider' => $dataProvider,
                    'itemView' => '_tk_item',
                    'viewParams' => [
                        'location' => $tkSearch->getLocationFrom(),
                        'pageTpl' => $pageTpl
                    ],
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
            <div class="col-sm-12">
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
        <?php Pjax::end() ?>
    </div>
</main>
