<?php

namespace common\components\telegram\conversation\interfaces;

use common\components\telegram\conversation\ConversationNote;
use Redis;

class RedisConversationStore implements ConversationStore
{
    /** @var Redis */
    private $redis;

    /**
     * Время жизни беседы(сек)
     * @var int
     */
    public $ttl = 3600;

    public $key_prefix = 'telegramConversation';

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    private function getKey($user_id, $chat_id)
    {
        return $this->key_prefix.':'.$chat_id.':'.$user_id;
    }

    /**
     * @param $user_id
     * @param $chat_id
     * @param ConversationNote $note
     * @return mixed|void
     */
    public function insert($user_id, $chat_id, ConversationNote $note)
    {
        $this->redis->setex($this->getKey($user_id, $chat_id), $this->ttl, serialize($note));
    }

    /**
     * @param $user_id
     * @param $chat_id
     * @return ConversationNote|null
     */
    public function get($user_id, $chat_id):?ConversationNote
    {
        $note = $this->redis->get($this->getKey($user_id, $chat_id));

        if( !$note )
            return null;

        return unserialize($note);
    }

    /**
     * @param $user_id
     * @param $chat_id
     * @return bool
     */
    public function update($user_id, $chat_id):bool
    {
        return $this->redis->hMSet($this->getKey($user_id, $chat_id), []);
    }

    /**
     * @param $user_id
     * @param $chat_id
     * @return bool
     */
    public function delete($user_id, $chat_id):bool
    {
        return $this->redis->del($this->getKey($user_id, $chat_id));
    }


}
