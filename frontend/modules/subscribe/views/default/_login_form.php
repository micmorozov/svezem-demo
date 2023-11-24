<?php

use common\models\LoginSignup;
use ferrumfist\yii2\recaptcha\ReCaptcha;
use yii\helpers\Html;
use frontend\widgets\Select2;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

/** @var $model LoginSignup */
/** @var $form ActiveForm */

?>
<?php if (Yii::$app->user->isGuest): ?>
    <div class="panel panel-primary border-none">
        <div class="panel-heading clear">
                <span style="display: inline-block;padding: 7px 0;">
                    <b>Авторизация</b>
                </span>
            <div class="pull-right">
                <?= Html::activeHiddenInput($model, 'type', ['value' => LoginSignup::TYPE_SIGNUP, 'id' => 'login-type']) ?>
                <?= Html::button("Я уже зарегистрирован", ['class' => "regi-btn btn btn-default"]) ?>
            </div>
        </div>
        <div class="panel-body">
            <div class="form-horizontal regi">
                <?= $form
                    ->field($model->signup, 'email', [
                        'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                        'options' => ['class' => 'form-group'],
                        'labelOptions' => ['class' => 'col-sm-2 control-label'],
                        'inputOptions' => [
                            'class' => 'form-control',
                            'placeholder' => "E-mail"
                        ]
                    ])
                ?>
                <?= $form
                    ->field($model->signup, 'contact_person', [
                        'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                        'options' => ['class' => 'form-group'],
                        'labelOptions' => ['class' => 'col-sm-2 control-label'],
                        'inputOptions' => [
                            'class' => "form-control",
                            'placeholder' => "Контактное лицо"
                        ]
                    ])
                ?>
                <?= $form
                    ->field($model->signup, 'city_id', [
                        'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                        'options' => ['class' => 'form-group'],
                        'labelOptions' => ['class' => 'col-sm-2 control-label'],
                        'inputOptions' => [
                            'class' => "form-control",
                            'placeholder' => "Контактное лицо"
                        ]
                    ])
                    ->widget(Select2::class, [
                        'options' => [
                            'class' => 'ajax-select',
                            'style' => 'width:100%'
                        ],
                        'data' => !empty($model->signup->city_id) ? [$model->signup['city_id'] => City::findOne(['id' => $model->signup['city_id']])->getFQTitle()] : [],
                        'pluginOptions' => [
                            'theme' => 'bootstrap',
                            'allowClear' => true,
                            'minimumInputLength' => 3,
                            'ajax' => [
                                'url' => \yii\helpers\Url::to(['/city/list/']),
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
            <div class="form-horizontal login" style="display: none">
                <?= $form->field($model->login, 'email', [
                    'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => 'col-sm-2 control-label'],
                    'inputOptions' => [
                        'class' => 'form-control',
                        'placeholder' => "E-mail",
                        'needValid' => 'false'
                    ]
                ])
                ?>
                <?= $form->field($model->login, 'password', [
                    'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => 'col-sm-2 control-label'],
                    'inputOptions' => [
                        'class' => "form-control",
                        'placeholder' => "Пароль",
                        'needValid' => 'false'
                    ]
                ])->passwordInput()
                ?>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <div class="checkbox">
                            <?= Html::a('Забыли пароль?', '/account/request-password-reset/', [
                                'class' => "form-forget-pass__link",
                                'target' => '_blank',
                                'rel' => 'nofollow'
                            ]); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="" style="display: none">
                <div class="col-md-6">
                    <?= $form->field($model->signup, 'contact_person', [
                        'options' => ['class' => 'form-group'],
                        'inputOptions' => [
                            'class' => "form-control",
                            'placeholder' => "Контактное лицо"
                        ]
                    ])
                    ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model->signup, 'city_id', [])
                        ->widget(Select2::class, [
                            'options' => [
                                'class' => 'ajax-select',
                                'style' => 'width:100%'
                            ],
                            'data' => !empty($model->signup->city_id) ? [$model->signup['city_id'] => City::findOne(['id' => $model->signup['city_id']])->getFQTitle()] : [],
                            'pluginOptions' => [
                                'theme' => 'bootstrap',
                                'allowClear' => true,
                                'minimumInputLength' => 3,
                                'ajax' => [
                                    'url' => \yii\helpers\Url::to(['/city/list/']),
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
            </div>
            <div class="" style="display: none">
                <div class="col-md-12">
                    <div class="form-horizontal">
                        <?= $form->field($model->login, 'email', [
                            'options' => ['class' => 'form-group'],
                            'labelOptions' => ['class' => "col-sm-2 control-label"],
                            'inputOptions' => [
                                'class' => "form-control",
                                'placeholder' => "E-mail",
                                'needValid' => 'false'
                            ]
                        ])
                        ?>
                        <?= $form->field($model->login, 'password', [
                            'options' => ['class' => 'form-group'],
                            'labelOptions' => ['class' => "col-sm-2 control-label"],
                            'inputOptions' => [
                                'class' => "form-control",
                                'placeholder' => "Пароль",
                                'needValid' => 'false'
                            ]
                        ])->passwordInput()
                        ?>
                    </div>
                </div>

                <?= Html::a('Забыли пароль?', '/account/request-password-reset/', [
                    'class' => "form-forget-pass__link",
                    'target' => '_blank',
                    'rel' => 'nofollow'
                ]); ?>

                <?= $form->field($model->login, 'reCaptcha', [
                    'options' => [
                        'style' => 'display: ' . ($model->login->needRecaptcha() ? 'block' : 'none')
                    ]
                ])
                    ->label('')
                    ->widget(ReCaptcha::class, [
                        'options' => ['needValid' => 'false']
                    ]);
                ?>
            </div>
        </div>
    </div>
<?php endif ?>

<?php $this->registerJs("
function toggleRegister(){
    var btn = $('.regi-btn');
    if (btn.hasClass('registation')){    
        $('.regi').show();
        $('.login').hide();
        $('.regi-btn').removeClass('registation');
        $('.regi-btn').html('Я уже зарегистрирован на svezem.ru');
        $('#login-type').val(" . LoginSignup::TYPE_SIGNUP . ");
        
        $('#loginform-email').attr('needValid', 'false');
        $('#loginform-password').attr('needValid', 'false');
        $('#loginform-recaptcha').attr('needValid', 'false');
        
        $('#signupform-email').attr('needValid', 'true');
        $('#signupform-contact_person').attr('needValid', 'true');
        $('#signupform-city_id').attr('needValid', 'true');
        
    }
    else{
        $('.regi').hide();
        $('.login').show();
        $('.regi-btn').addClass('registation');
        $('.regi-btn').html('Зарегистрироваться');
        $('#login-type').val(" . LoginSignup::TYPE_LOGIN . ");
        
        $('#loginform-email').attr('needValid', 'true');
        $('#loginform-password').attr('needValid', 'true');
        $('#loginform-recaptcha').attr('needValid', 'true');
        
        $('#signupform-email').attr('needValid', 'false');
        $('#signupform-contact_person').attr('needValid', 'false');
        $('#signupform-city_id').attr('needValid', 'false');
    }
}
$('.regi-btn').click(toggleRegister);

$('.form2').on('afterValidateAttribute', function(event, attribute, messages){    
    if ($('.form2').find('.has-error').length)
        $('#btn-error').show();
    else
        $('#btn-error').hide();
});
");
?>
<?php
// Если была выбрана форма авторизации на ней и должны остаться
if ($model->type == LoginSignup::TYPE_LOGIN) {
    $this->registerJs("toggleRegister();");
}
?>