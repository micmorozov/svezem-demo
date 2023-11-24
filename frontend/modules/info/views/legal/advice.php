<?php

use yii\web\View;

/** @var $this View */

$this->title = 'Бесплатная консультация юриста';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Бесплатная консультация юриста'
]);

?>
<main class="content">
    <div class="container post">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Бесплатная консультация юриста по вопросам грузоперевозки</b></h1>
        </div>
        <p style="text-align: left">
            Задайте любой вопрос в форме ниже. Юристы ответят в течении 15 минут. Все консультации бесплатны.<br>
            Так же бесплатную консультацию можно получить по телефону <b><a href="tel:88003508413,692">8 (800) 350-84-13 добавочный 692</a></b>. Звонок по России бесплатный. <br><br>
            На вопросы отвечают юристы <a href="https://juristpomozhet.ru/?cd_referral=3c328811b56792b48b8192f270c3ad18" target="_blank" rel="nofollow">Онлайн сервиса юридической помощи Правовед.ру</a>
        </p>
        <br>
        <div id="feedot--inline-form--10029"></div>
    </div>
</main>

<?= $this->render('//common/_feedot-loader') ?>
