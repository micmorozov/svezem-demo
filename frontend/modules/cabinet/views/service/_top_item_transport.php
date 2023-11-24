<?php
use common\models\Cargo;
use common\models\Payment;
use common\models\Transport;
use common\models\TransportLocation;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/* @var $model Payment */
$loadLocations = $model->getLocationsString(TransportLocation::TYPE_LOADING);
$unloadLocations = $model->getLocationsString(TransportLocation::TYPE_UNLOADING);
$route = $loadLocations . ' - ' . $unloadLocations;
$top_until = Yii::$app->formatter->asDate($model->top_until, 'dd.MM.y');
$transportCategories = count($model->cargoCategories) ? implode(",", ArrayHelper::getColumn($model->cargoCategories, 'category')) : "";
$loading_date = ($model->is_any_date == Transport::ANY_DATE) ? "в любое время" : "с " . $model->date_from . " по " . $model->date_to;
?>

<div class="item-fixed-adv">
  <div class="item-left">
    <p><?= $route ?></p>

    <p>
      <span>Категория: <b><?= $transportCategories?></b></span>
      <span>Загрузка: <b><?= $loading_date ?></b></span>
    </p>
  </div>
  <div class="item-right">
    <p>
      <b>До <?= $top_until ?></b>
      <?php if ($model->id && $model->status == Transport::STATUS_ACTIVE): ?>
        <?= Html::a('Продлить',
          ['/cabinet/service/top', 'transport_id' => $model->id],
          ['class' => 'btn btn-blue']
        ) ?>
      <?php endif; ?>
    </p>
  </div>
</div>
