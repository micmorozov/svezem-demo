<?php

use common\helpers\PhoneHelpers;
use yii\helpers\Html;
?>
<div>
    Телефон заказчика (доступен только Вам):<br><a href="tel:<?=PhoneHelpers::formatter($model->profile->contact_phone, '', true) ?>" id="phone_<?= $model->id ?>"><b><?= PhoneHelpers::formatter($model->profile->contact_phone) ?></b></a><br><br><br>
    После доставки груза укажите сумму сделки и нажмите "Завершить сделку". Если не договорились с заказчиком, нажмите "Отменить бронь"<br>
    <?= Html::input('text', 'price', '', [
        'class' => 'form-control',
        'id' => 'price_'.$model->id,
        'autocomplete' => 'off',
        'placeholder' => 'После завершения сделки укажите сумму в рублях'
    ]);
    ?>
    <br>
    <?= Html::button('Завершить сделку', [
        'class' => 'search__btn content__btn save_booking',
        'data-cargo_id' => $model->id
    ]) ?>
    &nbsp;&nbsp;&nbsp;
    <?= Html::a('отменить бронь', '#', [
        'class' => 'cancel_booking',
        'data-cargo_id' => $model->id
    ]) ?>
</div>
