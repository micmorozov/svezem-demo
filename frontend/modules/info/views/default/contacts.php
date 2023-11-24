<?php

use common\helpers\PhoneHelpers;
use common\models\Contacts;
use ferrumfist\yii2\recaptcha\ReCaptcha;
use frontend\modules\info\assets\ContactsAsset;
use frontend\modules\info\models\Feedback;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/** @var $contacts Contacts */
/** @var $this View */
/** @var $feedback Feedback */
// Устанавливаем значение по умолчанию
$title = 'Контакты';
$descr = 'Контакты';
$keywords = 'Контакты';
$h1 = 'Контакты';
$text = '';
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

$companyRequisites = Yii::$app->params['requisites'];
?>
<main class="content">
    <div class="container contact">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <div class="contact-block">
            <div class="contact-block-header">
                <div class="contact-block__address"><strong>Адрес: </strong>
                    <?= $contacts->city->title_ru . ", " . $contacts->city->region_ru . ". " . $contacts->address ?>
                </div>
                <?php if (!empty($contacts->email)): ?>
                    <div class="contact-block__email">
                        <strong>E-mail: </strong>
                        <?php
                        $links = [];
                        foreach ($contacts->email as $email) {
                            $links[] = Html::a($email, "mailto:$email", ['target' => '_blank']);
                        }
                        echo implode(', ', $links);
                        ?>
                    </div>
                <?php endif ?>

                <?php if (!empty($contacts->phone)): ?>
                    <div class="contact-block__phone">
                        <strong>Телефон для общих вопросов: </strong>
                        <?php
                        $links = [];
                        foreach ($contacts->phone as $phone) {
                            $links[] = Html::a(PhoneHelpers::formatter($phone), "tel:" . PhoneHelpers::formatter($phone, '', true));
                        }
                        echo implode(', ', $links);
                        ?>
                    </div>
                <?php endif ?>

                <div class="contact-block__phone">
                    <strong>Телефон для бесплатной юридической консультации: </strong>
                    <a href="tel:88003508413,692">8 800 350-84-13 добавочный 692</a> Звонок по России бесплатный.
                </div>

                <?php if (!empty($contacts->viber)): ?>
                    <div class="contact-block__phone">
                        <strong>Viber: </strong>
                        <?php
                        $links = [];
                        foreach ($contacts->viber as $phone) {
                            $links[] = Html::a(PhoneHelpers::formatter($phone), "viber://chat?number=$phone");
                        }
                        echo implode(', ', $links);
                        ?>
                    </div>
                <?php endif ?>

                <?php if (!empty($contacts->whatsapp)): ?>
                    <div class="contact-block__phone">
                        <strong>WhatsApp: </strong>
                        <?php
                        $links = [];
                        foreach ($contacts->whatsapp as $phone) {
                            $links[] = Html::a(PhoneHelpers::formatter($phone), " https://wa.me/" . PhoneHelpers::formatter($phone, '', true));
                        }
                        echo implode(', ', $links);
                        ?>
                    </div>
                <?php endif ?>

                <?php if (!empty($contacts->telegram)): ?>
                    <div class="contact-block__phone">
                        <strong>Telegram: </strong>
                        <?php
                        $links = [];
                        foreach ($contacts->telegram as $phone) {
                            $links[] = Html::a(PhoneHelpers::formatter($phone), "tel:" . PhoneHelpers::formatter($phone, '', true));
                        }
                        echo implode(', ', $links);
                        ?>
                    </div>
                <?php endif ?>

                <?php if ($contacts->skype): ?>
                    <div class="contact-block__address"><strong>Skype: </strong>
                        <?php
                        $links = [];
                        foreach ($contacts->skype as $skype) {
                            $links[] = Html::a($skype, "skype:$skype?chat");
                        }
                        echo implode(', ', $links);
                        ?>
                    </div>
                <?php endif ?>
            </div>
            <?php
            if ($contacts->city) {
                $zoom = $contacts->address ? 17 : 10;
                ContactsAsset::register($this);
                ?>
                <div id="contactVue">
                    <gmap
                            class="contact-block-map"
                            :address="'<?= "{$contacts->city->title_ru} {$contacts->address}" ?>'"
                            :zoom=<?= $zoom ?>
                    ></gmap>
                </div>
            <?php } ?>
        </div>
        <div class="content__line hide-mob"></div>
        <h2 class="contact-form__title">Форма обратной связи</h2>
        <div class="panel">
            <div class="panel-body">
                <?php $form = ActiveForm::begin([
                    //'fieldConfig' => ['class'=>'frontend\components\field\ExtendField']
                ])
                ?>
                <div class="col-md-6">
                    <?= $form->field($feedback, 'name', [
                        'options' => ['class' => 'form-group'],
                        'inputOptions' => [
                            'class' => 'form-control',
                            'placeholder' => "Имя"
                        ]
                    ]);
                    ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($feedback, 'email', [
                        'options' => ['class' => 'form-group'],
                        'inputOptions' => [
                            'class' => "form-control",
                            'placeholder' => "E-mail"
                        ]
                    ]);
                    ?>
                </div>
                <div class="col-md-12">
                    <?= $form->field($feedback, 'body', [
                        'options' => ['class' => 'form-group']
                    ])->textarea([
                        'class' => "form-control",
                        'placeholder' => "Ваше сообщение"
                    ]);
                    ?>
                </div>
                <div class="col-md-12">
                    <?= $form->field($feedback, 'reCaptcha')
                        ->label(false)
                        ->widget(ReCaptcha::class);
                    ?>
                </div>
                <div class="col-md-12">
                    <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary content__btn']) ?>
                </div>

                <?php ActiveForm::end() ?>
            </div>
        </div>

        <div class="content__line"></div>
        <div class="contact-form">
            <h2 class="contact-form__title">Реквизиты</h2>
            <div>
                <?= $companyRequisites['organization'] ?><br>
                ИНН/КПП <?= $companyRequisites['inn'] ?>/<?= $companyRequisites['kpp'] ?><br>
                ОГРН <?= $companyRequisites['ogrn'] ?><br>

                <br>
                <?= $companyRequisites['bank'] ?><br>
                БИК <?= $companyRequisites['bic'] ?><br>
                Кор.счет <?= $companyRequisites['corr_account'] ?><br>
                Расчетный счет <?= $companyRequisites['account'] ?><br>
                Юр. адрес <?= $companyRequisites['jur_address'] ?><br>
                Фактический адрес <?= $companyRequisites['fact_address'] ?><br>
                Телефон <?= $companyRequisites['phone'] ?><br>
            </div>
        </div>

        <br>

        <?= $this->render('_tags', ['tags' => $tags??[]]); ?>

    </div>
</main>
