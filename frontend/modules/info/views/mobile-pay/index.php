<?php
use yii\helpers\Html;
use yii\web\View;

/** @var $this View */

$this->title = 'Информация о платных услугах для Абонентов мобильных Операторов';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Информация о платных услугах для Абонентов мобильных Операторов'
]);

?>
<main class="content">
    <div class="container post">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Информация о платных услугах для Абонентов мобильных Операторов</b></h1>
        </div>

        <p style="text-align: left">
            ООО "Иновика", ИНН 2463082935, ОГРН 1062463061550, Юр. адрес: 660074, г.Красноярск, ул.Борисова 14, стр. 2, оф. 610
        </p>
        <br>

        <p style="text-align: left">
            <u>Абонентам Билайн:<br>
                <?= Html::img('/img/info/mobile-pay/logo-beeline.png', [
                    'width'=>"195",
                    'height'=>"75",
                    'align'=>"left",
                    'style' => 'margin: 10px;'
                ]) ?>
            </u>
            Минимальная сумма одного Платежа — 10 <br>
            Максимальная сумма одного Платежа — 5 000 <br>
            Максимальная сумма платежей за день — 15 000 <br>
            Максимальная сумма платежей в неделю — 40 000 <br>
            Максимальная сумма платежей в месяц 40'000 руб., максимум 50 транзакций <br>
            После списания суммы покупки на счете должно остаться не менее 50 руб. (для абонентов предоплатной системы расчетов)..</p><br>
        <p style="text-align: left"><b>Недоступна мобильная
                коммерция абонентам:</b><br>
            1. С тарифом “Простая логика”<br>
            2. Включенные услуги: “Безумные дни”, “Безлимит”
            внутри сети.<br>
            <br><b>Как пополнить авансовый счёт?</b><br>— Наличными в любом пункте приема платежей. Сообщите
            кассиру или укажите в квитанции федеральный номер
            своего мобильного телефона в 10-значном формате. <br>— В банкоматах с помощью пластиковой карты:<br>— С помощью Единой карты оплаты:<br>Наберите с вашего мобильного телефона команду: *104*
            код карты * номер телефона # вызов<br>Внимание: вне зависимости от способа пополнения
            авансового счета, изменяйте первую цифру кода своего
            телефона на 6 (например: 903 указывайте как 603).<br>
            <br><b>Как узнать остаток на счёте?</b><br>Уточнить остаток средств на специальном авансовом
            счете вы можете, набрав бесплатную команду *222#
            вызов<br>Все операции с вашим специальным авансовым счетом
            конфиденциальны.</p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left">
            <u>Абонентам Мегафон:<br>
                <?= Html::img('/img/info/mobile-pay/logo-megafon.png', [
                    'width'=>"195",
                    'height'=>"75",
                    'align'=>"left",
                    'style' => 'margin: 10px;'
                ]) ?>
            </u>
        </p>
        <p style="text-align: left">
            Минимальная сумма одного Платежа — 1 руб. <br>
            Минимальная сумма остатка после оплаты — 0 руб. <br>
            Максимальная сумма одного Платежа — 15 000 руб. <br>
            Максимальная сумма Платежей в сутки — 40 000 руб. <br>
            Максимальная сумма Платежей в месяц — 40 000 руб. <br>
            <br>
            <?= Html::a('<u>Условия оказания услуги «Мобильные платежи»</u>', '/docs/mobile-pay/oferta_megafon_payy.pdf', ['target'=>"_blank", 'rel'=>'nofollow']) ?>
        </p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left">
            <u>Абонентам МТС:<br>
                <?= Html::img('/img/info/mobile-pay/logo-mts.png', [
                    'width'=>"195",
                    'height'=>"75",
                    'align'=>"left",
                    'style' => 'margin: 10px;'
                ]) ?>
            </u>
            Минимальная сумма одного Платежа — 1 руб. <br>
            С каждого успешного платежа МТС взимает с абонента комиссию в размере 10 рублей (в том числе НДС)<br>
            Неснижаемый остаток на лицевом счете после совершения оплаты — 10 руб. <br>
            Для абонентов Санкт-Петербурга и Ленинградской области — 20 руб. <br>
            Максимальная сумма платежа: <br>
            1 000 - для услуг мобильной связи и Yota <br>
            5 000 - для остальных услуг и категорий <br>
            Максимальное число платежей в сутки: не более 5. <br>
            Максимальная сумма платежей в сутки: 5000 руб. или не более 40 000 руб. в месяц. <br>
        </p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left"><u>Абонентам Tele2:</u><br>
            <u>
                <?= Html::img('/img/info/mobile-pay/logo-tele2.png', [
                    'width'=>"195",
                    'height'=>"75",
                    'align'=>"left",
                    'style' => 'margin: 10px;'
                ]) ?>
            </u>
            <?= Html::a('<u>Оферта оператора</u>', '/docs/mobile-pay/oferta_tele2_payy.pdf', ['target'=>'_blank', 'rel'=>'nofollow']) ?>
            <br>
        </p>
        <p style="text-align: left"><br>
            При оплате с лицевого счета Tele2 действуют
            следующие лимиты:<br>
            Минимальная сумма одного Платежа - от 1 до 5 000 руб. <br>
            Неснижаемый остаток на лицевом счете после совершения оплаты — 0 руб. <br>
            Для абонентов Санкт-Петербурга и Ленинградской области — 20 руб. <br>
            Максимальная сумма платежа: <br>
            1 000 - для услуг мобильной связи и Yota <br>
            5 000 - для остальных услуг и категорий <br>
            Максимальная сумма платежей в сутки: 5000 руб. или не более 40 000 руб. в месяц. <br>
            <br>
            Для абонентов Санкт-Петербурга и Ленинградской
            области, Краснодарского края и Республики Адыгея,
            Нижегородской и Брянской областей сервис «Tele2
            кошелек» доступен по истечении 60 дней с момента
            активации SIM-карты в сети Tele2.</p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left"><u>Абонентам Йота:</u></p>
        <p style="text-align: left">
            <u>
                <?= Html::img('/img/info/mobile-pay/logo-yota.png', [
                    'width'=>"195",
                    'height'=>"75",
                    'align'=>"left",
                    'style' => 'margin: 10px;'
                ]) ?>
            </u>
            Минимальная сумма одного Платежа — 1 руб. <br>
            Максимальная сумма одного Платежа — 15 000 руб. <br>
            Максимальная сумма Платежей в сутки — 40 000 руб. <br>
            Максимальная сумма Платежей в месяц — 40 000 руб.<br>
            <?= Html::a('<u>Оферта оператора</u>', '/docs/mobile-pay/oferta_tele2_payy.pdf', ['target'=>'_blank', 'rel'=>'nofollow']) ?>
        </p>
        <p style="text-align: left">&nbsp;</p>
        <p style="text-align: left">&nbsp;</p>
        <br><br>
    </div>
</main>
