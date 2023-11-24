<?php
use common\models\Cargo;
use common\models\Offer;
use frontend\modules\cabinet\models\ResponseSearch;
use yii\helpers\Html;

/* @var $model Offer */
if ($model->cargo->status == Cargo::STATUS_ACTIVE){
    $status = ResponseSearch::STATUS_OPEN;
}
else{
    if ($model->status == Offer::STATUS_ACCEPTED){
        $status = ResponseSearch::STATUS_CHOSEN_ME;
    }
    else{
        $status = ResponseSearch::STATUS_CHOSEN_NOT_ME;
    }
}
$date = ($model->cargo->is_any_date == Cargo::ANY_DATE) ? "В любое время" : $model->cargo->date_from . " - " . $model->cargo->date_to;
$loadLocations = $model->cargo->cargoLocationsFrom;
$loadLocations = array_shift($loadLocations);
$unloadLocations = $model->cargo->cargoLocationsTo;
$unloadLocations = end($unloadLocations);
$route = '(' . $loadLocations->city->country->title_ru . ', ' . $loadLocations->city->title_ru . ' - ' . $unloadLocations->city->country->title_ru . ', ' . $unloadLocations->city->title_ru . ')';
if ($model->cargo->minOffer !== null){
    $minOfferString = "<strong>" . number_format($model->cargo->minOffer->price, 0, '.', ' ') . "</strong> руб. " . $model->cargo->minOffer->getNdsLabel();
}
else{
    $minOfferString = "не поступало";
}
$myOfferString = isset($model->price) ? (number_format($model->price, 0, '.', ' ') . " руб. " . $model->getNdsLabel()) : "не поступало";
?>

<div class="item-response">
  <div class="res-header">
    <div class="res-title">
      <b><?= Html::a($model->cargo->name, ['/cargo/' . $model->cargo_id . '/' . $model->cargo->slug . '.html'])?></b>
    </div>
    <div class="head-claim">
      <?= ResponseSearch::getStatusLabel($status, true)?>
    </div>
  </div>
  <div class="res-info">
    <div class="res-left">
      <p><?= $date ?> <?= $route ?></p>
      <p>Минимальное предложение: <b><?= $minOfferString?></b></p>
    </div>
    <div class="res-price"><span>Ваше предложение:</span>
      <b><?= $myOfferString?></b>
    </div>
  </div>
</div>

