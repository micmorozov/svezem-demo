<?php

namespace frontend\modules\rating\widget;

use frontend\modules\rating\models\Rating as RatingModel;
use frontend\modules\rating\widget\assets\RatingAsset;
use Yii;
use yii\base\Widget;
use yii\web\JsExpression;
use yii2mod\rating\StarRating;

class Rating extends Widget
{
    public $name;

    public $clickURL = '/rating/default/save/';

    public function run()
    {
        $this->registerAssets();

        $model = RatingModel::find($this->name);

        echo StarRating::widget([
            'id' => $this->id,
            'options' => ['class' => 'rating'],
            'name' => 'input_name',
            //'value' => $model->score,
            'clientOptions' => [
                'path' => "https://".Yii::getAlias('@assetsDomain/img/rating'),
                'click' => new JsExpression(strtr("clickCallback(':url', ':id')", [
                    ':url' => $this->clickURL,
                    ':id' => $model->getRatingId()
                ])),
                //'readOnly' => $model->readOnly,
                'hints' => ['плохо', 'так себе', 'нормально', 'хорошо', 'отлично'],
//                'starType' => 'i',
//                'starOn' => 'fa fa-fw fa-star',
//                'starOff' => 'fa fa-fw fa-star-o',
                //'starHalf' => 'star-half.png'

            ],
        ]);

        ?>
        <div class="rating__details">Рейтинг: <span class="rating__score" id="ratingScore"><?= sprintf('%.2f',
                    $model->score) ?></span>,
            голосов <span id="ratingSum" class="rating__voices"><?= $model->sum ?></span>
        </div>
        <?php

        $view = $this->getView();
        $js = "getRating('#{$this->id}', '{$model->getRatingId()}');";
        $view->registerJs($js);
    }

    protected function registerAssets()
    {
        RatingAsset::register($this->view);
    }
}