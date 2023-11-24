<?php

use common\models\City;
use common\models\LoginSignup;
use ferrumfist\yii2\recaptcha\ReCaptcha;
use yii\helpers\Html;
use frontend\widgets\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

/** @var $model LoginSignup */
/** @var $form ActiveForm */

?>
<?php if (Yii::$app->user->isGuest): ?>
    <div class="panel panel-primary">
        <div class="panel-heading clear">
            <span style="display: inline-block;padding: 7px 0;">
                    <b>Авторизация</b>
                </span>
            <div class="pull-right">
                <?= Html::activeHiddenInput($model, 'type', ['value' => LoginSignup::TYPE_SIGNUP, 'id' => 'login-type']) ?>
                <?= Html::button("Я уже зарегистрирован", [
                    'class' => 'btn btn-default regi-btn',
                    'onclick' => 'toggleRegister()'
                ]) ?>
            </div>
        </div>
        <div class="panel-body">
            <div class="form-horizontal form-login-signup">
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
                                'url' => Url::to(['/city/list/']),
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
            <div class="form-horizontal form-login-signin" style="display: none">
                <?= $form->field($model->login, 'email', [
                    'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => 'col-sm-2 control-label'],
                    'inputOptions' => [
                        'class' => 'form-control',
                        'placeholder' => "E-mail",
                        'needValid' => 'false'
                    ]
                ]) ?>
                <?= $form->field($model->login, 'password', [
                    'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => 'col-sm-2 control-label'],
                    'inputOptions' => [
                        'class' => "form-control",
                        'placeholder' => "Пароль",
                        'needValid' => 'false'
                    ]
                ])->passwordInput() ?>
                <?= $form->field($model->login, 'reCaptcha', [
                        'template' => '{label}<div class="col-sm-10">{input}{error}</div>',
                        'labelOptions' => ['class' => 'col-sm-2 control-label'],
                        'options' => [
                            'style' => 'display: '.($model->login->needRecaptcha()?'block':'none')
                        ],
                        'inputOptions' => [
                            'needValid' => 'false'
                        ]
                    ])
                    ->widget(ReCaptcha::class)
                    ->label('Проверочный код');
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
        </div>
    </div>
    <script>
        function toggleRegister() {
            var btn = $('.regi-btn');

            if (btn.hasClass('registation')) {
                $('.form-login-signup').show();
                $('.form-login-signin').hide();

                $('.regi-btn').removeClass('registation');
                $('.regi-btn').html('Я уже зарегистрирован');

                $('#login-type').val("<?= LoginSignup::TYPE_SIGNUP ?>");

                $('#loginform-email').attr('needValid', 'false');
                $('#loginform-password').attr('needValid', 'false');
                $('#loginform-recaptcha').attr('needValid', 'false');

                $('#signupform-email').attr('needValid', 'true');
                $('#signupform-contact_person').attr('needValid', 'true');
                $('#signupform-city_id').attr('needValid', 'true');
            } else {
                $('.form-login-signup').hide();
                $('.form-login-signin').show();
                $('.regi-btn').addClass('registation');
                $('.regi-btn').html('Зарегистрироваться');
                $('#login-type').val("<?= LoginSignup::TYPE_LOGIN ?>");

                $('#loginform-email').attr('needValid', 'true');
                $('#loginform-password').attr('needValid', 'true');
                if( $('#loginform-recaptcha').is(':visible') ) {
                    $('#loginform-recaptcha').attr('needValid', 'true');
                }

                $('#signupform-email').attr('needValid', 'false');
                $('#signupform-contact_person').attr('needValid', 'false');
                $('#signupform-city_id').attr('needValid', 'false');
            }
        }
    </script>
<?php endif ?>
