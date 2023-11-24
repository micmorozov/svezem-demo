<?php

namespace common\components\telegram;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Yii;

class Telegram extends \Longman\TelegramBot\Telegram
{
    /**
     * @param Update $update
     * @return ServerResponse
     * @throws TelegramException
     */
    public function processUpdate(Update $update)
    {
        $upd = (array)$update;

        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'data' => $upd,
            'channel' => 'telegram-bot-request'
        ]);

        return parent::processUpdate($update);
    }
}
