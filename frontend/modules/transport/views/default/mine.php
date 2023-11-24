<?php

use frontend\helpers\Load;
use frontend\modules\transport\assets\TransportItemAsset;
use frontend\widgets\PaginationWidget;
use yii\data\ActiveDataProvider;
use yii\widgets\Pjax;
use yii\widgets\ListView;

$this->title = "Личный кабинет - Мой транспорт";

/** @var ActiveDataProvider $dataProvider */

TransportItemAsset::register($this);
?>
<main class="content">
    <div class="container carrier-search">
        <?php Pjax::begin([
            'timeout' => 3000
        ]) ?>
        <div class="page-title" id="scrollTo">
            <h1 class="h3 text-uppercase"><b>Мой транспорт</b></h1>
        </div>
        <div class="carrier-search__found services" id="scrollTo">
            <?= ListView::widget([
                'id' => 'search_items',
                'dataProvider' => $dataProvider,
                'itemView' => '/search/_transport_item',
                'itemOptions' => [
                    'tag' => false
                ],
                'options' => [
                    'tag' => 'div'
                ],
                'layout' => "{items}",
                'viewParams' => [
                    'cutText' => true
                ]
            ]);
            ?>
        </div>
        <div class="content__pagination carrier-search__pagination">
            <?= PaginationWidget::widget([
                'pagination' => $dataProvider->getPagination(),
                'registerLinkTags' => true,
                'registerRobotsTags' => true,
                'scrollTo' => '#scrollTo',
                'searchFade' => 'search_items'
            ]) ?>
        </div>
        <?php Pjax::end() ?>
    </div>
</main>
