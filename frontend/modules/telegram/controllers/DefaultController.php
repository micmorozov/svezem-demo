<?php

namespace frontend\modules\telegram\controllers;

use Longman\TelegramBot\Exception\TelegramException;
use common\components\telegram\Telegram;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\web\Controller;

/**
 * Default controller for the `telegram` module
 */
class DefaultController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function actionHook()
    {
        try{
            /** @var Telegram $telegram */
            $telegram = Yii::$container->get(Telegram::class);
            $telegram->handle();
        } catch (TelegramException $e){
            Yii::error($e->getMessage(), 'modules.telegram');
        }

        return null;
    }
}
