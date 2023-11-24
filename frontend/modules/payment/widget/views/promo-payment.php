<?php

use frontend\widgets\Select2;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/** @var $label string */
/** @var array $mainServices */
/** @var array services */
/** @var string $url */
/** @var string $item_id */
/** @var bool $onlyOne */
/** @var bool $authUserOnly */

$checkItemType = $onlyOne ? 'radio' : 'checkbox';
?>
<div class="promo-payment">
    <?php ActiveForm::begin(['action' => $url]); ?>
    <?= Html::hiddenInput('item_id', $item_id) ?>
    <?php if ($mainServices) : ?>
        <?php foreach ($mainServices as $id => $mainService): ?>
            <div class="col-md-12">
                <div class="promo-payment__item promo-payment__item-vip row display-flex">
                    <div class="col-xs-12 col-sm-12 col-md-3 promo-payment__item-check text-center">
                        <label for="<?= $id; ?>" class="promo-payment__item-check-img-label">
                        <span class="custom-checkbox">
                            <input class="promo-option"
                                   <?= isset($mainService['checked']) && !$mainService['checked'] ?: 'checked' ?>
                                   type="<?= $checkItemType ?>" name="checkItem" id="<?= $id; ?>"/>
                            <span class="checkmark <?= $onlyOne ? 'dot' : ''; ?> "></span>
                        </span>
                            <?= Html::img("/img/icons/payment/$id.svg", [
                                'class' => 'img-responsive promo-payment__item-check-img'
                            ]); ?>
                        </label>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-9 promo-payment__item-info">
                        <div class="promo-payment__item-info-title"
                             style="display: inline-block;width: 100%; margin-top: 0;">
                            <span class="h3" style="margin-top: 0"><?= $mainService['title']; ?>
                            <span class="h3 pull-right promo-payment__item-price-count price_for_service"
                                  style="margin:8px 0;"></span></span>
                            <div style="margin: 12px 0;">
                                <?php if (count($mainService['options']) > 1) : ?>
                                    <?php
                                    $optionData = [];
                                    $options = [];
                                    foreach ($mainService['options'] as $_id => $option) {
                                        $optionData[$_id] = ['data-price' => $option['price']];
                                        $options[$_id] = $option['description'] . ' (' . $option['price'] . ' р.)';
                                    }
                                    ?>
                                    <span class="limiter">
                                        <?= Select2::widget([
                                            'name' => 'rates',
                                            'data' => $options,
                                            'options' => [
                                                'data-minimum-results-for-search' => "Infinity",
                                                'style' => 'display:inline-block',
                                                'class' => 'promo-payment__item-price-value',
                                                'options' => $optionData,
                                            ],
                                            'pluginOptions' => [
                                                'width' => '100%',
                                                'placeholder' => "Выберите",
                                            ]
                                        ]); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="h3"><small><?= current($mainService['options'])['description'] ?></small></span>
                                    <span class="promo-payment__item-price-value"
                                          data-price="<?= current($mainService['options'])['price']; ?>">
                                <?= Html::hiddenInput('rates', key($mainService['options'])) ?>
                            </span>
                                <?php endif; ?>

                            </div>

                        </div>
                        <div class="divider"></div>
                        <div>
                            <?= $mainService['description']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($services)): ?>
        <span class="h4" style="margin: 32px 0; display: inline-block">Дополнительные предложения</span>
    <?php endif; ?>
    <?php foreach ($services as $id => $service) : ?>
        <div class="col-sm-12">
            <div class="promo-payment__item row display-flex">
                <div class="col-xs-12 col-sm-12 col-md-3 promo-payment__item-check">
                    <div style="padding-top: 16px;">
                    <span style="padding-left: 35px">
                    <label for="<?= $id; ?>" class="custom-checkbox">
                        <input class="promo-option" <?= !$service['checked'] ?: 'checked' ?> type="checkbox"
                               name="<?= $id; ?>" id="<?= $id; ?>"/>
                        <span class="checkmark"></span>
                        <?= $service['title'] ?>
                    </label>
                    <small style="padding-left: 35px">
                        <?= current($service['options'])['description'] ?>
                    </small>
                    </span>
                    </div>
                    <span class="promo-payment__item-price-value visible-sm visible-xs"
                          style="padding-left: 35px;word-wrap: normal"
                          data-price="<?= current($service['options'])['price']; ?>">
                                <span class="h3 promo-payment__item-price-count price_for_service"></span>
                                <?= Html::hiddenInput('rates', key($service['options'])) ?>
                            </span>
                </div>
                <div class="col-md-9 promo-payment__item-info">
                    <div class="promo-payment__item-box">
                        <div class="promo-payment__item-description promo-payment__item-box-center"
                             style="vertical-align: top;">
                            <?= $service['description']; ?>
                        </div>
                        <span class="pull-right promo-payment__item-price-value hidden-sm hidden-xs"
                              style="word-wrap: normal" data-price="<?= current($service['options'])['price']; ?>">
                        <span class="h3 promo-payment__item-price-count price_for_service"
                              style="padding-left: 10px;"></span>
                        <?= Html::hiddenInput('rates', key($service['options'])) ?>
                    </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <hr style="border-top: 1px dashed #d7d7d7"/>
    <div class="row">
        <div class="col-md-12 text-right" style="padding: 6px 15px;">
            Всего к оплате:&nbsp;&nbsp;<span class="promoTotalPrice h3">0</span>&nbsp;р.
        </div>
        <div class="col-md-12 text-right">

                <?php if ($authUserOnly && Yii::$app->user->isGuest): ?>
                    <?= Html::a('Для оплаты необходимо авторизоваться', Yii::$app->user->loginUrl) ?>
                <?php else: ?>
                    <button class="pay-button btn btn-primary btn-svezem" data-pay='card' style="max-width: 100%; width: 350px;margin-bottom: 12px">
                        <span>Оплатить банковской картой</span>
                    </button>

                    <button class="pay-button btn btn-primary btn-svezem" data-pay='juridical' style="max-width: 100%; width: 350px;margin-bottom: 12px">
                        <span>Оплатить как юр лицо или ИП</span>
                    </button>

                <div class="text-right" style="padding-top: 12px;">
                    <small>
                    Оплачивая услуги, вы принимаете <?= Html::a('договор-оферту',
                        '//' . Yii::getAlias('@domain') . '/info/legal/public-offer/', [
                            'target'=>'_blank',
                            'rel' => 'nofollow',
                            'data-pjax' => 0
                        ]) ?>
                    </small>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
