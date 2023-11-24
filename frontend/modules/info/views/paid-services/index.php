<?php

use common\models\Service;
use common\models\Transport;
use frontend\modules\payment\widget\PromoPayment;
use frontend\modules\payment\widget\PromoPaymentAsset;
use frontend\widgets\Select2;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\web\View;
use yii\widgets\Pjax;

/** @var $transports Transport[] */
/** @var $this View */
/** @var $selectedId int */

$this->title = 'Платные услуги';

$services = Service::find()
    ->with('serviceRates')
    ->where(['id' => Yii::$app->params['transportServices']])
    ->all();

$transportsDropItems = ['' => ''];

if (empty($transports)) {
    $transportsDropItems['add'] = 'Добавить транспорт';
}

foreach ($transports as $transport) {
    $transportsDropItems[$transport->id] = $transport->cityFrom->title_ru . ' - ' .
        $transport->cityTo->title_ru . ' (' . StringHelper::truncate($transport->description, 60) . ')';
}

//PromoPayment подключает этот asset, но в pjax
//чтобы на продакшене объявить его в load-list
//зарегистрируем его здесь
PromoPaymentAsset::register($this);
?>
<main class="content">
    <div class="container post">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $this->title ?></b></h1>
        </div>
        <?php Pjax::begin(['id' => 'pjax']) ?>
        <?= Select2::widget([
            'id' => 'tr_select',
            'name' => 'transport',
            'data' => $transportsDropItems,
            'value' => $selectedId,
            'options' => [
                'data-minimum-results-for-search' => "Infinity",
                'style' => 'width: 100%; max-width:350px;'
            ],
            'pluginOptions' => [
                'theme' => 'bootstrap',
                'placeholder' => "Выберите транспорт",
            ]
        ]) ?>
        <br><br>
        <div id="serviceList">
            <?php if (!$selectedId): ?>
                <?php foreach ($services as $service): ?>
                    <div class="promo-payment__item promo-payment__item-vip">
                        <div class="col-md-3 promo-payment__item-check">
                            <div class="promo-payment__item-box">
                                <label class="promo-payment__item-check-img-label" for="vip" style="cursor: auto">
                                    <?= Html::img("/img/icons/payment/{$service->id}.svg", [
                                        'class' => 'img-responsive promo-payment__item-check-img'
                                    ]); ?>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-9 promo-payment__item-info">
                        <span class="h3 promo-payment__item-info-title"
                              style="display: inline-block;width: 100%; margin-top: 0;">
                            <?= $service->name; ?><br/>
                            <?php if (!empty($service->serviceRates)): ?>
                                <small><?= 'на ' . $service->serviceRates[0]->amount . ' дней' ?></small>
                                <span class="pull-right promo-payment__item-price-count price_for_service"><?= doubleval($service->serviceRates[0]->price) ?> р.</span>
                            <?php endif; ?>
                            </span>
                            <div class="divider"></div>
                            <?= Service::extenedDescription($service) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                foreach ($services as $serv) {
                    $options = [];

                    foreach ($serv->serviceRates as $rate) {
                        $options[$rate->id] = [
                            'description' => 'на ' . $rate->amount . ' дней',
                            'price' => doubleval($rate->price)
                        ];
                    }

                    $servicesParams[$serv->id] = [
                        'title' => $serv->name,
                        'description' => Service::extenedDescription($serv, ['transport_id' => $selectedId]),
                        'checked' => true,
                        'options' => $options
                    ];
                }
                ?>
                <?= PromoPayment::widget([
                        'url' => '/payment/transport/pay/',
                        'mainServices' => $servicesParams,
                        'item_id' => $selectedId
                    ]
                )
                ?>
            <?php endif; ?>
        </div>
        <?php Pjax::end() ?>
    </div>
</main>
<?php
$js = <<<JS
jQuery(document).on('change', "#tr_select", function(event){
    var id = $(this).val();

    if( id == 'add' ){
        document.location.href="/account/signup-transport/";
        return false;
    }

    $('#serviceList').css('opacity', 0.3);

    $.pjax.reload('#pjax', {
        history: false,
        data: {id:$(this).val()}
      })
});
JS;

$this->registerJs($js);
?>
