<?php
use common\models\Payment;
use frontend\modules\cabinet\models\JuridicalForm;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/** @var Payment $payment */

$juridicalForm = new JuridicalForm();
?>

<p class="number-step">
   <b>4. Укажите реквезиты плательщика</b>
</p>

<?php $form = ActiveForm::begin([
  'action' => ['payment/juridical']
]); ?>

  <?= Html::hiddenInput('summ', $payment->amount) ?>
  <?= $form->field($juridicalForm, 'payment_id', ['template' => "{input}"])->hiddenInput(['value' => $payment->id]) ?>
  <?= $form->field($juridicalForm, 'name', ['template' => "{label} {input}{error}"])->textInput() ?>
  <?= $form->field($juridicalForm, 'address', ['template' => "{label} {input}{error}"])->textInput() ?>

  <div class="form-group">
    <?= Html::submitButton('Оплатить', ['class' => 'btn btn-blue']) ?>
  </div>
<?php ActiveForm::end(); ?>