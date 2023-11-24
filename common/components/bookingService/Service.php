<?php

namespace common\components\bookingService;

use common\components\bookingService\Store\BaseStore;
use Redis;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

/**
 * Class Service
 * @package common\components\bookingService
 */
class Service
{
    /**
     * Хранилище данных бронирования
     * @var BaseStore object
     */
    private $store;

    /**
     * Redis хранилище
     * @var Redis $redis
     * */
    private $redis;

    /**
     * ИД пользователя
     * @var int $userid
     */
    private $userid;

    /**
     * Данные бронирования
     * @var array $object
     */
    private $object;

    /**
     * Ключ в редисе для для подсчета кол-ва бронирований в сутки
     */
    const BOOKING_DAY_LIMIT_COUNTER_KEY = 'bookingDayLimitCounter';

    /**
     * Service constructor.
     * @param $userid
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function __construct($userid)
    {
        $this->store = Yii::$container->get(BaseStore::class);
        $this->redis = Yii::$app->redisTemp;

        $this->userid = $userid;
        $this->init();
    }

    /**
     * Получение актуальных данных о бронировании
     */
    private function init()
    {
        if ( !$this->object = $this->store->getData($this->userid)) {
            $this->object = [
                'user' => null,
                'expire' => 0,
                'booking_remain' => 0,
                'booking_limit' => 0,
                'day_limit' => 0,
                'tariff_id' => null
            ];
        }
    }

    /**
     * Имеет ли возможность пользователь бронировать
     * @return bool
     */
    public function canBooking():bool
    {
        $expire = $this->getExpire();

        if ( !$expire) {
            return false;
        }

        return $expire > time();
    }

    /**
     * Кол-во оставшихся броней
     * @return int
     */
    public function getBookingRemain():int
    {
        return $this->object['booking_remain'];
    }

    /**
     * Лимит бронирований
     * @return int
     */
    public function getBookingLimit():int
    {
        return $this->object['booking_limit'];
    }

    /**
     * Timestamp до которого оплачено бронирование
     * @return mixed
     */
    public function getExpire()
    {
        return $this->object['expire'];
    }

    /**
     * Кол-во броинрований в сутки
     * @return mixed
     */
    public function getDayLimit()
    {
        return $this->object['day_limit'];
    }

    /**
     * ИД тарифа
     * @return mixed
     */
    public function getTariffId()
    {
        return $this->object['tariff_id'];
    }

    /**
     * Установаить бронирование (при оплате)
     * @param $expire
     * @param $bookigLimit
     * @param $dayLimit
     * @param $tariff_id
     * @return bool
     */
    public function setBooking($expire, $bookigLimit, $dayLimit, $tariff_id)
    {
        $expire = intval($expire);

        if ($this->store->setBooking($this->userid, $bookigLimit, $bookigLimit, $expire, $dayLimit, $tariff_id)) {
            $this->init();
            return true;
        }

        return false;
    }

    /**
     * Ключ в редисе для подсчетка кол-ва попыток бронирования
     * @return string
     */
    private function getDayLimitCounterKey()
    {
        return self::BOOKING_DAY_LIMIT_COUNTER_KEY.":".$this->userid;
    }

    /**
     * Наименование ключа, хранящего ИД объектов чьи контакты были открыты
     * @return string
     */
    private function getDayObjsKey()
    {
        return self::getDayLimitCounterKey().":objs";
    }

    /**
     * Увеличиваем счетчик просмотра контактов в текущих сутках
     * @param $obj_id Ид объекта чьи контакты показали
     */
    public function incrDayLimit($obj_id)
    {
        $expire = strtotime('tomorrow') - time();
        //Устанавливаем ключ и TTL до конца суток
        $this->redis->set($this->getDayLimitCounterKey(), 0, ['nx', 'ex' => $expire]);

        //Увеличиваем счетчик
        $incr = $this->redis->incr($this->getDayLimitCounterKey());

        if ($incr <= $this->getDayLimit()) {
            $this->redis->multi()
                ->sAdd($this->getDayObjsKey(), $obj_id)
                ->expire($this->getDayObjsKey(), $expire)
                ->exec();
            return true;
        }

        //Если дошли до сюда,
        //значит не удалось забронировать. Но так как дневной
        //счетчик уже был вызван, то откатываем значение
        $this->redis->decr($this->getDayLimitCounterKey());

        return false;
    }

    /**
     * Былли ли сегодня открыты контакты объекта
     *
     * @param $obj_id ИД объекта
     * @return bool
     */
    public function isLimitedToday($obj_id)
    {
        return $this->redis->sIsMember($this->getDayObjsKey(), $obj_id);
    }

    /**
     * Уменьшить кол-во бронирований
     * @return bool
     */
    public function countReduce()
    {
        /*$expire = strtotime('tomorrow') - time();
        //Устанавливаем ключ и TTL до конца суток
        $this->redis->set($this->getDayLimitCounterKey(), 0, ['nx', 'ex' => $expire]);

        //Увеличиваем счетчик
        $incr = $this->redis->incr($this->getDayLimitCounterKey());

        if ($incr <= $this->getDayLimit()) {*/
            if ($this->store->reduceCount($this->userid)) {
                $this->init();
                return true;
            }
       /* }

        //Если дошли до сюда,
        //значит не удалось забронировать. Но так как дневной
        //счетчик уже был вызван, то откатываем значение
        $this->redis->decr($this->getDayLimitCounterKey());
*/
        return false;
    }

    /**
     * @return bool
     */
    public function countIncrease()
    {
        if ($this->store->countIncrease($this->userid)) {
            $this->init();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function checkDayLimit()
    {
        return $this->dayLimitRemain() > 0;
    }

    /**
     * Оставшееся кол-во суточных бронирований
     * @return int
     */
    public function dayLimitRemain()
    {
        $remain = $this->object['day_limit'] - (int)$this->redis->get($this->getDayLimitCounterKey());
        return $remain < 0 ? 0 : $remain;
    }

    /**
     * @return bool|int
     */
    public function getDayLimitTTL()
    {
        return $this->redis->ttl($this->getDayLimitCounterKey());
    }

    public function getUserId()
    {
        return $this->userid;
    }
}
