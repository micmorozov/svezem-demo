<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 24.10.17
 * Time: 11:12
 */

namespace frontend\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\Expression;

class RatingBehavior extends Behavior
{
    public function saveRating($score){
        $id = $this->getRatingId();

        if( !Yii::$app->redisTemp->sAdd('rating:ips:'.$id, Yii::$app->request->getUserIP()) )
            return false;

        $transaction = $this->owner::getDb()->beginTransaction();

        $this->owner->updateAttributes([
            'rating_sum' => new Expression("rating_sum + :score", [':score'=>$score]),
            'rating_voices' => new Expression("rating_voices + 1")
        ]);

        $this->owner->refresh();

        $rating = $this->owner->rating_sum / $this->owner->rating_voices;

        $this->owner->updateAttributes(['rating'=>$rating]);

        $transaction->commit();

        return true;
    }

    public function getReadOnly(){
        $id = $this->getRatingId();

        return true;//Yii::$app->redisTemp->sIsMember('rating:ips:'.$id, Yii::$app->request->getUserIP());
    }

    public function getRatingId(){
        return $this->owner::className()."_".$this->owner->id;
    }
}