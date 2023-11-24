<?php

use common\models\PageTemplates;
use frontend\modules\tk\models\Tk;
use yii\helpers\Html;
use frontend\modules\tk\models\SphinxTk;
use common\models\LocationInterface;

/** @var $model SphinxTk */
/* @var $pageTpl PageTemplates */
/** @var $location LocationInterface */

/** @var Tk $modelTk */
$modelTk = $model->tk;

?>
<div class="companies__item item content__block row">
    <div class="item__block">
        <div class="item__info">
            <div class="item__img-block">
                <div class="services__item-head-logo">
                    <img style="max-width:86px;max-height:86px" src="<?=$modelTk->iconPath('preview_86', false, true)?>" alt="Транспортная компания <?=$modelTk->name;?>"/>
                </div>
            </div>
            <div class="item__details">
                <dic class="h3 item__name"><?= $modelTk->title($pageTpl??null)?></dic>
            </div>
        </div>
        <div class="item__contact-info">
            <br>
            <div class="item__adres">
                <?= $modelTk->getCityAddress($location) ?>
            </div>
            <div class="item__contact">
                <?php $emails = $modelTk->getEmails($location); ?>
                <?php if($emails): ?>
                    <br>
                    <span class="item__email">
                        <span class="strong">E-mail:</span>
                        <?php
                        foreach($emails as $email){
                            echo Html::beginTag('span', ['class' => "mob-green"]);
                            echo Html::a($email, 'mailto:'.$email);
                            echo Html::endTag('span');
                            echo '&nbsp;';
                        }
                        ?>
                    </span>
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="item__block">
        <div class="item__desc">
            <div class="desc__excerpt">
                <?= nl2br($model->snippet) ?>
            </div>
            <div class="desc__btn-wrap">
                <?= Html::a('Посмотреть подробнее описание <span class=" hide-mob">транспортной компании</span>',
                    $modelTk->getUrl(),
                    ['class' => "desc__btn content__block-btn", 'data-pjax' => 0]) ?>
            </div>
        </div>
    </div>
</div>