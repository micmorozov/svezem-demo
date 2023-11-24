<?php

namespace common\components\telegram\conversation;

use Longman\TelegramBot\Entities\Location;

/**
 * Class ConversationNote
 * @package common\components\telegram\conversation
 */
class ConversationNote
{
    private $_command;

    /** @var Location $_location */
    private $_location;

    private $_data;

    public function __construct($command)
    {
        $this->_command = $command;
    }

    /**
     * @return mixed
     */
    public function getCommand(){
        return $this->_command;
    }

    /**
     * @param $location
     */
    public function setLocation(Location $location){
        $this->_location = $location;
    }

    /**
     * @return Location
     */
    public function getLocation(){
        return $this->_location;
    }

    /**
     * @param $data
     */
    public function setData($data){
        $this->_data = $data;
    }

    /**
     * @return mixed
     */
    public function getData(){
        return $this->_data;
    }
}
