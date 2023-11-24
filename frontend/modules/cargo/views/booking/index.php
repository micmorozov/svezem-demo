<?php

use common\models\ServiceRate;
use frontend\modules\payment\widget\PromoPayment;
use common\models\Service;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Service[] $services */
/** @var \common\components\bookingService\Service $bookingService */

$this->title = "Приоритетный доступ к заказам";

if (!empty($services)) {

    foreach ($services as $service) {
        $options = [];
        foreach ($service->serviceRates as $rate) {
            $options[$rate->id] = [
                'description' => 'на ' . $rate->amount . ' дней',
                'price' => doubleval($rate->price)
            ];
        }

        $mainServiceParams[$service->id] = [
            'title' => 'Тариф "' . $service->name . '"',
            'description' => Service::extenedDescription($service),
            'checked' => $service->id == Service::BOOKING_BUSINESS ? true : false,
            'options' => $options
        ];
    }
}
?>
<main class="content">
    <div class="container">
        <div class="page-title">
            <h1 class="h3 text-uppercase">
                <b>Приоритетный доступ к заказам</b>
            </h1>
            <div class="content__subtitle">
                <b>Приоритетный доступ к заказам</b> - это платная услуга, позволяющая получить контакты заказчика раньше,
                чем они отобразятся на сайте в публичном доступе. Дополнительно груз можно забронировать. Такой груз становится
                недоступен для других перевозчиков.

                <!--br><br>
                Каждый тариф имеет определенное количество заявок в работе, срок действия и ограниченное количество
                бронирований в сутки.<br>
                Предположим, Вы оплатили 10 бронирований на 30 дней. Это значит, что в течении 30 дней Вы можете взять в
                работу(забронировать) только 10 заказов.
                При этом можно отменить бронь, тогда появится возможность забронировать другой заказ. Т.е. 10
                бронирований - это 10 заказов находящихся в работе одновременно. Однако, в сутки по этому тарифу можно
                забронировать не более 10 заявок.-->

            </div>
        </div>
        <?php if ($bookingService->canBooking()): ?>
            <?php
            $rate = ServiceRate::findOne($bookingService->getTariffId());
            ?>
            <div>
                <p><b>У вас: Тариф "<?= $rate->service->name ?>"</b></p>
                <p>Доступно контактов в сутки: <?= $bookingService->dayLimitRemain() ?>
                    из <?= $bookingService->getDayLimit() ?>
                    <?php
                    $booking_dayLimitTTL = $bookingService->getDayLimitTTL();
                    if ($booking_dayLimitTTL > 0):
                        ?>
                        (обновление через <?= date('H:i', $booking_dayLimitTTL) ?> часов)
                    <?php endif; ?>
                </p>
                <p>Доступно бронирований: <?= $bookingService->getBookingRemain() ?>
                    из <?= $bookingService->getBookingLimit() ?></p>
                <?php
                $relativeTime = $bookingService->getExpire();
                //Если истекает больше чем через один день,
                //то прибавляем еще сутки для корректного отображения
                $relativeTime += ($relativeTime > time() + 86400 ? 86400 : 0);
                ?>
                <p>Доступ к бронированию: до <?= Yii::$app->formatter->asDate($bookingService->getExpire()); ?>
                    (<?= Yii::$app->formatter->asRelativeTime($relativeTime); ?>)</p>
                <div class="services__item-contact text-uppercase">
                    <?= Html::a('Перейти к списку заказов', Url::toRoute('/cabinet/cargo-booking/')) ?>
                </div>
            </div>

            <br>
        <?php endif; ?>

        <?= PromoPayment::widget([
                'label' => '',
                'url' => 'pay/',
                'mainServices' => $mainServiceParams ?? null,
                'onlyOne' => true,
                'authUserOnly' => true
            ]
        )
        ?>
    </div>
</main>
