<?php

namespace common\components\bookingService\Store;

interface BaseStore
{
    /**
     * @param $userid
     * @return array|null
     */
    public function getData($userid):?array;

    /**
     * @param $userid
     * @param $bookingLimit
     * @param $bookingRemain
     * @param $expire
     * @param $dayLimit
     * @param $tariffId
     * @return bool
     */
    public function setBooking($userid, $bookingLimit, $bookingRemain, $expire, $dayLimit, $tariffId):bool;

    /**
     * @param $userid
     * @return bool
     */
    public function countIncrease($userid):bool;

    /**
     * @param $userid
     * @return bool
     */
    public function reduceCount($userid):bool;
}
