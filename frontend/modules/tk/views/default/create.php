<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Добавление новой транспортной компании';
$this->registerMetaTag([
	'name' => 'description',
	'content' => 'Добавление новой транспортной компании'
]);
?>
<div class="container">
    <div class="page-title">
        <h1 class="h3 text-uppercase"><b>Добавление новой транспортной компании</b></h1>
    </div>
   	<div class="bl-white">

	      <?php $form = ActiveForm::begin([
	      		'id' => 'createtk-form',
	      		'fieldConfig' => [
	      			'template' => "<p><span>{label} {input}{error}</span></p>"
	      		],
	      ]); ?>
			<?= $form->field($model, 'name');  ?>
			<?= $form->field($model, 'email');  ?>
			<?= $form->field($model, 'url');  ?>
			<?= $form->field($model, 'phone')->textInput(['class' => 'contact_phone']);  ?>
			<?= $form->field($model, 'body')->textArea(['rows' => 6]) ?>

	      <div class="form-group">
	        <?= Html::submitButton('Добавить транспортную компанию', ['class' => 'btn btn-primary', 'name' => 'contact-button']) ?>
	      </div>

	      <?php ActiveForm::end(); ?>
	</div>
</div>

