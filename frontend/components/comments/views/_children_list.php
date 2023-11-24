<?php
use yii\helpers\Html;
use yii\web\View;
use yii2mod\comments\models\CommentModel;

/* @var $this View */
/* @var $model CommentModel */
/* @var $maxLevel null|integer comments max level */
?>
<li class="comments__item clear">
    <div class="comments__img">
        <img src="img/author-1.png">
    </div>
    <div class="comments__body" data-comment-content-id="<?php echo $model->id ?>">
        <div class="comments__response response">
            <div class="response__from response__item">
                <span class="response__author"><?= $model->getAuthorName() ?></span>
            </div>
            <div class="response__arrow response__item">
                <span class="desktop-v">
                    <?= Html::img("/img/icons/direction-arrow-1.svg", ['alt'=>"arrow"]) ?>
                </span>
                <span class="mobile-v">
                    <?= Html::img("/img/icons/direction-arrow-2.svg", ['alt'=>"arrow"]) ?>
                </span>
            </div>
            <div class="response__to response__item">
                <span class="response__author">
                    <?php
                        $parentModel = CommentModel::findOne($model->parentId);
                        echo $parentModel->getAuthorName();
                    ?>
                </span>
            </div>
        </div>
        <div class="comments__details clear">

        </div>
        <div class="comments__text">
            <p><?= $model->getContent() ?></p>
            <?php if( !Yii::$app->user->isGuest )
                echo Html::a('Ответить', '#', ['class' => 'comments__reply', 'data' => ['action' => 'reply', 'comment-id' => $model->id]]);
            ?>
        </div>
    </div>
</li>
<li class="comments__item clear add" style="display: none;">
    <div class="comments__add-body clear">

    </div>
</li>
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