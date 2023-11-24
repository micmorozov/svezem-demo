<?php

use common\models\City;
use common\models\Profile;
use frontend\widgets\Select2;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;
/* @var $model Profile */
?>

<?php
$this->registerJs("
$('#profile-{$model->id}-upload-img').on('change',function () {
    $('#photoLink').html(this.files[0].name);
    var reader = new FileReader();
    reader.onload = function (e) {
        $('#profile-{$model->id}-upload-preview').attr('src',e.target.result)
        $('#profile-{$model->id}-upload-delete').val(0);
    }
    reader.readAsDataURL(this.files[0]);
});

$('#profile-{$model->id}-upload-del').click(function () {
    $('#profile-{$model->id}-upload-delete').val(1);
    $('#profile-{$model->id}-upload-preview').attr('src','/img/icons/default_transport_icon.svg')
})
"); ?>

<?php $form = ActiveForm::begin([
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => ''
    ],
    'fieldConfig' => [
        'template' => '{input}{error}'
    ],
    'action' => ['/cabinet/profile/update', 'id' => $model->id],
    'enableAjaxValidation' => false,
    'validateOnType' => true,
    'validationDelay' => 1
]); ?>
    <div class="item-profile col-md-12 panel" style="background: white; padding: 16px;">
        <span class="h4">Профиль <?= $model->type >= Profile::TYPE_TRANSPORTER_PRIVATE ? "перевозчика" : "отправителя" ?></span>
        <a href="#" class="pull-right" onclick="$(this).parent().toggleClass('edit')">
            <span class="hidden-xs">изменить&nbsp;</span><i class="fa fa-edit"></i>
        </a>
        <div class="" style="padding-top: 16px;">
            <div class="col-md-3 text-center">
                <div style="position: relative;height: 160px;width: 160px;margin: 0 auto 16px;">
                    <?php if ($model->photo) : ?>
                        <span class="show-on-edit"><i id="<?='profile-' . $model->id . '-upload-del';?>" style="position: absolute;right: 0;top: 0;cursor: pointer;" class="fa fa-times text-danger" aria-hidden="true"></i></span>
                    <?php endif; ?>
                    <?= Html::img($model->image, [
                        'id' => 'profile-' . $model->id . '-upload-preview',
                        'style' => 'border-radius:50%;height:100%;max-width:100%;border:1px solid #2a82e6;'
                    ]) ?>
                    <?= $form->field($model, 'imageFile')->fileInput([
                        'id' => 'profile-' . $model->id . '-upload-img',
                        'class' => 'hidden',
                        'accept' => 'image/jpg, image/jpeg, image/png, image/bmp',
                    ]) ?>
                    <?= $form->field($model, 'deleteImage')->hiddenInput([
                        'id' => 'profile-' . $model->id . '-upload-delete'
                    ])->label(false) ?>
                </div>
                <a id="photoLink" class="show-on-edit" href="#" style="padding: 16px 4px;" onclick="$('#profile-<?= $model->id ?>-upload-img').click();">Загрузить фото</a>
            </div>
            <div class="col-md-9">
                <?php if ($model->type >= Profile::TYPE_TRANSPORTER_PRIVATE): ?>
                    <div class="row form-group">
                        <label class="col-sm-3 control-label">Статус</label>
                        <div class="col-sm-9 hide-on-edit">
                            <?= $model->getPersonLabel() ?>
                        </div>
                        <div class="col-sm-9 show-on-edit"><?= $form->field($model, 'type')->dropDownList(Profile::getPersonLabels()) ?></div>
                        <div class="clearfix"></div>
                    </div>
                <?php endif; ?>
                <div class="row form-group">
                    <label class="col-sm-3 control-label">Ф.И.О</label>
                    <div class="col-sm-9 hide-on-edit">
                        <?= !empty($model->name) ? $model->name : 'Не указано' ?>
                    </div>
                    <div class="col-sm-9 show-on-edit">
                        <?= $form->field($model, 'name')->textInput() ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-3 control-label">Город</label>

                    <div class="col-sm-9 hide-on-edit">
                        <?= !empty($model->city_id) ? City::findOne(['id' => $model->city_id])->getFQTitle() : 'Не указан'; ?>
                    </div>
                    <div class="col-sm-9 show-on-edit">
                        <?= $form
                            ->field($model, 'city_id', [
                                'options' => ['class' => ''],
                                'labelOptions' => ['class' => '']
                            ])
                            ->widget(Select2::class, [
                                'options' => [
                                    'id' => 'profile-' . $model->id,
                                    'style' => 'width: 100%',
                                    'class' => 'ajax-select form-control'
                                ],
                                'data' => !empty($model->city_id) ? [$model->city_id => City::findOne(['id' => $model->city_id])->getFQTitle()] : [],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                    'minimumInputLength' => 3,
                                    'theme' => 'bootstrap',
                                    'ajax' => [
                                        'url' => Url::to(['/city/list']),
                                        'dataType' => 'json',
                                        'data' => new JsExpression('function(params) { return {query:params.term}; }'),
                                        'processResults' => new JsExpression('function(data) { return {results:data};}'),
                                        'delay' => 250,
                                        'cache' => true
                                    ],
                                    'placeholder' => 'Выберите город'
                                ]
                            ]);
                        ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-3 control-label">Контактное лицо</label>
                    <div class="col-sm-9 hide-on-edit">
                        <?= !empty($model->contact_person) ? $model->contact_person : 'Не указано' ?>
                    </div>
                    <div class="col-sm-9 show-on-edit">
                        <?= $form->field($model, 'contact_person')->textInput() ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-3 control-label">Контактный телефон</label>
                    <div class="col-sm-9 hide-on-edit">
                        <?= (!empty($model->contact_phone) && strlen($model->contact_phone) > 6) ? '+' . $model->contact_phone : 'Не указан'; ?>
                    </div>
                    <div class="col-sm-9 show-on-edit">
                        <?= $form->field($model, 'contact_phone')->textInput(['type' => 'tel']) ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-3 control-label"">Контактный E-mail</label>
                    <div class="col-sm-9 hide-on-edit">
                        <?= !empty($model->contact_email) ? $model->contact_email : 'Не указан'; ?>
                    </div>
                    <div class="col-sm-9 show-on-edit">
                        <?= $form->field($model, 'contact_email')->textInput() ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row form-group show-on-edit">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="submit" class="btn btn-primary btn-svezem">Сохранить</button>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
<?php ActiveForm::end(); ?>
