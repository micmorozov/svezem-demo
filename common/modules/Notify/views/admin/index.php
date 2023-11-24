<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\modules\Notify\models\NotifyRule;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Правила уведомлений';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notify-rule-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать правило', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                    'attribute' => 'page',
                    'value' => function($model){
                        /** @var  $model NotifyRule */
                        return NotifyRule::getPageLabel($model->page);
                    }
            ],
            'message',
            [
                'attribute' => 'type',
                'value' => function($model){
                    /** @var  $model NotifyRule */
                    return NotifyRule::getTypeLabel($model->type);
                }
            ],
            'url',
            'delay',
            'rule:ntext',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
