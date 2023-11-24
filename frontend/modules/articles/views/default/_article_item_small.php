<?php
/** $model Articles */

?>
<div class="col-md-6 news__item">
    <div class="news__img" style="background-image: url('<?= $model->imagePath('link_preview') ?>');">
    </div>
    <div class="news__info">
        <div class="news__title">
            <?= yii\helpers\Html::a($model->name, ['/articles/default/view', 'slug'=>$model->slug], ['class'=>"news__link"]) ?>
        </div>
        <div class="news__excerpt">
            <p><?= $model->preview ?></p>
        </div>
    </div>
</div>
