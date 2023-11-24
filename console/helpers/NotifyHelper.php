<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 09.08.18
 * Time: 16:33
 */

namespace console\helpers;

use common\helpers\CodeHelper;
use common\helpers\Convertor;
use common\helpers\refflog\ReffLogObject;
use common\helpers\UserHelper;
use common\helpers\Utils;
use common\helpers\UTMHelper;
use common\models\Cargo;
use frontend\modules\cabinet\models\CargoBookingSearch;
use frontend\modules\subscribe\models\SubscribeRules;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use Yii;
use yii\base\Exception;
use yii\base\View;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class NotifyHelper
{
    /**
     * @param Cargo $cargo
     * @param $userid
     * @param int $SMS_LENGTH_MAX общая длина текста
     * @return null|string
     * @throws Exception
     */
    static public function subscribeSms($cargo, $userid, $SMS_LENGTH_MAX = 134 /* 2 СМС */)
    {
        //для смс существует ограничение по длине
        //шаблон сообщения:
        // Город1-Город2 <текст объявления> <тел отправителя>
        //поэтому сначала заполняем обязательным текстом, а затем
        // текст объявления обрезаем

        //$generalText = 'Заказ на перевозку ';
        $generalText = '';

        //если по городу
        if ($cargo->city_from == $cargo->city_to) {
            $generalText .= 'По '.GeographicalNamesInflection::getCase($cargo->cityFrom->title_ru, Cases::DATIVE);
        } else {
            $generalText .= $cargo->cityFrom->title_ru.'-'.$cargo->cityTo->title_ru;
        }

        //Двоеточие после города
        $generalText .= ':';

        // здесь будет <текст объявления>
        $generalText .= ' {{desc}}';

        // Код авторизации пользователя
        $authCode = UserHelper::createAuthorizeCode($userid, 3*86400);
        $utmParams=[
            'auth_code'     => $authCode,

            'utm_source'    => 'sms',
            'utm_medium'    => 'notify',
            'utm_compaign'  => 'notify_carrier',
            'utm_content'   => (int)$userid
        ];
        //Т.к страница груза находится в статике, переход делаем на главный домен
        $contactsUrl = UTMHelper::genUTMLink('https://'.Yii::getAlias('@domain').'/cargo/'.$cargo->id.'/', $utmParams);
        $generalText .= ' ' . Utils::createShortenUrl($contactsUrl);

        //получаем длину текста за вычитом длины шаблона
        // 8 - длина шаблона {{desc}}
        // $subEditTextLength - длина текста ссылки

        /* Убираем из СМС ссылку на настройку уведомлений, что бы удлиннить полезный текст
        $subEditText = '';
        if ($subEditUrl = self::subscribeEditUrl($userid, 'sms', 'notify', true, 86400 * 3)) {
            $subEditText .= "\nНастройки: ".$subEditUrl;
        }
        $subEditTextLength = mb_strlen($subEditText);*/

        $strlen = mb_strlen($generalText) - 8;// + $subEditTextLength;

        $description = $cargo->description;
        //при необходимости обрезаем текст
        if ($strlen < $SMS_LENGTH_MAX) {
            //отнимаем 3 символа чтобы заменить троеточием
            $description = StringHelper::truncate($description, $SMS_LENGTH_MAX - $strlen - 3);
        }

        $smsText = preg_replace("/{{desc}}/", $description, $generalText);

        //$smsText .= $subEditText;

        return $smsText;
    }

    /**
     * Получение короткого УРЛ для редактирования подписки
     * @param $userid
     * @param $source
     * @param $source_type
     * @param bool $short
     * @param float|int $ttl
     * @return bool
     * @throws Exception
     */
    static public function subscribeEditUrl($userid, $source, $source_type, $short = false, $ttl = 86400 * 3)
    {
        $url = 'https://'.Yii::getAlias('@domain').'/sub/';

        $auth_code = UserHelper::createAuthorizeCode($userid, $ttl);
        $utmParams=[
            'auth_code'     => $auth_code,

            'utm_source'    => $source,
            'utm_medium'    => $source_type,
            'utm_compaign'  => 'notify_carrier',
            'utm_content'   => (int)$userid
        ];

        //Т.к страница груза находится в статике, переход делаем на главный домен
        $url = UTMHelper::genUTMLink($url, $utmParams);

        /*$url = Utils::addParamToUrl($url, [
            'auth_code' => $auth_code,
            'reff_log' => $reff_log
        ]);*/

        if ($short) {
            return Utils::createShortenUrl($url);
        } else {
            return $url;
        }
    }

    /**
     * Создаем урл для страницы бронирования груза с авторизацией пользователя
     * @param SubscribeRules $rule
     * @param bool $short - Укорачивать ли урл
     * @param float|int $ttl - Время жизни ключа авторизации
     * @return bool|string
     * @throws Exception
     */
    static public function createBookingUrlAuth(SubscribeRules $rule, $short = false, $ttl = 86400*3)
    {
        $code = UserHelper::createAuthorizeCode($rule->subscribe->userid, $ttl);

        $url = 'https://'.Yii::getAlias('@domain') . Url::toRoute(['/cabinet/cargo-booking/index',
            'locationFrom' => $rule->cityFrom,
            'locationTo' => $rule->cityTo,
            'cargoCategoryIds' => $rule->categoriesId,
            'auth_code' => $code
        ]);

        if ($short) {
            return Utils::createShortenUrl($url);
        } else {
            return $url;
        }
    }

    /**
     * @param Cargo $cargo
     * @param SubscribeRules $rule
     * @param bool $isBooking ссылка на страницу груза должна вести на страницу бронирования грузов
     * @return string
     * @throws Exception
     */
    static public function subscribeEmail(Cargo $cargo, SubscribeRules $rule, $isBooking = false, string $template=null)
    {
        if(is_null($template)){
            $template = 'notifyCarrier';
        }

        $view = new View();

        $userid = $rule->subscribe->userid;
        $subscribeEditUrl = self::subscribeEditUrl($userid, 'email', 'notify', false);

        // Страница бронирования груза. Если получатель наш партнер, то отправляем ему ссылку на страницу бронирование грузов
        $bookingUrl = $isBooking ? self::createBookingUrlAuth($rule, true) : '';


        // Код авторизации пользователя
        $authCode = UserHelper::createAuthorizeCode($userid, 3 * 86400);

        $utmParams = [
            'auth_code' => $authCode,

            'utm_source' => 'email',
            'utm_medium' => 'notify',
            'utm_compaign' => 'notify_carrier',
            'utm_content' => (int)$userid
        ];
        //Т.к страница груза находится в статике, переход делаем на главный домен
        $contactsUrl = UTMHelper::genUTMLink('https://' . Yii::getAlias('@domain') . '/cargo/' . $cargo->id . '/', $utmParams);

        $cargoBookingPay = null;
        if( !$bookingUrl ){
            $cargoBookingPay = UTMHelper::genUTMLink('https://'.Yii::getAlias('@domain').'/cargo/booking/', $utmParams);

           /* $cargoBookingPay = Utils::addParamToUrl('https://'.Yii::getAlias('@domain').'/cargo/booking/', [
                'auth_code' => $authCode,
                'reff_log' => $reff_log
            ]);*/
        }

        return $view->render("@console/mail/{$template}", [
            'cargo' => $cargo,
            'contactsUrl' => $contactsUrl,
            'bookingUrl' => $bookingUrl,
            'subscribeEditUrl' => $subscribeEditUrl,
            'cargoBookingPay' => $cargoBookingPay
        ]);
    }

    /**
     * Создание ссылки для оплаты услуг транспортной компании
     *
     * @param int $userid
     * @param int $transport_id
     * @param array $services
     * @param bool $short
     * @return string
     * @throws Exception
     */
    static public function transportPaymentUrl($userid, $transport_id, $services, $short = false):string
    {
        $code = UserHelper::createAuthorizeCode($userid, 86400*3);

        $url = 'https://'.Yii::getAlias('@domain').'/payment/transport/?'.http_build_query([
                'auth_code' => $code,
                'service_id' => $services,
                'item_id' => $transport_id
            ]);

        if ($short) {
            return Utils::createShortenUrl($url);
        } else {
            return $url;
        }

    }

    /**
     * Сообщение на стену группы ВК
     *
     * @param Cargo $cargo
     * @return string
     * @throws \Exception
     */
    public static function vkPostMessage(Cargo $cargo, $utmParams, bool $short = false){
        //$text = "Заказ от ".Yii::$app->formatter->asDate($cargo->created_at, 'dd.MM.y')."\n";
        $text = '';

        //если по городу
        if ($cargo->city_from == $cargo->city_to) {
            $text .= /*'По городу: ' . */$cargo->cityFrom->title_ru . ', ' . $cargo->regionFrom->title_ru;
        } else {
            $text .= /*'Направление: ' . */$cargo->cityFrom->title_ru . ' - ' . $cargo->cityTo->title_ru;
            if($cargo->distance){
                $text .= ', ' . Convertor::distance($cargo->distance) . ', ' . Convertor::time($cargo->duration);
            }
        }

        $text .= "\n\n".$cargo->description;

        $url = UTMHelper::genUTMLink($cargo->url, $utmParams);
        if($short) $url = file_get_contents("https://clck.ru/--?url=".urlencode($url));

        $text .= "\n\nКонтакты заказчика: {$url}";

        //$text .= "\n\n"."P.S. Поделись заказом с другом!";

        // Хэш теги
        /*$category = isset($cargo->cargoCategory)
            ? ('#'.mb_ereg_replace('\s','', mb_strtolower($cargo->cargoCategory->category)))
            : '';
        $text .= "\n\nТэги: #перевозка #грузоперевозкипороссии #доставкагрузов #перевозки #грузоперевозки #груз {$category}";*/

        return $text;
    }
}
