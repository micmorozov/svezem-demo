<?php

namespace frontend\modules\rating\storages;

use Redis;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;

class RedisStorage extends Component implements StorageInterface
{
    /** @var Redis $redis */
    public $redis;

    const ipKey = 'rating:ips:';
    const idKey = 'rating:id:';

    public function __construct($config = []){
        parent::__construct($config);

        if( !isset($config['redis']) ){
            throw new InvalidArgumentException('Необходимо указать redis');
        }

        $this->redis = $config['redis'];
    }

    /**
     * @param $id
     * @param $score
     * @return array|bool
     */
    public function save($id, $score){
        if( !$this->redis->sAdd(self::ipKey.$id, Yii::$app->request->getUserIP()) ){
            return false;
        }

        $voteSum = $this->redis->multi()
            ->hIncrBy(self::idKey.$id, 'sum', $score)
            ->hIncrBy(self::idKey.$id, 'voices', 1)
            ->exec();

        return [
            'score' => sprintf('%.2f', $voteSum[0]/$voteSum[1]),
            'sum' => $voteSum[1]
        ];
    }

    public function get($id){
        $res = $this->redis->hMGet(self::idKey.$id, ['sum', 'voices']);

        $res['voices'] = $res['voices'] ? $res['voices'] : 0;
        $score = !$res['voices'] ? 0 : $res['sum']/$res['voices'];

        return [
            'score' => $score,
            'sum' => $res['voices']
        ];
    }

    public function isSet($id):bool{
        return $this->redis->sIsMember(self::ipKey.$id, Yii::$app->request->getUserIP());
    }
}