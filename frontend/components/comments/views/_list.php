<?php

use yii\helpers\Html;
use yii\web\View;
use yii2mod\comments\models\CommentModel;

/* @var $this View */
/* @var $model CommentModel */
/* @var $maxLevel null|integer comments max level */
?>
<div class="comments__item-wrap">
    <div class="comments__item up clear" data-comment-content-id="<?php echo $model->id ?>">
        <div class="comments__img">
            <?php echo Html::img($model->getAvatar(), ['alt' => $model->getAuthorName()]); ?>
        </div>
        <div class="comments__body">
            <div class="comments__details clear">
                <div class="comments__author"><?= $model->getAuthorName(); ?></div>
            </div>
            <div class="comments__text">
                <p><?= $model->getContent() ?></p>
                <?php if( !Yii::$app->user->isGuest )
                    echo Html::a('Ответить', '#', ['class' => 'comments__reply', 'data' => ['action' => 'reply', 'comment-id' => $model->id]]);
                ?>
            </div>
        </div>
    </div>
    <?php IF($model->hasChildren()) : ?>
    <ul class="comments__children">
        <?php FOREACH($model->getChildren() as $children) : ?>
            <li class="comment" id="comment-<?php echo $children->id; ?>">
                <?= $this->render('_children_list', [
                    'model' => $children,
                    'maxLevel' => $maxLevel
                ]) ?>
            </li>
        <?php ENDFOREACH; ?>
    </ul>
    <?php ENDIF ?>
</div>