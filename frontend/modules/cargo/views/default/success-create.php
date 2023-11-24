<?php

use common\models\Cargo;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;

/** @var $transportDataProvider ArrayDataProvider */
/** @var $transportSearchButtonUrl string */
/** @var $tkDataProvider ArrayDataProvider */
/** @var $tkSearchButtonUrl string */
/** @var Cargo $cargo */

// Склоняем предложение
$this->title = "Заявка размещена";
?>
<main class="content">
    <div class="container">
        <div class="text-center">
            <?= Html::img("/img/icons/payment/add-success.png", [
                'class' => 'img'
            ]); ?>
            <div class="h4 text-uppercase">Поздравляем!<br/>Заявка размещена, в ближайшее время к Вам начнут поступать
                предложения по заказу
            </div>
        </div>

        <?php if ($cargo->city_from != $cargo->city_to): ?>
            <div class="cargo-search__title-wrap">
                <h2 class="content__title"  style="margin-top: 25px; margin-bottom: 10px;">Этапы работы с перевозчиком</h2>
                Договариваясь о перевозке старайтесь соблюдать несколько простых правил. Это позволит сэкономить время,
                деньги и нервы.
                <ol>
                    <li>Обязательно подпишите с перевозчиком договор на перевозку. Шаблон договора можно <?= Html::a('скачать здесь',
                            'https://' . Yii::getAlias('@domain') . Url::toRoute(['/download', 'file'=>'Проект договора по грузоотправителю.docx']), ['rel' => 'nofollow']) ?>. Он всегда будет доступен внизу сайта в разделе "Заказчикам"
                    </li>
                    <li>Внимательно проверьте паспортные данные перевозчика</li>
                    <li>Составьте опись груза в соответствии с договором</li>
                    <li>После загрузки сделайте фотографии машины, так, что бы был виден гос. номер автомобиля</li>
                </ol>
            </div>
        <?php endif; ?>

        <?php if ($transportDataProvider->count): ?>
            <div class="cargo-search__title-wrap" style="margin-top: 25px; margin-bottom: 0px;">
                <h2 class="content__title">Рекомендуемые перевозчики</h2>
            </div>

            <?= ListView::widget([
                'dataProvider' => $transportDataProvider,
                'emptyText' => '',
                'itemView' => '@frontend/modules/transport/views/search/_transport_item',
                'itemOptions' => [
                    'tag' => false
                ],
                'options' => [
                    'tag' => 'div'
                ],
                'layout' => "{items}"
            ]);
            ?>

            <div class="content__more-btn-wrap">
                <?= Html::a('Показать больше', $transportSearchButtonUrl, [
                    'class' => 'content__more-btn'
                ]) ?>
            </div>
        <?php endif; ?>

        <br>

        <?php if ($tkDataProvider->count): ?>
            <div class="content__services companies content__item">
                <h2 class="content__title">Рекомендуемые транспортные компании</h2>
                <?= ListView::widget([
                    'dataProvider' => $tkDataProvider,
                    'itemView' => '@frontend/modules/tk/views/search/_tk_item',
                    'viewParams' => [
                        'location' => $cargo->cityFrom
                    ],
                    'itemOptions' => [
                        'tag' => false
                    ],
                    'options' => [
                        'tag' => 'div'
                    ],
                    'layout' => "{items}"
                ]);
                ?>
            </div>


            <div class="content__more-btn-wrap">
                <?= Html::a('Показать больше', $tkSearchButtonUrl, [
                    'class' => 'content__more-btn'
                ]) ?>
            </div>
        <? endif; ?>

        <?php if ($transportDataProvider->count == 0 && $tkDataProvider->count == 0): ?>
            <div class="content__more-btn-wrap">
                <?= Html::a('Найти перевозчика самостоятельно', Url::toRoute('/transport/default/search/'), [
                    'class' => 'content__more-btn'
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</main>
