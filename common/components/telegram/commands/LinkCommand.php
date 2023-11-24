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
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Yii;

/**
 * Link command
 */
class LinkCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'link';
    protected $description = 'Привязать Ваш Телеграм аккаунт к сервису Svezem.ru';
    protected $usage = '/link <code>';
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
                            'Правила отслеживания грузов всегда можно [настроить на странице подписки](https://svezem.ru/sub/)';
                }else{
                    Yii::warning("Не удалось обновить подписку: {$data['subscribeid']}, {$message->getFrom()->getId()}", 'TelegramCommand.Link');

                    $text = 'Произошла ошибка, попробуйте повторить операцию или обратитесь в [cлужбу поддержки](https://svezem.ru/contacts/)';
                }
            }else{
                Yii::warning("Ошиблись в команде: {$command}", 'TelegramCommand.Link');

                $text = 'Команда не распознана, попробуйте повторить операцию или обратитесь в [cлужбу поддержки](https://svezem.ru/contacts/)';
            }
        }else{
            $text = 'Скопируйте параметры команды на [странице подписки](https://svezem.ru/sub/).'."\n".
                    'Формат команды: /link код';
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
