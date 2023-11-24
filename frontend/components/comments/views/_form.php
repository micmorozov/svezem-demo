<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii2mod\comments\models\CommentModel;

/* @var $this View */
/* @var $commentModel CommentModel */
/* @var $encryptedEntity string */
/* @var $formId string comment form id */
?>
<div class="comment-form-container">
<?php IF( !Yii::$app->user->isGuest ): ?>
    <?php $form = ActiveForm::begin([
        'id' => $formId,
        'options' => [
            'class' => 'comment-box'
        ],
        'action' => Url::to(['/comment/default/create', 'entity' => $encryptedEntity]),
        'validateOnChange' => false,
        'validateOnBlur' => false
    ]); ?>
    <div class="comments__new  new clear" style="border-bottom: 0px; margin-bottom: 00px;">
        <div class="comments__author"><?= Yii::$app->user->identity->username ?></div>
        <div class="comments__container clear">
            <div class="comments__img">
                <img src="img/author.png">
            </div>
            <div class="comments__input-area">
                <?= $form->field($commentModel, 'content', [
                        'template' => '{input}{error}'
                    ])
                    ->textarea([
                        'class' => "comments__textarea",
                        'placeholder' => Yii::t('yii2mod.comments', 'Add a comment...'),
                        'rows' => 4,
                        'data' => ['comment' => 'content']
                    ]) ?>
            </div>
            <?= $form->field($commentModel, 'parentId', [
                    'template' => '{input}'
                ])
                ->hiddenInput([
                    'data' => ['comment' => 'parent-id']
                ]);
            ?>
        </div>
        <div class="comments__input-det clear">
            <div class="comments__btn-wrap">
                <?= Html::submitButton('Отправить', ['class' => 'comments__btn content__btn']); ?>
                <?= Html::tag('span', 'Отмена', [
                        'id' => 'cancel-reply',
                        'class' => 'comments__cancel comments__reply',
                        'data' => ['action' => 'cancel-reply'],
                        'style'=>'color: #ef0e14;'
                    ]);
                ?>
            </div>
        </div>
    </div>
    <?php $form->end(); ?>
<?php ELSE: ?>
    <div class="comments__input-det clear">
        <div class="comments__btn-wrap">
            <?= Html::a('Чтобы оставить комментарий, нужно авторизоваться', Yii::$app->urlManager->createAbsoluteUrl(Yii::$app->user->loginUrl) , ['class'=>"comments__reply no-login"]) ?>
        </div>
    </div>
<?php ENDIF ?>
</div>