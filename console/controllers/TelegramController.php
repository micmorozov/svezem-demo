<?php

namespace console\controllers;

use Longman\TelegramBot\Exception\TelegramException;
use common\components\telegram\Telegram;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\di\NotInstantiableException;

class TelegramController extends Controller
{
    /**
     * Обновления от Telegram
     * @return |null
     * @throws TelegramException
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function actionUpdate()
    {
        do {
            try{
                /** @var Telegram $telegram */
                $telegram = Yii::$container->get(Telegram::class);
                $telegram->useGetUpdatesWithoutDatabase(true);
                $telegram->handleGetUpdates();

            } catch (TelegramException $e){
                echo $e->getMessage()."\n";
            }

            sleep(2);
        } while (1);

        return null;
    }

    /**
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function actionSetWebhook()
    {
        try{
            /** @var Telegram $telegram */
            $telegram = Yii::$container->get(Telegram::class);

            $hook_url = 'https://'.Yii::getAlias('@domain').'/telegram/default/hook/';

            // Set webhook
            $result = $telegram->setWebhook($hook_url);
            if ($result->isOk()) {
                echo $result->getDescription()."\n";
            }
        } catch (TelegramException $e){
            echo "Не удалось установить hook\n";
            echo $e->getMessage()."\n";
        }
    }

    public function actionUnsetWebhook()
    {
        try{
            /** @var Telegram $telegram */
            $telegram = Yii::$container->get(Telegram::class);

            // Handle telegram getUpdates request
            $result = $telegram->deleteWebhook();
            if ($result->isOk()) {
                echo $result->getDescription()."\n";
            }
        } catch (TelegramException $e){
            echo "Не удалось сбросить hook\n";
            echo $e->getMessage()."\n";
        }
    }
}
