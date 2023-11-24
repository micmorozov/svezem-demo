<html>
<head>
    <title>Счет для оплаты</title>
    <style>
        table.border {
            border-spacing: 0;
            border-collapse: collapse;
        }

        table.border td, th {
            border: 1px solid black;
            padding-left: 4px;
            padding-right: 4px;
        }
    </style>
</head>
</html>
<?php

use common\helpers\Utils;
use common\models\Payment;
use common\models\PaymentRequisites;

/* @var yii\web\View $this */
/* @var Payment $payment */
/* @var PaymentRequisites $requisites */
/** @var string $qr */

$companyRequisites = Yii::$app->params['requisites'];
?>
<table style="width: 100%" class="border">
    <tr>
        <td rowspan="4" style="width: 100px; border: 0"><img src="data:image/png;base64,<?= $qr; ?>"></td>
        <td rowspan="2" colspan="2"><?= $companyRequisites['bank'] ?><br><br>Банк получателя</td>
        <td style="border-bottom: 0; height: 18px">БИК</td>
        <td style="border-bottom: 0"><?= $companyRequisites['bic'] ?></td>
    </tr>
    <tr>
        <td style="height: 38px; vertical-align: top">Сч. №</td>
        <td style="border-top: 0; vertical-align: top"><?= $companyRequisites['corr_account'] ?></td>
    </tr>
    <tr>
        <td>ИНН <?= $companyRequisites['inn'] ?></td>
        <td>КПП <?= $companyRequisites['kpp'] ?></td>
        <td style="border-bottom: 0">Сч. №</td>
        <td style="border-bottom: 0"><?= $companyRequisites['account'] ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <?= $companyRequisites['organization'] ?>
            <br><br>
            Получатель
        </td>
        <td style="border-top: 0;"></td>
        <td style="border-top: 0"></td>
    </tr>
</table>
<br>
<h2>Счет № КИ<?= sprintf('%1$08d', $payment->id); ?> от <?= Yii::$app->formatter->asDate($payment->created_at) ?>
    <br>
    <hr style="width: 100%">
</h2>

<table style="width: 100%">
    <tr>
        <td>Поставщик:</td>
        <td style="font-weight:bold">
            ИНН <?= $companyRequisites['inn'] ?>
            КПП <?= $companyRequisites['kpp'] ?> <?= $companyRequisites['organization'] ?> <?= $companyRequisites['post_address'] ?>
            , <span style="white-space: nowrap">тел: <?= $companyRequisites['phone'] ?></span>
        </td>
    </tr>
    <tr>
        <td>Покупатель:</td>
        <td style="font-weight:bold">
            ИНН <?= $requisites->inn ?>
            <?= $requisites->kpp ? 'КПП '.$requisites->kpp : '' ?>
            <?= $requisites->organization ?>
            <?= $requisites->jur_address ?>
        </td>
    </tr>
</table>

<br><br>

<table style="width: 100%" class="border">
    <tr style="text-align:center">
        <th>№</th>
        <th>Товар</th>
        <th>Кол-во</th>
        <th>Ед.</th>
        <th>Цена</th>
        <th>Сумма</th>
    </tr>
    <?php foreach ($payment->paymentDetails as $index => $detail): ?>
        <tr>
            <td style="text-align: center"><?= $index + 1 ?></td>
            <td>Услуга "<?= $detail->service->name ?>"</td>
            <td style="text-align: right"><?= $detail->count ?></td>
            <td style="text-align: right"><?= $detail->unit ?></td>
            <td style="text-align: right">
                <?= $detail->service->open_price
                    ? number_format($detail->serviceRate->price, 2, '.', ' ')
                    : '-' ?>
            </td>
            <td style="text-align: right"><?= number_format($detail->amount, 2, '.', ' ') ?></td>
        </tr>
    <?php endforeach; ?>
    <tr style="font-weight:bold">
        <td colspan="5" style="text-align: right; border: 0">Итого:</td>
        <td style="text-align: right; border: 0"><?= number_format($payment->amount, 2, '.', ' ') ?></td>
    </tr>
    <tr style="font-weight:bold">
        <td colspan="3" style="border: 0"></td>
        <td colspan="2" style="text-align: right; border: 0">В том числе НДС:</td>
        <td style="border: 0"></td>
    </tr>
</table>

<br><br>

<br>
<span class=font_style>Всего наименований 1, на сумму <?= number_format($payment->amount, 2, '.', ' ') ?> руб.
    <br>
    <b>
        <?= Utils::digit2string($payment->amount, 1, ['', '', '']); ?> рублей
        <?php $decimal = ($payment->amount - (int)$payment->amount)*100 ?>
        <?= sprintf('%1$02d', $decimal).' '.Yii::t(
            'app',
            '{n, plural, one{копейка} few{копейки} many{копеек} other{копеек}}',
            ['n' => $decimal]
        ); ?>
    </b>
    <br>
    </span>
<br>
<hr style="width: 100%">
<br>
<span class=font_style><b>Руковитель</b> _____________(<?= $companyRequisites['supervisor'] ?>)
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <b>Бухгалтер</b> _____________(<?= $companyRequisites['accountant'] ?>)
    </span>
<script>
    window.print();
    setTimeout(window.close, 3000);
</script>
