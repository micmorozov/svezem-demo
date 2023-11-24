<?php

use frontend\modules\cargo\widgets\models\CargoCarriageModel;
use yii\helpers\Json;
use yii\web\View;
use yii\helpers\Html;

/** @var View $this */
/** @var CargoCarriageModel $model */

// placeholder для поля описание груза
$cargo_hint = 'Пример: ' . ((isset($pageTpl) && $pageTpl->cargo_hint) ? $pageTpl->cargo_hint : 'Нужно перевезти шкаф, диван и кровать, общей массой около 50 кг');

$one_city = $pageTpl && $pageTpl->one_city ? true : false;

$CargoCarriage = [
    'oneCity' => $one_city,
    'category_id' => $model->category_id
];
$this->registerJs("window.CargoCarriage = " . Json::encode($CargoCarriage) . ";", View::POS_HEAD);
?>

<div id="cargoCarriage" v-cloak>
    <div class="visible-sm visible-xs" v-if="oneCitySelect">
        <div class="v-cloak--hidden">
            <span id="mobileCitySelectlabel">
                {{!oneCity ? 'Между городами' : 'По городу'}}
            </span>
            <a href="#" rel='nofollow' @click="oneCityChange">({{oneCity ? 'Между городами' : 'По городу'}})</a>
        </div>
        <br>
    </div>
    <div class="">
        <div class="row">
            <div class="form-group col-sm-6 required" :class="oneCity ? 'col-md-12' : 'col-md-6'">
                <label class="v-cloak--inlineBlock">
                    <span>Откуда</span>
                </label>
                <label for="cargocarriagemodel-cityfrom" class="v-cloak--hidden">
                    <span>{{oneCity ? 'Город' : 'Откуда'}}</span>
                    <span class="hidden-sm hidden-xs" v-if="oneCitySelect">
                            &nbsp;(<a class="citySelector" href="#" rel="nofollow" @click="oneCityChange">{{oneCity ? 'Между городами' : 'По городу'}}</a>)
                        </span>
                </label>
                <select id="cargocarriagemodel-cityfrom" class="form-control ajax-select" style="width: 100%"
                        aria-required="true"></select>
            </div>
            <div class="form-group col-md-6 col-sm-6 required" :style="{display: oneCity?'none':null }">
                <div class="tooltip fade top in form__field_error" role="tooltip" style="display: none">
                    <div class='tooltip-arrow' style='left: 50%;'></div>
                    <div class='tooltip-inner'></div>
                </div>
                <label class="form-label" for="cargocarriagemodel-cityto">Куда</label>
                <select id="cargocarriagemodel-cityto" class="form-control ajax-select" style="width: 100%"
                        aria-required="true">
                </select>
            </div>
            <div class="form-group col-md-6 col-sm-6 required" :class="{ 'has-error': descriptionErr }">
                <label for="cargocarriagemodel-description">
                    Описание <span class="hide-mob">заявки</span>
                </label>
                <textarea v-model="description"
                          rows="4"
                          id="cargocarriagemodel-description"
                          class="form-control"
                          placeholder="<?= htmlspecialchars($cargo_hint) ?>"
                          aria-required="true">
                    </textarea>
            </div>
            <div class="form-group col-md-6 col-sm-6 required" :class="{ 'has-error': phoneErr }">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" class="form-control" aria-required="true" v-model="phone"
                       @keyup.enter="send"/>
            </div>
            <div class="col-md-6 col-sm-6 text-center">
                <div class="form-group clearfix">
                    <button type="submit" class="btn btn-primary btn-svezem pull-right" @click="send"
                            :disabled="sending"
                            :class="{ 'form-disable-button': sending }">
                        Заказать
                    </button>
                    <span v-if="formErr" class="text-danger v-cloak--hidden">
                            {{formErr}}
                        </span>
                </div>
            </div>
        </div>
        <small style="float: right; text-align: right;">
            Нажимая "Заказать", Вы соглашаетесь с <?= Html::a('политикой конфиденциальности', '//'.Yii::getAlias('@domain').'/info/legal/privacy-policy/', [
                'target' => '_blank',
                'rel' => 'nofollow'
            ]) ?>
        </small>
    </div>
</div>
