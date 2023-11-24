<?php
use common\models\Payment;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */
?>

<?php $form = ActiveForm::begin([
  'action' => 'https://w.qiwi.com/order/external/create.action',
  'method' => 'get', 
  'id' => 'payForm'
]); ?>

  <?= Html::hiddenInput('from', Yii::$app->qiwi->id) ?>
  <?= Html::hiddenInput('summ', $payment->amount) ?>
  <?= Html::hiddenInput('currency', 643) ?>
  <?= Html::hiddenInput('comm', 'Svezem.ru: ' . $payment->serviceRate->service->name) ?>
  <?= Html::hiddenInput('txn_id', $payment->id) ?>
  <?= Html::hiddenInput('successUrl', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
  <?= Html::hiddenInput('failUrl', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
<?php ActiveForm::end(); ?>
<script>
  $('#payForm').submit();
</script>

