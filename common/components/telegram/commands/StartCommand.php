<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\helpers\UserHelper;
use frontend\modules\subscribe\models\Subscribe;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Yii;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
    protected $version = '0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $command = trim($message->getText(true));
        $chat_id = $message->getChat()->getId();

        if($command){
            $data = UserHelper::getDataByAnyCode($command);
            if(isset($data['subscribeid'])){
                $upd = Subscribe::updateAll(['telegram' => $message->getFrom()->getId()], 'id=:id',
                    [':id' => $data['subscribeid']]);
                if($upd) {
                    $text = 'Ваш аккаунт на сайте Svezem.ru удачно привязан к боту.'."\n".
                            'Теперь вы будете получать уведомления о новых грузах в свой Телеграм'."\n\n".
                            'Правила отслеживания грузов всегда можно настроить на [странице подписки](https://svezem.ru/sub/)';
                }else{
                    Yii::warning("Не удалось обновить подписку: {$data['subscribeid']}, {$message->getFrom()->getId()}", 'TelegramCommand.Start');

                    $text = 'Произошла ошибка, попробуйте повторить операцию или обратитесь в [cлужбу поддержки](https://svezem.ru/contacts/)';
                }
            }else{
                Yii::warning("Ошиблись в команде: {$command}", 'TelegramCommand.Start');

                $text = 'Команда не распознана, попробуйте повторить операцию или обратитесь в [cлужбу поддержки](https://svezem.ru/contacts/)';
            }
        }else{
            $text = 'Вас приветствует бот Svezem.ru'."\n".
                    'Что бы узнать что я умею, набери /help'."\n".
                    'Удачной работы!';
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];

        return Request::sendMessage($data);
    }
}
