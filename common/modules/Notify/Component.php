<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 13.02.19
 * Time: 11:32
 */

namespace common\modules\Notify;

use common\modules\Notify\assets\NotifyAsset;
use yii\web\View;
use Yii;

class Component extends \yii\base\Component
{
    public function init(){
        $this->foo();

        parent::init();
    }

    protected function foo(){
        $view = Yii::$app->getView();

        $view->on(View::EVENT_END_BODY, [$this, 'renderNotify']);
    }

    public function renderNotify(){
        $view = Yii::$app->getView();

        NotifyAsset::register($view);

        $page = Yii::$app->controller->module->id.'/'.Yii::$app->controller->id.'/'.Yii::$app->controller->action->id;
        $view->registerJs("popUpNotify.notifyRequest('$page')");
    }
}