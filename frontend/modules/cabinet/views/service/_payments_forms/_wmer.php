<?php
use common\models\Payment;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */

$params = [
    'SYSTEM_NAME' => Yii::$app->name,
    'PAYMENT_USERNAME' => Yii::$app->wmer->login,
    'PAYMENT_PASSWORD' => md5(Yii::$app->wmer->payment_password),
    'PAYMENT_ORDER_ID' => $payment->id,
    'PAYMENT_AMOUNT' => $payment->amount,
    'PAYMENT_DESCRIPTION' => 'Svezem.ru: ' . $payment->serviceRate->service->name,
    'RESULT_URL' => Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/wmer-callback']),
    'SUCCESS_URL' => Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id]),
    'FAIL_URL' => Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id]),
];
$params['SIGN'] = md5(implode('::', $params));
$params['SOURCE_CURRENCY'] = 'bvcom2_rur';

?>

<?php $form = ActiveForm::begin([
  'action' => 'https://www.wmer.ru/ru/moneypool,merchant/',
    'id' => 'payForm'
]); ?>

<?php
foreach ($params as $k => $v) {
  echo Html::hiddenInput(htmlspecialchars($k), htmlspecialchars($v));
}
?>
<?php ActiveForm::end(); ?>
<script>
  $('#payForm').submit();
</script>

