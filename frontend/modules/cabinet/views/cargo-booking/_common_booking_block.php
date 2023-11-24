<?php
use common\models\Cargo;

/** @var $model Cargo*/
?>
<span id="comment_block_<?= $model->id  ?>">
<?php

// Комментарий менеджера
$comment = isset($model->bookingComment[0]) ? $model->bookingComment[0]->comment : '';
echo $this->render('_comment', [
    'cargo_id' => $model->id,
    'comment' => $comment
]);
?>
</span>

<span id="booking_block_<?= $model->id  ?>">
<?php
if($showBookingBtn = $model->status == Cargo::STATUS_ACTIVE){
    echo $this->render('_booking_btn', [
        'model' => $model
    ]);
}

$showPriceBlock = $model->status == Cargo::STATUS_WORKING && $model->booking_by == Yii::$app->user->id;
$showBookingDone = $model->status == Cargo::STATUS_DONE && $model->booking_by == Yii::$app->user->id;

if( $showPriceBlock ){
    echo $this->render('_price_block', [
        'model' => $model
    ]);
}
if( $showBookingDone ){
    echo $this->render('_booking_done', [
        'model' => $model
    ]);
}
?>
</span>
