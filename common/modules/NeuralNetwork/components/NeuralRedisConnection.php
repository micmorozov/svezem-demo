<?php

namespace common\modules\NeuralNetwork\components;

use common\components\ARedisConnection;
use common\modules\NeuralNetwork\NrRedis;
use Exception;

/**
 * Represents a redis connection.
 *
 * @author Charles Pick
 * @package packages.redis
 */
class NeuralRedisConnection extends ARedisConnection  {
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new NrRedis();
            if($this->persistent) $this->_client->pconnect($this->hostname, $this->port, $this->timeout);
            else $this->_client->connect($this->hostname, $this->port);
            if (isset($this->password)) {
                if ($this->_client->auth($this->password) === false) {
                    throw new Exception('Redis authentication failed!');
                }
            }
        }
        $this->_client->select($this->database);
        return $this->_client;
    }
}
