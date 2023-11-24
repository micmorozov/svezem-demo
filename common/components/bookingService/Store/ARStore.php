<?php

namespace common\components\bookingService\Store;

use common\models\UserServiceBooking;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\Expression;

class ARStore implements BaseStore
{
    private $className;

    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * @return UserServiceBooking
     * @throws InvalidConfigException
     */
    private function makeObject():ActiveRecord
    {
        return Yii::createObject($this->className);
    }

    /**
     * @param $userid
     * @return array|null
     * @throws InvalidConfigException
     */
    public function getData($userid):?array
    {
        $obj = $this->makeObject();
        $model = $obj::findOne($userid);
        if ( !$model) {
            return null;
        }

        return [
            'user' => $model->user,
            'expire' => $model->expire,
            'booking_remain' => $model->booking_remain,
            'booking_limit' => $model->booking_limit,
            'day_limit' => $model->day_limit,
            'tariff_id' => $model->tariff_id
        ];
    }

    /**
     * @param $userid
     * @param $bookingLimit
     * @param $bookingRemain
     * @param $expire
     * @param $dayLimit
     * @param $tariffId
     * @return bool
     * @throws InvalidConfigException
     */
    public function setBooking($userid, $bookingLimit, $bookingRemain, $expire, $dayLimit, $tariffId):bool
    {
        /** @var UserServiceBooking $obj */
        $obj = $this->makeObject();
        $model = $obj::findOne($userid);

        if ( !$model) {
            $model = $obj;
        }

        $model->userid = $userid;
        $model->booking_limit = $bookingLimit;
        $model->booking_remain = $bookingRemain;
        $model->expire = $expire;
        $model->day_limit = $dayLimit;
        $model->tariff_id = $tariffId;

        if ( !$model->save()) {
            return false;
        }

        return true;
    }

    /**
     * @param $userid
     * @return bool
     * @throws InvalidConfigException
     */
    public function countIncrease($userid):bool
    {
        $obj = $this->makeObject();

        return (bool)$obj::updateAllCounters([
            'booking_remain' => 1
        ], [
            'and',
            ['userid' => $userid],
            //Чтобы при смене тарифа и отмене бронирования
            //кол-во оставшихся не превышало лимит
            ['<', 'booking_remain', new Expression('booking_limit')]
        ]);
    }

    /**
     * @param $userid
     * @return bool
     * @throws InvalidConfigException
     */
    public function reduceCount($userid):bool
    {
        /** @var UserServiceBooking $obj */
        $obj = $this->makeObject();

        return (bool)$obj::updateAllCounters([
            'booking_remain' => -1
        ], ['and',
            ['userid' => $userid],
            ['>', 'booking_remain', 0]
        ]);
    }
}
