<?php

use common\models\Payment;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var Payment $payment */
/* @var string $qr */

$companyRequisites = Yii::$app->params['requisites'];

$this->title = 'Оплата счета';

?>
<style>
    /*table.border {*/
    /*    border-spacing: 0;*/
    /*    border-collapse: collapse;*/
    /*}*/

    /*table.border td, th {*/
    /*    border: 1px solid black;*/
    /*    padding-left: 4px;*/
    /*    padding-right: 4px;*/
    /*}*/

    /*tr th {*/
    /*    padding: 10px;*/
    /*}*/
    /*tr td {*/
    /*    padding: 6px 10px;*/
    /*}*/

</style>
<main class="content cargo-list__wrap">
    <div class="container">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $this->title ?></b></h1>
        </div>
        <br>
        <div style="padding-bottom: 12px">
            <h3>
                Счет № <?= $payment->id ?>
                <small>от&nbsp;<?= Yii::$app->formatter->asDate($payment->created_at) ?></small>
            </h3>
        </div>
        <table style="width: 100%" class="table table-bordered">
            <thead>
            <tr style="text-align:center">
                <th>Наименование услуги</th>
                <th>Кол-во</th>
                <th>Ед. изм</th>
                <th>Цена за ед. (руб.)</th>
                <th>Сумма (руб.)</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payment->paymentDetails as $detail): ?>
                <tr>
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
            </tbody>
            <tfoot>
            <tr>
                <td colspan="4">НДС</td>
                <td style="text-align: right">без НДС</td>
            </tr>
            <tr>
                <td colspan="4"><b>Всего к оплате</b></td>
                <td style="text-align: right"><b><?= number_format($payment->amount, 2, '.', ' ') ?> руб.</b></td>
            </tr>
            </tfoot>
        </table>
        <br/>
        <br/>
        <h3 class="h3">Реквезиты платежа</h3>
        <div class="row">
            <div class="col-md-4 col-md-push-8 hidden-xs hidden-sm">
                <img class="img img-responsive" style="margin: 10px auto" alt="QR code"
                     src="data:image/png;base64,<?= $qr; ?>">
            </div>
            <div class="col-md-8 col-md-pull-4">
                <table class="table">
                    <tr>
                        <td>Наименование получателя платежа</td>
                        <td><?= $companyRequisites['organization'] ?></td>
                    </tr>
                    <tr>
                        <td>ИНН</td>
                        <td><?= $companyRequisites['inn'] ?></td>
                    </tr>
                    <tr>
                        <td>КПП</td>
                        <td><?= $companyRequisites['kpp'] ?></td>
                    </tr>
                    <tr>
                        <td>Номер счёта получателя платежа</td>
                        <td><?= $companyRequisites['account'] ?></td>
                    </tr>
                    <tr>
                        <td>Наименование банка</td>
                        <td><?= $companyRequisites['bank'] ?></td>
                    </tr>
                    <tr>
                        <td>БИК</td>
                        <td><?= $companyRequisites['bic'] ?></td>
                    </tr>
                    <tr>
                        <td>Кор./сч.</td>
                        <td><?= $companyRequisites['corr_account'] ?></td>
                    </tr>
                    <tr>
                        <td>Наименование платежа</td>
                        <td>Оплата интернет-услуг сервиса svezem.ru по счету <?= $payment->id ?>
                            от <?= Yii::$app->formatter->asDate($payment->created_at) ?></td>
                    </tr>
                </table>
            </div>

        </div>
        <?= Html::a('<i class="fas fa-print"></i> Распечатать счет для оплаты', '#', [
            'class' => 'btn btn-default text-uppercase',
            'onclick' => "printDiv('printableArea')"
        ]) ?>


    </div>
</main>
<script>
    function printDiv(divName) {
        var w = 800;
        var h = 600;
        var left = (screen.width / 2) - (w / 2);
        var top = (screen.height / 2) - (h / 2);

        var mywindow = window.open('https://<?= Yii::getAlias('@domain') ?>/payment/juridical/receipt/?payment=<?= $payment->id ?>', 'receipt', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
    }
</script>
