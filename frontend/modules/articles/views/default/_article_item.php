<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $model \common\models\Articles */
?>
<div class="col-md-6 article__item">
    <div class="article__img" style="background-image: url('<?= $model->imagePath('preview') ?>');"></div>
    <div class="article__title-wrap">
        <?php
            $url = Url::toRoute(['/articles/default/view', 'slug'=>$model->slug]);
            echo Html::a($model->name, $url, ['class'=>"article__title", 'data-pjax'=>'0']);
        ?>
    </div>
    <div class="article__desc">
        <?= $model->preview  ?>
    </div>
</div>