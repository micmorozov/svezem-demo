<?php

use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\Html;
use yii2mod\comments\models\CommentModel;

/* @var $this View */
/* @var $commentModel CommentModel */
/* @var $maxLevel null|integer comments max level */
/* @var $encryptedEntity string */
/* @var $pjaxContainerId string */
/* @var $formId string comment form id */
/* @var $commentDataProvider ArrayDataProvider */
/* @var $listViewConfig array */
/* @var $commentWrapperId string */
?>


<div class="post__comments comments" id="<?php echo $commentWrapperId; ?>">
    <?php Pjax::begin(['enablePushState' => false, 'timeout' => 20000, 'id' => $pjaxContainerId]); ?>

    <h2 class="comments__title">Оставить комментарий</h2>
    <?php echo $this->render('_form', [
            'formId' => $formId,
            'commentModel' => $commentModel,
            'encryptedEntity' => $encryptedEntity
        ]); ?>
    <div class="comments row">
        <div class="col-md-12 col-sm-12">
            <div class="title-block clearfix">
                <h3 class="h3-body-title">
                    <?php echo Yii::t('yii2mod.comments', 'Comments ({0})', $commentModel->getCommentsCount()); ?>
                </h3>
                <div class="title-separator"></div>
            </div>
            <?php echo ListView::widget(ArrayHelper::merge(
                [
                    'dataProvider' => $commentDataProvider,
                    'layout' => "{items}\n{pager}",
                    'itemView' => '_list',
                    'viewParams' => [
                        'maxLevel' => $maxLevel,
                        'encryptedEntity' => $encryptedEntity
                    ],
                    'options' => [
                        'tag' => 'div',
                        'class' => 'comments__wrap',
                    ],
                    'itemOptions' => [
                        'tag' => false,
                    ],
                ],
                $listViewConfig
            )); ?>
        </div>
    </div>
    <?php Pjax::end(); ?>
</div>
