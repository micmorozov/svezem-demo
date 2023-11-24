<?php

use common\models\Service;
use common\models\Transport;
use frontend\assets\ButtonLoaderAsset;
use frontend\modules\payment\widget\PromoPayment;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\web\View;

$this->title = "Личный кабинет - Оплата услуг";

/** @var $item_id int */
/** @var $services Service[] */
/** @var $remain_services Service[] */
/** @var $this View */
/** @var $transport Transport */

ButtonLoaderAsset::register($this);

//Формируем опции главной услуги
if( !empty($services)){

    foreach($services as $service){
        $options = [];
        foreach($service->serviceRates as $rate){
            $options[$rate->id] = [
                'description' => 'на '.$rate->amount.' дней',
                'price' => doubleval($rate->price)
            ];
        }

        $mainServiceParams[$service->id] = [
            'title' => $service->name,
            'description' => Service::extenedDescription($service, ['transport_id' => $item_id]),
            'checked' => true,
            'options' => $options
        ];
    }
}

//Формируем опции оставшихся услуг
$remains_serv = Service::find()
    ->with(['serviceRates' => function($q){
        /** @var $q ActiveQuery */
        $q->orderBy(['service_id' => SORT_ASC]);
    }])
    ->where(['id' => $remain_services])
    ->all();

$servicesParams = [];
foreach($remains_serv as $serv){
    $options = [];

    foreach($serv->serviceRates as $rate){
        $options[$rate->id] = [
            'description' => 'на '.$rate->amount.' дней',
            'price' => doubleval($rate->price)
        ];
    }

    $servicesParams[$serv->id] = [
        'title' => $serv->name,
        'description' => Service::extenedDescription($serv, ['transport_id' => $item_id]),
        //если не передан сервис, то выбираем все галочки
        'checked' => empty($services) ? true : false,
        'options' => $options
    ];
}

if($transport->city_from == $transport->city_to){
    $label = 'по '.GeographicalNamesInflection::getCase($transport->cityFrom->title_ru, Cases::DATIVE);
} else{
    $label = $transport->cityFrom->title_ru.' &raquo; '.$transport->cityTo->title_ru;
}
?>
<main class="content">
    <div class="container">
        <?php if($transportStatus = Yii::$app->session->getFlash('Transport')): ?>
            <div class="text-center">
                <?= Html::img("/img/icons/payment/add-success.png", [
                    'class' => 'img'
                ]); ?>
                <?php
                $messageStatus = $transportStatus == 'created' ? 'добавлен' : 'обновлен';
                ?>
                <div class="h4 text-uppercase">Поздравляем!<br/>Ваш транспорт успешно <?= $messageStatus ?></div>
            </div>
        <?php else: ?>
            <div class="page-title">
                <h1 class="h3 text-uppercase"><b>Оплата услуг</b></h1>
                <div class="content__subtitle"><?= $label ?></div>
            </div>
        <?php endif ?>

        <?= PromoPayment::widget([
                'label' => $label,
                'url' => 'pay/',
                'mainServices' => $mainServiceParams??null,
                'services' => $servicesParams,
                'item_id' => $item_id
            ]
        )
        ?>
    </div>
</main>
