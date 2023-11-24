<?php

use frontend\modules\cabinet\models\UserEditForm;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;
use common\helpers\PhoneHelpers;

/* @var $model UserEditForm */
?>
    <table class="table">
        <tbody>
        <tr>
            <td>
                ID
            </td>
            <td>
                <?= Yii::$app->user->id ?>
            </td>
        </tr>
        <tr>
            <td>Логин:</td>
            <td>
                <?php if (!empty(Yii::$app->user->identity->email)) : ?>
                    <?= Yii::$app->user->identity->email; ?>
                <?php endif; ?>
                <?= (!empty(Yii::$app->user->identity->email) && !empty((Yii::$app->user->identity->phone))) ? '|' : ''; ?>
                <?php if (!empty((Yii::$app->user->identity->phone))) : ?>
                    <?= PhoneHelpers::formatter(Yii::$app->user->identity->phone,
                        Yii::$app->user->identity->phone_country); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Пароль:</td>
            <td>
                ********
                <?= Html::a('Изменить', '#', ['id' => 'settings-edit-button', 'class' => 'change-field']); ?>
            </td>
        </tr>
        </tbody>
    </table>

    <!--    <div id="user-data-text">-->
    <!--        <div class="form-text">-->
    <!--            <label>Пароль:</label>-->
    <!--            <span>********</span>-->
    <!---->
    <!--        </div>-->
    <!--    </div>-->

    <div id="user-data-edit-form" class="panel" style="padding: 16px; display: none;">
        <?php $form = ActiveForm::begin([
            'action' => ['/cabinet/settings/save'],
            'enableAjaxValidation' => true
        ]); ?>
        <?= $form->field($model, 'password', [
            'labelOptions' => ['class' => 'col-sm-3 control-label'],
            'template' => '<div class="form-group">{label}<div class="col-sm-9">{input}{error}</div><div class="clearfix"></div></div>'
        ])->passwordInput() ?>
        <?= $form->field($model, 'password_new', [
            'labelOptions' => ['class' => 'col-sm-3 control-label'],
            'template' => '<div class="form-group">{label}<div class="col-sm-9">{input}{error}</div><div class="clearfix"></div></div>'
        ])->passwordInput() ?>
        <?= $form->field($model, 'password_new_repeat', [
            'labelOptions' => ['class' => 'col-sm-3 control-label'],
            'template' => '<div class="form-group">{label}<div class="col-sm-9">{input}{error}</div><div class="clearfix"></div></div>'
        ])->passwordInput() ?>
        <div class="col-sm-3">
        </div>
        <div class="col-md-9">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary btn-svezem']) ?>
            &nbsp;
            <?= Html::a('Отменить', 'javascript:', ['id' => 'cancel-edit-button']) ?>
        </div>
        <?php ActiveForm::end(); ?>
        <div class="clearfix"></div>
    </div>

<?php
$this->registerJs("
    $(document).on('click', '#settings-edit-button', function(event){
        $('#user-data-text').slideUp();
        $('#user-data-edit-form').slideDown();
        return false;
    });

    $(document).on('click', '#cancel-edit-button', function(event){
        $('#user-data-text').slideDown();
        $('#user-data-edit-form').slideUp();
        return false;
    });
");
