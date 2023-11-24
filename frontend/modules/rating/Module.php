<?php

namespace frontend\modules\rating;

use frontend\modules\rating\storages\RedisStorage;
use frontend\modules\rating\storages\StorageInterface;
use Yii;

/**
 * Module module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'frontend\modules\rating\controllers';

    /** @var StorageInterface $storage */
    public $storage;

    /**
     * {@inheritdoc}
     */
    public function init(){
        parent::init();

        if( !isset($this->storage)){
            $this->storage = new RedisStorage(['redis' => Yii::$app->redisTemp]);
        }
    }
}
