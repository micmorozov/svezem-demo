<?php
use common\models\Payment;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */
$sign = md5(Yii::$app->robokassa->client_id . ":" . $payment->amount . ":" . $payment->id . ":" . Yii::$app->robokassa->password1);
$descr = 'Svezem.ru: ' . $payment->serviceRate->service->name;
?>

<?php $form = ActiveForm::begin([
  'action' => 'https://auth.robokassa.ru/Merchant/Index.aspx',
  'method' => 'post', 
  'id' => 'payForm'
]); ?>

  <?= Html::hiddenInput('MrchLogin', Yii::$app->robokassa->client_id) ?>
  <?= Html::hiddenInput('OutSum', $payment->amount) ?>
  <?= Html::hiddenInput('InvId', $payment->id) ?>
  <?= Html::hiddenInput('Desc', $descr) ?>
  <?= Html::hiddenInput('SignatureValue', $sign) ?>
  <?= Html::hiddenInput('successUrl', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
  <?= Html::hiddenInput('failUrl', Yii::$app->urlManager->createAbsoluteUrl(['/cabinet/payment/return', 'payment_id' => $payment->id])) ?>
<?php ActiveForm::end(); ?>
<script>
	$('#payForm').submit();
</script>

