<?php
use common\models\Payment;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */
?>

<?php
$descr = 'Svezem.ru: ' . $payment->serviceRate->service->name;
$form = ActiveForm::begin([
//  'action' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
  'action' => 'https://www.paypal.com/cgi-bin/webscr',
  'method' => 'post',
  'id' => 'payForm'
]); ?>

  <?= Html::hiddenInput('cmd', '_xclick') ?>
  <?= Html::hiddenInput('charset', 'UTF-8') ?>
  <?= Html::hiddenInput('item_name', $descr) ?>
  <?= Html::hiddenInput('item_number', $payment->id) ?>
  <?= Html::hiddenInput('amount', $payment->amount) ?>
  <?= Html::hiddenInput('currency_code', 'RUB') ?>
  <?= Html::hiddenInput('business', Yii::$app->paypal->login) ?>
  <?= Html::hiddenInput('return', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
  <?= Html::hiddenInput('notify_url', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/paypal-callback'])) ?>
<?php ActiveForm::end(); ?>
<script>
  $('#payForm').submit();
</script>

