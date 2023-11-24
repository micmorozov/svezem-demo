<?php

namespace common\components\telegram\conversation;

use common\components\telegram\conversation\interfaces\ConversationStore;
use Longman\TelegramBot\Exception\TelegramException;

/**
 * Class Conversation (Беседа) контроллер управления беседы.
 * Необходим для хранения состояния между запросами к телеграм боту
 *
 * @package common\components\telegram\conversation
 */
class Conversation
{
    /**
     * Заметки связанные беседа
     *
     * @var ConversationNote $note
     */
    public $note = null;

    /**
     * Telegram user id
     *
     * @var int
     */
    protected $user_id;

    /**
     * Telegram chat id
     *
     * @var int
     */
    protected $chat_id;

    /** @var ConversationStore $store */
    private $store;

    /**
     * Команда беседы
     *
     * @var string
     */
    protected $command;

    /**
     * Conversation constructor.
     * @param ConversationStore $store
     */
    public function __construct(ConversationStore $store)
    {
        $this->store = $store;
    }

    /**
     * @param $user_id
     * @param $chat_id
     * @param $command
     * @throws TelegramException
     */
    public function init($user_id, $chat_id, $command = null)
    {
        $this->user_id = $user_id;
        $this->chat_id = $chat_id;
        $this->command = $command;

        //Try to load an existing conversation if possible
        if ( !$this->load() && $command !== null) {
            //A new conversation start
            $this->start();
        }
    }


    /**
     * Старт новой беседы если текущая команда не существует
     *
     * @return bool
     * @throws TelegramException
     */
    protected function start()
    {
        if ( !$this->exists()) {
            $this->createNote();

            if ($this->insert()) {
                return $this->load();
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function insert()
    {
        return $this->store->insert($this->user_id, $this->chat_id, $this->note);
    }

    public function delete()
    {
        return $this->store->delete($this->user_id, $this->chat_id);
    }

    /**
     * Загрузка беседы из хранилища
     *
     * @return bool
     * @throws TelegramException
     */
    protected function load()
    {
        $note = $this->store->get($this->user_id, $this->chat_id);
        if ($note) {
            //Если полученаня заметка не относится к данной команде,
            //то то удаляем
            if ($this->command && $note->getCommand() != $this->command) {
                $this->delete();
            } else {
                $this->note = $note;
            }
        }

        return $this->exists();
    }

    private function createNote()
    {
        $this->note = new ConversationNote($this->command);
    }

    /**
     * Проверка существования беседы
     *
     * @return bool
     */
    public function exists()
    {
        return ($this->note !== null);
    }

    /**
     * Обновление заметок беседы
     *
     * @return bool
     */
    public function update()
    {
        return $this->insert();
    }
}
