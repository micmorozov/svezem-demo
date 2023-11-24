<?php

use common\helpers\TelegramHelper;
use common\helpers\UserHelper;
use common\models\CargoCategory;
use common\models\LoginSignup;
use common\models\PageTemplates;
use frontend\modules\account\widgets\FormLogin;
use frontend\modules\subscribe\assets\SubscribeFormVueAssets;
use frontend\modules\subscribe\models\Subscribe;
use frontend\modules\subscribe\models\SubscribeRules;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/** @var $subscribe Subscribe */
/** @var $sub_rule SubscribeRules */
/** @var $loginSignup LoginSignup */
/** @var $this View */
/** @var $priceForMsg float */
/* @var $pageTpl PageTemplates */

SubscribeFormVueAssets::register($this);
$this->registerJs("init_vue();", View::POS_END);

// Устанавливаем значение по умолчанию
$title = 'Управления уведомлениями о новых грузах';
$descr = 'Создайте набор правил для получения уведомлений о новых грузах, укажите способ получения и будьте всегда в курсе появления новых заявок на грузоперевозку по нужному направлению и виду.';
$keywords = 'уведомления о грузах, подписка на новые грузы';
$h1 = 'Подписка на уведомления о новых грузах';
$text = 'Создайте набор правил для получения уведомлений о новых грузах, укажите способ получения и будьте всегда в курсе появления новых заявок на грузоперевозку по нужному направлению и виду.';

// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = $pageTpl->title;
    $descr = $pageTpl->desc;
    $keywords = $pageTpl->keywords;
    $h1 = $pageTpl->h1;
    $text = nl2br($pageTpl->text);
}

$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);

$cacheKey = 'SubscribeCategoryCache';
if (!$categories = Yii::$app->cache->get($cacheKey)) {
    $categories = CargoCategory::find()
        ->root()
        ->showModerCargo()
        ->all();

    foreach ($categories as $category) {
        $subCats = array_filter($category->nodes, function ($cat) {
            /** @var $cat CargoCategory */
            return $cat->show_moder_cargo;
        });

        $categories = array_merge($categories, $subCats);
    }

    $categories = ArrayHelper::map($categories, 'id', 'category');

    Yii::$app->cache->set($cacheKey, $categories, 86400);
}

?>
<script>
    <?php
    $formFrom = [
        'errorMsg' => false,
        'id' => $sub_rule->locationFrom,
        'type' => $sub_rule->locationFromType,
        'options' => [
            [
                'id' => $sub_rule->locationFrom,
                'type' => $sub_rule->locationFromType,
                'text' => $sub_rule->locationFrom
                    ? ( $sub_rule->locationFromType == SubscribeRules::LOCATION_TYPE_CITY
                            ? $sub_rule->cityFrom->getFQTitle()
                            : ($sub_rule->region_from ? $sub_rule->regionFrom->getFQTitle() : '')
                    )
                    : ''
            ]
        ]
    ];

    $formTo = [
        'errorMsg' => false,
        'id' => $sub_rule->locationTo,
        'type' => $sub_rule->locationToType,
        'options' => [
            [
                'id' => $sub_rule->locationTo,
                'type' => $sub_rule->locationToType,
                'text' => $sub_rule->locationTo
                    ? ( $sub_rule->locationToType == SubscribeRules::LOCATION_TYPE_CITY
                        ? $sub_rule->cityTo->getFQTitle()
                        : ($sub_rule->region_to ? $sub_rule->regionTo->getFQTitle() : '')
                    )
                    : ''
            ]
        ]
    ];
    ?>
var formFrom = <?= json_encode($formFrom, JSON_UNESCAPED_UNICODE) ?>;
var formTo = <?= json_encode($formTo, JSON_UNESCAPED_UNICODE) ?>;
var catIds = <?= json_encode($sub_rule->categoriesId) ?>;
</script>
<script id="rule-component" type="text/template">
    <div class="subscribe_wrap">
        <div class="app-block__direction-info clear">
            <div class="app-block__direction direction">
                <div class="direction__from direction__item">
                        <span class="direction__flag">
                            <img :src="cityFrom.flag" :title="cityFrom.countyTitle">
                        </span>
                    <span class="direction__city" :title="cityFrom.title">
                            {{cityFrom.title}}
                        </span>
                </div>
                <div class="direction__arrow direction__item">
                    <i class="fas fa-long-arrow-alt-right" style="font-size: 25px;color: #3e99dd;"></i>
                </div>
                <div class="direction__to direction__item">
                        <span class="direction__flag">
                            <img :src="cityTo.flag" :title="cityTo.countyTitle">
                        </span>
                    <span class="direction__city" :title="cityTo.title">
                            {{cityTo.title}}
                        </span>
                </div>
            </div>
            <div class="app-block__coment">
                    <span class="dscktop_cont">
                        {{categoriesText}}
                    </span>
                <span lass="mobile_cont"></span>
            </div>
            <div class="app-block__class">{{msgCount}} СМС/день</div>
            <div class="tools_block" style="padding: 4px;">
                <i class="editRule far fa-edit"
                   style="cursor: pointer; color: #3f7abe;font-size: 20px;padding-right: 4px;"
                   title="Редактировать правило" @click="show=!show"></i>
                <i class="copyRule far fa-copy"
                   style="cursor: pointer; color: #3f7abe;font-size: 20px;padding-right: 4px;"
                   title="Копировать правило" @click="$emit('copy', id)"></i>
                <i class="deleteRule far fa-trash-alt"
                   style="cursor: pointer; color: #3f7abe;font-size: 20px;padding-right: 4px;"
                   title="Удалить правило" @click="$emit('remove', id)"></i>
            </div>
        </div>
        <div class="subscribe_form" v-if="show">
            <div class="add-offer-block">
                <div class="col-md-6">
                    <city-region-select
                            :classBlock="'form-field'"
                            :options="optionsFrom"
                            :label="'Откуда'"
                            :errorMsg="cityFromErrorMsg"
                            :city.sync="cityFrom.id"
                            :type.sync="cityFrom.type">
                    </city-region-select>
                </div>
                <div class="col-md-6">
                    <city-region-select
                            :classBlock="'form-field'"
                            :options="optionsTo"
                            :label="'Куда'"
                            :errorMsg="cityToErrorMsg"
                            :city.sync="cityTo.id"
                            :type.sync="cityTo.type">
                    </city-region-select>
                </div>
                <div class="col-md-12">
                    <multi-select
                            :classBlock="'form-field'"
                            :label="'Вид'"
                            :errorMsg="catErrMsg"
                            :placeholder="'Выберете виды перевозки'"
                            :selected.sync="catIds">
                        <?php foreach ($categories as $val => $title): ?>
                            <option value="<?= $val ?>"><?= $title ?></option>
                        <?php endforeach ?>
                    </multi-select>
                </div>
                <div class="clearfix"></div>
                <div class="total_wrap">
                    <span class="cost">Новых грузов в день:</span>
                    <span id="msgCount">{{msgCount}}</span>
                </div>
                <div class="btn_wrap text-center">
                        <span class="form__field-wrapper">
                            <button type="submit" class="btn btn-primary btn-svezem"
                                    @click="save">Сохранить правило</button>
                            <span v-if="formErr" class="text-danger">
                                            {{formErr}}
                                        </span>
                        </span>
                </div>
            </div>
        </div>
    </div>
</script>
<main class="content cargo-list__wrap">
    <div class="container subscribe_page">
        <div class="page-title">
            <h1 class="h3 text-uppercase">
                <b><?= $h1 ?></b>
                <small><?= ' (' . Html::a('Справка', ['/info/subscribe'], ['target' => '_blank', 'rel'=>'nofollow']) . ')'; ?></small>
            </h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <div class="subscribe_block v-cloak--hidden" id="subscribe_block" v-cloak>
            <div class="loader" style="width: 50px; height: 50px; margin: auto" v-if="!$data">
                <svg class="circular" viewBox="25 25 50 50">
                    <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" />
                </svg>
            </div>
            <div class="loader v-cloak--hidden" style="width: 50px; height: 50px; margin: auto" v-if="!fetched">
                <svg class="circular" viewBox="25 25 50 50">
                    <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" />
                </svg>
            </div>
            <div v-if="list.length" class="panel panel-primary border-none v-cloak--hidden"
                 style="background: transparent; box-shadow: none">
                <div class="panel-heading">
                    <b>Правила отслеживания грузов</b>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <rule v-for="rule in list" :key="rule.id" v-bind="rule" @copy="copy" @remove="remove"></rule>
                    </div>
                </div>
            </div>
            <div class="subscribe_wrap add_form v-cloak--hidden">
                <form class="form_validate form1" @submit.prevent="createRule">
                    <div class="app-block__direction-info clear">
                        <span class="title">Новое Правило отслеживания грузов:</span>
                    </div>
                    <div class="subscribe_form">
                        <div class="add-offer-block">
                            <div class="row">
                                <city-region-select
                                        :class-block="'col-md-6 col-sm-6'"
                                        :style="'margin-bottom: 12px;'"
                                        :label="'Откуда'"
                                        :error-msg="form.from.errorMsg"
                                        :city.sync="form.from.id"
                                        :type.sync="form.from.type"
                                        :options="form.from.options"
                                        ref="cityFrom">
                                </city-region-select>
                                <city-region-select
                                        :class-block="'col-md-6 col-sm-6'"
                                        :style="'margin-bottom: 12px;'"
                                        :label="'Куда'"
                                        :error-msg="form.to.errorMsg"
                                        :city.sync="form.to.id"
                                        :type.sync="form.to.type"
                                        :options="form.to.options"
                                        ref="cityTo">
                                </city-region-select>
                                <multi-select
                                        :class-block="'col-md-12 col-sm-12'"
                                        :style="'margin-bottom: 12px;'"
                                        :label="'Вид перевозки'"
                                        :error-msg="form.catErrMsg"
                                        :placeholder="'Выберете виды перевозки'"
                                        :selected.sync="form.catIds"
                                        ref="cats">
                                    <?php foreach ($categories as $val => $title): ?>
                                        <option value="<?= $val ?>"><?= $title ?></option>
                                    <?php endforeach ?>
                                </multi-select>
                            </div>
                            <div class="clearfix"></div>
                            <div class="total_wrap col-md-12">
                                <span class="cost">Новых грузов в день:</span>
                                <span><span id="day_price">{{msgCount}}</span></span>
                            </div>
                            <div class="clearfix"></div>
                            <div class="btn_wrap text-center">
                                    <span class="form__field-wrapper">
                                        <button type="submit"
                                                class="form-custom-button add_rule_btn btn btn-primary btn-svezem">Сохранить правило</button>
                                        <span v-if="formErr" class="text-danger">
                                            {{formErr}}
                                        </span>
                                    </span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="total_wrap v-cloak--hidden" style="border-top: none;">
                <span class="cost">Суммарно по всем правилам:</span>
                <span>{{totalMsgCount}}</span>
            </div>
        </div>
        <div class="btn_wrap">
        </div>
        <div class="pay_wrap">
            <?php $formSubscribe = ActiveForm::begin([
                'action' => [Url::toRoute(['/sub/'])],
                'method' => 'post',
                'options' => [
                    'id' => 'subscribe_form',
                    'class' => "form_validate form1"
                ],
                'validateOnType' => true
            ]) ?>
            <div class="panel panel-primary border-none">
                <div class="panel-heading">
                    <b>Способ уведомления</b>
                </div>
                <div class="panel-body">
                    <div class="raw">
                        В каждом уведомлении Вы получите:
                        <span style="display: block;font-size: 1.5rem;">
                            <span style="display: block" class="clear"></span>
                            <span class="text-success">
                                <i class="far fa-grin"></i> Направление перевозки
                            </span><br/>
                            <span class="text-success">
                                <i class="far fa-grin"></i> Описание груза
                            </span><br/>
                            <span class="text-success">
                                <i class="far fa-grin"></i> Cсылку на страницу груза с контактами заказчика
                            </span><br/>
                        </span>
                    </div>

                    <div class="row">
                        <div style="position: relative" class="col-md-4">
                            <div class="radio-pill <?= ($subscribe->type == Subscribe::TYPE_PAID) ? 'active' : ''; ?>">
                                <label class="rcontainer">
                                    <input type="radio" value="paid" name="Subscribe[type]"
                                           <?= ($subscribe->type == Subscribe::TYPE_PAID) ? 'checked="checked"' : ''; ?>>
                                    <span class="checkmark"></span> СМС
                                    <small class="pull-right" style="color: gray">
                                        <?= $priceForMsg ?> руб/смс
                                    </small><br/>
                                </label>
                                <div style="position: relative">
                                    <?php if (!$subscribe->isNewRecord) {
                                        echo Html::a('Изменить', '', [
                                            'id' => 'editPhone',
                                            'style' => "text-decoration: underline;position:absolute;right: 16px; top:10px;z-index:10"
                                        ]);
                                    } ?>
                                    <?= $formSubscribe
                                        ->field($subscribe, 'phone', [])
                                        ->label(false)
                                        ->textInput([
                                            'type' => 'tel',
                                            'class' => 'form-control',
                                            'style' => 'max-width:none;',
                                            'disabled' => !$subscribe->isNewRecord ? true : false
                                        ])
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div style="position: relative" class="col-md-4">
                            <div class="radio-pill <?= ($subscribe->type == Subscribe::TYPE_FREE && $subscribe->email) ? 'active' : ''; ?>">
                                <label class="rcontainer">
                                    <input type="radio"
                                           value="free"
                                           <?= ($subscribe->type == Subscribe::TYPE_FREE) && $subscribe->email ? 'checked="checked"' : ''; ?>
                                           name="Subscribe[type]">
                                    <span class="checkmark"></span>
                                    Email
                                    <small class="pull-right" style="color: gray">бесплатно</small>
                                </label>
                                <div style="position: relative">
                                    <?php if (!$subscribe->isNewRecord) {
                                        echo Html::a('Изменить', '', [
                                            'id' => 'editEmail',
                                            'style' => "text-decoration: underline;position:absolute;right: 16px; top:10px;"
                                        ]);
                                    } ?>
                                    <?= $formSubscribe
                                        ->field($subscribe, 'email', [])
                                        ->label(false)
                                        ->textInput([
                                            'class' => 'form-control',
                                            'placeholder' => 'Введите email',
                                            'style' => 'max-width:none;',
                                            'disabled' => !$subscribe->isNewRecord ? true : false
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Telegram -->
                        <div style="position: relative" class="col-md-4">
                            <div class="radio-pill <?= ($subscribe->type == Subscribe::TYPE_FREE && $subscribe->telegram) ? 'active' : ''; ?>">
                                <label class="rcontainer">
                                    <input type="radio"
                                           value="free"
                                           <?= ($subscribe->type == Subscribe::TYPE_FREE && $subscribe->telegram) ? 'checked="checked"' : ''; ?>
                                           name="Subscribe[type]">
                                    <span class="checkmark"></span>
                                    Telegram
                                    <small class="pull-right" style="color: gray">бесплатно</small>
                                </label>
                                <div style="position: relative">
                                    <?php
                                    $code = UserHelper::createAnyCode(6, '0123456789', [
                                        'userid' => Yii::$app->user->id,
                                        'subscribeid' => $subscribe->id
                                    ], 600);

                                    if (!$subscribe->telegram):
                                        ?>
                                        Пройдите по <a
                                            href="<?= TelegramHelper::getLinkToBot() . '?start=' . $code ?>"
                                            target="_blank">ссылке</a> и нажмите кнопку START в чате, чтобы привязать ваш аккаунт к telegram боту @<?= TelegramHelper::BOT_NAME; ?>.
                                        Или пошлите боту следующую команду /link <?= $code ?> (ссылка действует 10 минут)
                                    <? else: ?>
                                        Ваш Телеграм аккаунт привязан к сервису Svezem.ru. Уведомления о грузах будут приходить в ваш Телеграм.
                                        <br><br>
                                        <a href="#" onclick="$(this).hide(); $('#unlink_tg').show();">Отвязать</a>
                                        <span style="display: none"
                                              id="unlink_tg">Для отвязки Telegram бота @<?= TelegramHelper::BOT_NAME; ?> зайдите в мессенджер и пошлите боту команду /unlink <?= $code ?> (ссылка действует 10 минут)</span>
                                    <? endif ?>
                                </div>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
            <div class="panel panel-primary calendar"
                 style="<?= $subscribe->type == Subscribe::TYPE_FREE ? 'display: none;' : '' ?>">
                <div class="panel-heading border-none">
                    <b>Баланс уведомлений</b>
                </div>
                <?php if (!Yii::$app->user->isGuest): ?>
                    <div class="add-offer-block">
                        <div class="notification-info" style="background: #778898;padding: 8px 15px;color: white;">
                            <img style="vertical-align: middle" src="/img/icons/notification.png">
                            <span class="cost">У вас на балансе <?= Yii::t('app',
                                    '{n, plural, =0{нет уведомлений} one{# уведомление} few{# уведомления} other{# уведомлений}}',
                                    ['n' => $subscribe->remain_msg_count]) ?>.</span>
                            <?php if ($subscribe->free): ?>
                                <span class="cost">Из них бесплатных <?= $subscribe->free; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                <?php endif ?>
                <div class="panel-body">
                    <?= $formSubscribe
                        ->field($subscribe, 'addMessage', [
                            'options' => [],
                            'inputOptions' => [
                                'class' => 'form-control',
                            ]
                        ])
                        ->label('Количество уведомлений для пополнения')
                        ->textInput(['value' => '10']); ?>
                    <label>Пополнить на
                        <span class="fastLinks" style="margin-bottom: 10px;">
                                <?= Html::a('3&nbsp;дня', '#', ['class' => 'fastCalendar', 'data-period' => 3]) ?>
                                <?= Html::a('7&nbsp;дней', '#', ['class' => 'fastCalendar', 'data-period' => 7]) ?>
                                <?= Html::a('1&nbsp;месяц', '#', ['class' => 'fastCalendar', 'data-period' => 30]) ?>
                                <?= Html::a('3&nbsp;месяца', '#', ['class' => 'fastCalendar', 'data-period' => 90]) ?>
                            </span></label>
                    <span class='visible-sm visible-xs text-center' style="color:green; font-weight:bold;">
                        уведомлений&nbsp;*&nbsp;<?= $priceForMsg ?>&nbsp;руб.&nbsp;=&nbsp;<span
                                class="totalPrice">?</span>
                        </span>
                </div>
            </div>
            <?= FormLogin::widget(['model' => $loginSignup, 'form' => $formSubscribe]) ?>
            <div class="btn_wrap">
                <div id="payButtons" class="text-right" style="display: <?= $subscribe->type == Subscribe::TYPE_PAID?'block':'none' ?>">
                    <?= Html::submitButton('Оплатить банковской картой', [
                        'class' => 'submitBtn btn btn-primary btn-svezem payBtn',
                        'data-type' => 'card',
                        'style' => 'margin-bottom:12px;'
                    ]) ?>

                    <?= Html::submitButton('Оплатить как юр лицо или ИП', [
                        'class' => 'submitBtn btn btn-primary btn-svezem payBtn',
                        'data-type' => 'juridical',
                        'style' => 'margin-bottom:12px;'
                    ]) ?>
                    <div class="text-right" style="padding-top: 12px;">
                        <small>
                            Оплачивая услуги, вы принимаете <?= Html::a('договор-оферту',
                                '//' . Yii::getAlias('@domain') . '/info/legal/public-offer/', ['target'=>'_blank', 'rel' => 'nofollow']) ?>
                        </small>
                    </div>
                </div>
                <div id="saveButtons" class="text-center" style="display: <?= $subscribe->type == Subscribe::TYPE_FREE?'block':'none' ?>">
                    <?= Html::submitButton('Сохранить', [
                        'class' => 'submitBtn btn btn-primary btn-svezem'
                    ]) ?>
                    <div style="padding-top: 12px;">
                        <small>
                            Создавая подписку, Вы соглашаетесь с <?= Html::a('политикой конфиденциальности', '//'.Yii::getAlias('@domain').'/info/legal/privacy-policy/', [
                                'target' => '_blank',
                                'rel' => 'nofollow'
                            ]) ?>
                        </small>
                    </div>
                </div>
                <?= Html::hiddenInput('payType') ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
</main>

<?php $this->registerJs("var priceForMsg = {$priceForMsg};", View::POS_HEAD); ?>
