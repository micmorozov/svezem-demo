<?php
use common\models\Cargo;
use common\models\Payment;
use common\models\Transport;
use common\models\TransportLocation;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/* @var $model Payment */
$loadLocations = $model->cargoLocationsFrom;
$loadLocations = array_shift($loadLocations);
$unloadLocations = $model->cargoLocationsTo;
$unloadLocations = end($unloadLocations);
$route = $loadLocations->city->country->title_ru . ', ' . $loadLocations->city->title_ru . ' - ' . $unloadLocations->city->country->title_ru . ', ' . $unloadLocations->city->title_ru;
$top_until = Yii::$app->formatter->asDate($model->top_until, 'dd.MM.y');
$cargoCategories = $model->cargoCategory->category;
$loading_date = ($model->is_any_date == Cargo::ANY_DATE) ? "в любое время" : "с " . $model->date_from . " по " . $model->date_to;
?>

<div class="item-fixed-adv">
  <div class="item-left">
    <p><?= $route ?></p>

    <p>
      <span>Категория: <b><?= $cargoCategories?></b></span>
      <span>Загрузка: <b><?= $loading_date ?></b></span>
    </p>
  </div>
  <div class="item-right">
    <p>
      <b>До <?= $top_until ?></b>
      <?php if ($model->id && $model->status == Cargo::STATUS_ACTIVE): ?>
        <?= Html::a('Продлить',
          ['/cabinet/service/top', 'cargo_id' => $model->id],
          ['class' => 'btn btn-blue']
        ) ?>
      <?php endif; ?>
    </p>
  </div>
</div>
