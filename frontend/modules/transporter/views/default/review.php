<?php
/** @var $review TransporterReviews */

use common\models\TransporterReviews; ?>
<div class="reviews-slider-item-wrap">
    <div class="reviews-slider-item">
        <div class="reviews-slider-item__header">
            <div class="reviews-slider-item__img" style="background-image: url('img/ava.jpg')"></div>
            <div class="reviews-slider-item__det">
                <div class="reviews-slider-item__name"><?= $review->sender->username ?></div>
                <div class="reviews-slider-item__date"><?= Yii::$app->formatter->asDate($review->created_at, 'dd.MM.yyy')  ?></div>
            </div>
        </div>
        <div class="reviews-slider-item__body">
            <?= $review->message ?>
        </div>
    </div>
</div>