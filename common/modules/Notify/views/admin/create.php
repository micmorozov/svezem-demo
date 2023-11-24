<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\modules\Notify\models\NotifyRule */

$this->title = 'Редактировать правило';
$this->params['breadcrumbs'][] = ['label' => 'Правила уведомлений', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notify-rule-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
