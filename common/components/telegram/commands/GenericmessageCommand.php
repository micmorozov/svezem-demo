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

use common\components\telegram\conversation\Conversation;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'Genericmessage';
    protected $description = 'Handle generic message';
    protected $version = '1.0.2';
    protected $need_mysql = false;
    /**#@-*/

    /**
     * Execution if MySQL is required but not available
     *
     * @return boolean
     */
    public function executeNoDb()
    {
        //Do nothing
        return Request::emptyResponse();
    }

    /**
     * @return ServerResponse
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws TelegramException
     */
    public function execute()
    {
        $userId = $this->getMessage()->getFrom()->getId();
        $chatId = $this->getMessage()->getChat()->getId();

        /** @var Conversation $conversation */
        $conversation = Yii::$container->get(Conversation::class);
        $conversation->init($userId, $chatId);

        $note = &$conversation->note;

        if ($note) {
            if ($note->getCommand() == 'findcargo') {
                return $this->telegram->executeCommand('findcargo');
            }
        }

        // При общении в группе не отвечаем на неизвестные команды, что бы не засорять канал
        if(!$this->getMessage()->getChat()->isPrivateChat()){
            return Request::emptyResponse();
        }

        //Если неизвестная команда
        $data = [
            'chat_id' => $chatId,
            'text' => "Команда не распознана. Введите /help что бы получить справку по командам бота",
            //Убрать кнопки, если они есть
            'reply_markup' => ['remove_keyboard' => true]
        ];

        if ($data) {
            return Request::sendMessage($data);
        } else {
            return Request::emptyResponse();
        }
    }
}
