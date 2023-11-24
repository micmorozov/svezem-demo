<?php
use common\models\Payment;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */
?>

<?php
$descr = 'Svezem.ru: ' . $payment->serviceRate->service->name;
$form = ActiveForm::begin([
  'id' => 'payForm',
  'action' => 'https://money.yandex.ru/quickpay/confirm.xml'
]);
?>
  <?= Html::hiddenInput('receiver', Yii::$app->yandex->money_wallet) ?>
  <?= Html::hiddenInput('formcomment', $descr) ?>
  <?= Html::hiddenInput('short-dest', $descr) ?>
  <?= Html::hiddenInput('targets', $descr) ?>
  <?= Html::hiddenInput('quickpay-form', 'shop') ?>
  <?= Html::hiddenInput('paymentType', 'PC') ?>
  <?= Html::hiddenInput('label', $payment->id) ?>
  <?= Html::hiddenInput('sum', $payment->amount) ?>
  <?= Html::hiddenInput('successURL', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>

<?php ActiveForm::end(); ?>
<script>
  $('#payForm').submit();
</script>
