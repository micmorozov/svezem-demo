<?php

use common\components\bookingService\Service;
use frontend\modules\cabinet\models\CargoBookingSearch;
use frontend\widgets\PaginationWidget;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use frontend\modules\cabinet\assets\BookingAsset;
use common\models\ServiceRate;
use frontend\modules\cargo\assets\CargoViewAsset;

/** @var $searchModel CargoBookingSearch */
/** @var bool $openFilter */
/** @var $dataProvider ActiveDataProvider */
/** @var $this View */
/** @var $filters array */
/** @var Service $bookingService */

//BookingAsset::register($this);
CargoViewAsset::register($this);
$this->registerJs("cargo_view_init();", View::POS_END);

$this->title = "Личный кабинет - Бронирование грузов";

$rate = ServiceRate::findOne($bookingService->getTariffId());
?>
<main class="content">
    <div class="container cargo-search">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Бронирование</b></h1>
        </div>
        <?php /*Pjax::begin([
            'timeout' => 3000,
            'id' => 'cargoBooking'
        ])*/ ?>

        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-4 col-md-push-8">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Тариф
                    </div>
                    <div class="panel-body">
                        <h3><?= $rate->service->name ?></h3>
                        <ul class="list-unstyled clear">
                            <li class="clear">
                                Доступно контактов в сутки
                                <span class="pull-right">
                                    <?= $bookingService->dayLimitRemain() ?> из <?= $bookingService->getDayLimit() ?>
                                </span>
                            </li>
                            <?php if ($bookingService->getDayLimitTTL() > 0): ?>
                                <li class="clear">
                                    <small class="pull-right text-muted">
                                        (обновление через <?= date('H:i', $bookingService->getDayLimitTTL()) ?> часов)
                                    </small>
                                </li>
                            <?php endif; ?>
                            <li class="divider"></li>
                            <li>Доступно бронирований: <span
                                    class="pull-right"><?= $bookingService->getBookingRemain() ?> из <?= $bookingService->getBookingLimit() ?></span>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <?php
                                $relativeTime = $expire = $bookingService->getExpire();
                                //Если истекает больше чем через один день,
                                //то прибавляем еще сутки для корректного отображения
                                $relativeTime += ($expire > time() + 86400 ? 86400 : 0);
                                ?>
                                Истекает:
                                <span class="pull-right">
                                    <?php
                                    $now = new DateTime();
                                    $end = new DateTime();
                                    $end->setTimestamp($relativeTime);
                                    ?>
                                    <?= Yii::$app->formatter->asDate($expire); ?>
                                </span>
                            </li>
                            <li>
                                <span class="pull-right">
                                    <small class="text-muted">
                                        (<?= Yii::$app->formatter->asRelativeTime($end->diff($now)); ?>)
                                        </small>
                                </span>
                            </li>
                        </ul>
                        <div class="text-center">
                            <a class="btn btn-primary btn-svezem"
                               href="//<?= Yii::getAlias('@domain') . '/cargo/booking/' ?>">
                                Изменить / продлить
                            </a>
                        </div>
                    </div>
                </div>
                <?= $this->render('_search_form', [
                    'model' => $searchModel,
                    'openFilter' => $openFilter
                ]); ?>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-8 col-md-pull-4 " id="vue-phone">
                <?= $this->render('_filters', ['filters' => $filters]) ?>
                <div style="margin-bottom: 12px;"></div>
                <div class="cargo-search__found transportation" id="scrollTo">
                    <?= ListView::widget([
                        'id' => 'search_items',
                        'dataProvider' => $dataProvider,
                        'itemView' => '_cargo_item',
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
                <div class="content__pagination cargo-search__pagination">
                </div>
            </div>
        </div>
        <div class="content__pagination carrier-search__pagination" style="text-align: center">
            <?= PaginationWidget::widget([
                'pagination' => $dataProvider->getPagination(),
                'registerLinkTags' => true,
                'registerRobotsTags' => true,
                'scrollTo' => '#scrollTo',
                'searchFade' => 'search_items'
            ]) ?>
        </div>
        <?php
        //$this->registerJs("startTimers();");
        ?>
        <?php
        $this->registerJs("
$('.search_scroll').click(function(){
    $('html, body').animate({
        scrollTop: $('#scrollTo').offset().top
    }, 500);
    $('#search_items').css('opacity', 0.3);
});
$('.show_trigger').click(function(){
    $(this).next().slideToggle('slide');
    $(this).find('i').toggleClass('up');
    $('.posts__tags').slideToggle();
});
$('.hide_trigger').click(function(){
    $('.show_trigger').find('i').toggleClass('up');
    $('.cargo-search__box.search').slideToggle('slide');
    $('.posts__tags.tags').slideToggle('slide');
});"
        );
        ?>
        <?php //Pjax::end() ?>
    </div>
</main>
