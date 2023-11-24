<?php

use backend\models\PaymentSearch;
use frontend\widgets\PaginationWidget;
use yii\grid\GridView;
use yii\web\View;
use yii\widgets\Pjax;

/** @var $this View */
/* @var $searchModel backend\models\PaymentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "Личный кабинет - История операций";
?>
<main class="content">
    <div class="container cargo-search">
        <div class="cargo-search__title-wrap">
            <h1 class="cargo-search__title">История операций</h1>
            <span class="line"></span>
        </div>
        <?php Pjax::begin() ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            //'filterModel' => $searchModel,
            'pager' => [
                'class' => PaginationWidget::class
            ],
            'options' => [
                'style' => 'text-align: right;'
            ],
            'columns' => [
                [
                    'attribute' => 'id',
                    'label' => 'Номер счета',
                    'contentOptions' => [
                        'style' => 'width:110px;'
                    ],
                    'enableSorting' => false
                ],
                [
                    'attribute' => 'payment_system_id',
                    'format' => 'raw',
                    'label' => 'Способ оплаты',
                    'value' => function ($model){
                        /** @var $model PaymentSearch */
                        return $model->paymentMethodLabel;
                    },
                    'filter' => PaymentSearch::paymentMethodLabels(),
                    'enableSorting' => false
                ],
                [
                    'attribute' => 'amount',
                    'label' => 'Cумма оплаты (руб.)',
                    'encodeLabel' => false,
                    'enableSorting' => false
                ],
                [
                    'attribute' => 'created_at',
                    'format' => ['date', 'dd.MM.YYYY HH:mm:ss'],
                    'enableSorting' => false
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function ($model){
                        /** @var $model PaymentSearch */
                        return PaymentSearch::getStatusLabel($model->status, true);
                    },
                    'filter' => PaymentSearch::statusLabels(),
                    'enableSorting' => false
                ]
            ]
        ]); ?>
        <?php Pjax::end() ?>
    </div>
</main>
