<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 28.11.16
 * Time: 17:38
 */

namespace common\helpers;

use common\models\GeoNetwork;
use common\models\Profile;
use common\models\User;
use Redis;
use Yii;
use yii\base\Exception;

class UserHelper
{

    const AUTH_USER = 'authorizeByUser';

    /**
     * Наименование ключа для хранения прочих данных
     * @var string
     */
    private static $redisAnyKey = 'anyData';

    static public function getGeoLocation($ip = null){
        $ip = sprintf('%u', ip2long($ip));

        $geo = GeoNetwork::find()->where("{$ip} BETWEEN `beginip` AND `endip`")
            ->orderBy("length")
            ->limit(1)
            ->one();

        $country = false;
        $region = false;
        $city = false;

        if($geo){
            $country = $geo->country ? ['id' => $geo->country->id, 'name' => $geo->country->title_ru, 'code' => $geo->country->code] : false;
            $region = $geo->region ? ['id' => $geo->region->id, 'name' => $geo->region->title_ru] : false;
            $city = $geo->city ? ['id' => $geo->city->id, 'name' => $geo->city->title_ru] : false;
        }

        return [
            'country' => $country,
            'region' => $region,
            'city' => $city
        ];
    }

    /**
     * Возвращает профайл по номеру телефона, либо создает новый
     * @param $phone
     * @param array $opt
     * @return array
     */
    static public function createProfile($phone, array $opt = []){
        $opt['type'] = isset($opt['type']) ? $opt['type'] : Profile::TYPE_SENDER;

        $result = [
            'error' => false,
            'profile' => null,
            'isNewUser' => false,
            'user' => null
        ];

        //Ищем профиль по телефону
        $profile = Profile::findOne([
            'contact_phone' => $phone,
            'type' => $opt['type']
        ]);

        if( !$profile){
            //Ищем пользователя по телефону
            $user = User::findOne(['phone' => $phone]);

            if( !$user){
                //создаем нового пользователя
                $user = new User();
                $user->phone = $phone;

                if(isset($opt['userCreatedBy']))
                    $user->created_by = $opt['userCreatedBy'];

                if( !$user->save()){
                    Yii::error("Ошибка создания пользователя ".print_r($user->getErrors(), 1), 'application.UserHelper.createProfile');
                    $result['error'] = true;
                    return $result;
                }

                $result['isNewUser'] = true;
                $result['user'] = $user;
            }

            //есть пользователь, ищем профайл
            $profile = Profile::findOne([
                'created_by' => $user->id,
                'type' => $opt['type']
            ]);

            //не найден профайл пользователя
            if( !$profile){
                $profile = new Profile();
                $profile->name = 'Грузовладелец';
                $profile->contact_person = 'Грузовладелец';

                //поле created_by устанавливается при помощи Behavior
                //чтобы задать его, не авторизуя пользователя,
                //задаем значение ИД найденного/созданного пользователя
                $Behavior = $profile->getBehavior('BlameableBehavior');
                if($Behavior)
                    $Behavior->value = $user->id;

                $profile->city_id = $opt['city_id'];
                $profile->type = $opt['type'];
                $profile->contact_phone = $user->phone;
                $profile->phone_country = $user->phone_country;

                if( !$profile->save()){
                    Yii::error("Ошибка создания профайла ".print_r($profile->getErrors(), 1), 'application.UserHelper.createProfile');
                    $result['error'] = true;
                    return $result;
                }
            }
        }

        $result['profile'] = $profile;
        return $result;
    }

    /**
     * Создание ключа авторизации под другим пользователем
     *
     * @param $userid
     * @param int $ttl
     * @param bool $deleteAfter
     * @return string
     * @throws Exception
     */
    static public function createAuthorizeCode($userid, $ttl = 10, $deleteAfter = false){
        $security = Yii::$app->getSecurity();
        $code = $security->generateRandomString();

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        $key = self::AUTH_USER.':'.$code;

        $redis->hMSet($key, [
            'userid' => $userid,
            'deleteAfter' => (int)$deleteAfter
        ]);

        $redis->expire($key, $ttl);
        return $code;
    }

    /**
     * Возвращает ИД пользователя по ключу авторизации
     * @param $code
     * @return mixed
     */
    static public function getUserByAuthCode($code){
        $key = self::AUTH_USER.':'.$code;

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        if( !$res = $redis->hGetAll($key))
            return null;

        // Удалить ключ при необходимости
        $deleteAfter = $res['deleteAfter']??false;
        if($deleteAfter){
            $redis->del($key);
        }

        return $res['userid'];
    }

    public static function createAuthorizeUrl($userid, $url = '/', $short = false, $ttl = 86400){
        $code = self::createAuthorizeCode($userid, $ttl);

        $url = 'https://'.Yii::getAlias('@domain').$url;
        $url = Utils::addParamToUrl($url, ['auth_code' => $code]);

        if($short)
            return Utils::createShortenUrl($url);
        else
            return $url;
    }

    /**
     * Создаем ключ для прочих целей
     * @param $length - Длинна ключа
     * @param $chars - Набор символов из которых строить ключ
     * @param $data - Массив с данными, которые надо поместить в ключ
     * @param int $ttl - Время жизни в секундах
     */
    public static function createAnyCode($length, $chars, $data, $ttl=900)
    {
        $code = StringHelper::str_rand($length, $chars);

        $redis = Yii::$app->redisTemp;

        $key = self::$redisAnyKey.':'.$code;

        $redis->set($key, json_encode($data), ['ex' => $ttl]);

        return $code;
    }

    /**
     * Возвращаем массив с данными по переданному коду. false - если ключа не существует
     * @param $code
     * @return mixed|null;
     */
    public static function getDataByAnyCode($code)
    {
        $key = self::$redisAnyKey.':'.$code;

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        if( !$data = $redis->get($key))
            return null;

        return json_decode($data, true);
    }
}