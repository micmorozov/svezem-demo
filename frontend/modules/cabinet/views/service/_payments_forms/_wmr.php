<?php
use common\models\Payment;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */
?>

<?php
$descr = 'Svezem.ru: ' . $payment->serviceRate->service->name;
$form = ActiveForm::begin([
    'action' => 'https://merchant.webmoney.ru/lmi/payment.asp',
    'method' => 'post',
    'options' => ['accept-charset' => 'windows-1251'],
    'id' => 'payForm'
]); ?>

<?= Html::hiddenInput('LMI_PAYMENT_AMOUNT', $payment->amount) ?>
<?= Html::hiddenInput('LMI_PAYMENT_DESC_BASE64', base64_encode($descr)) ?>
<?= Html::hiddenInput('LMI_PAYMENT_NO', $payment->id) ?>
<?= Html::hiddenInput('LMI_PAYEE_PURSE', Yii::$app->wmr->wallet) ?>
<?= Html::hiddenInput('LMI_SIM_MODE', '0') ?>
<?= Html::hiddenInput('LMI_RESULT_URL', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/webmoney-callback'])) ?>
<?= Html::hiddenInput('LMI_SUCCESS_URL', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
<?= Html::hiddenInput('LMI_SUCCESS_METHOD', '2') ?>
<?= Html::hiddenInput('LMI_FAIL_URL', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
<?= Html::hiddenInput('LMI_FAIL_METHOD', '2') ?>
<?php ActiveForm::end(); ?>
<script>
	$('#payForm').submit();
</script>

