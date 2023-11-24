<?php

namespace common\components\telegram\conversation\interfaces;

use common\components\telegram\conversation\ConversationNote;

interface ConversationStore
{
    /**
     * @param $user_id
     * @param $chat_id
     * @param ConversationNote $note
     * @return mixed
     */
    public function insert($user_id, $chat_id, ConversationNote $note);

    /**
     * @param $user_id
     * @param $chat_id
     * @return ConversationNote
     */
    public function get($user_id, $chat_id):?ConversationNote;

    public function update($user_id, $chat_id):bool;

    public function delete($user_id, $chat_id):bool;
}
