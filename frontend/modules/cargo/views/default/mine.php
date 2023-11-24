<?php

use frontend\modules\cargo\assets\CargoItemAsset;
use frontend\widgets\PaginationWidget;
use yii\data\ActiveDataProvider;
use yii\widgets\Pjax;
use yii\widgets\ListView;
use yii\helpers\Html;

$this->title = "Личный кабинет - Мои грузы";
/** @var ActiveDataProvider $dataProvider */

CargoItemAsset::register($this);
?>
<main class="content">
    <div class="container cargo-search">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Мои грузы</b></h1>
            <div class="content__subtitle">Договариваясь о перевозке старайтесь соблюдать <a href="<?='//' . Yii::getAlias('@domain') . '/info/legal/cargo-owner/'?>">несколько простых правил по работе с перевозчиком</a>. Это позволит сэкономить время, деньги и нервы.</div>
        </div>
        <?php Pjax::begin([
            'timeout' => 3000
        ]) ?>
        <div class="cargo-search__found transportation" id="scrollTo">
            <?= ListView::widget([
                'id' => 'search_items',
                'dataProvider' => $dataProvider,
                'itemView' => '/search/_cargo_item',
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
