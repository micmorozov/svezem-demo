<?php

/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\bookingService\Service as BookingService;
use common\helpers\UserHelper;
use common\helpers\UTMHelper;
use common\models\Cargo;
use common\models\User;
use frontend\modules\cabinet\components\Cabinet;
use frontend\modules\subscribe\models\Subscribe;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;
use yii\helpers\Html;
use yii\web\View;


/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    protected $show_in_help = false;

    /**
     * Command execute method
     *
     * @return ServerResponse
     */
    public function execute()
    {
        $callback_query    = $this->getCallbackQuery();
        $message = $callback_query->getMessage();
        $chatid = $message->getChat()->getId();
        $tlgUserid = $callback_query->getFrom()->getId();

        //////////////////////
        $emojiSuccess = json_decode("\"\xE2\x9C\x85\"");
        $emojiError = json_decode("\"\xE2\x9D\x8C\"");
        $emojiPhone = json_decode('"'."\xE2\x98\x8E".'"');
        $emojiTruck = json_decode('"'."\xF0\x9F\x9A\x9A".'"');
        $emWarrning = json_decode('"' . "\xE2\x9A\xA0" . '"');
        //////////////////////

        $params = json_decode($callback_query->getData(), true);

        $cmd = $params['cmd']??'';

        $data['text'] = '';
        switch ($cmd){
            // Бронируем груз и высылаем контакты в телеграм
            case 'CargoBooking':
                $cargoid = intval($params['cargoid']??0);
                $userid = intval($params['userid']??0);

                $view = new View();
                $template = 'cargoPrivate.php';

                $utmParams = [
                    'utm_source'    => 'telegram',
                    'utm_medium'    => $chatid,
                    'utm_compaign'  => 'notify_carrier',

                    'auth_code' => UserHelper::createAuthorizeCode($userid, 3 * 86400)
                ];

                $cargo = Cargo::findOne($cargoid);

                $bookingService = new BookingService($userid);
                $canBooking = $bookingService->canBooking();
                // Если не оплачен доступ или
                // Если не осталось просмотров на сегодня
                if( !$canBooking || !$bookingService->dayLimitRemain()){
                    //Т.к страница груза находится в статике, переход делаем на главный домен
                    $cargoUrl = 'https://' . Yii::getAlias('@domain') . '/cargo/' . $cargo->id . '/';
                    $tplMsg = json_decode($view->renderFile("@common/components/telegram/templates/{$template}", [
                        'cargo' => $cargo,
                        'contactUrl' => UTMHelper::genUTMLink($cargoUrl, $utmParams),
                        'bookingService' => $bookingService,
                        'utmParams' => $utmParams
                    ]), true);

                    Request::editMessageText(array_merge($tplMsg, [
                        'chat_id' => $chatid,
                        'message_id' => $message->getMessageId(),
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]));
                    break;
                }

                // Если есть платный доступ, и небыло открытй контактов указанного груза
                // надо показать кнопку, что бы списать просмотры контактов
                if($canBooking && !$bookingService->isLimitedToday($cargo->id)) {
                    if(!$bookingService->incrDayLimit($cargo->id)) {
                        $data['text'] = $emojiError . ' Произошла ошибка при открытии контакта';
                        break;
                    }
                }

                Request::editMessageText([
                    'chat_id' => $chatid,
                    'text' => $message->getText() . "\n\n" . $emojiPhone . ' +' . $cargo->createdBy->phone,
                    'message_id' => $message->getMessageId(),
                    'parse_mode' => 'html'
                ]);
                break;

            // Получаем контакты перевозчика
            case 'CargoGetJob':
                $cargoid = intval($params['cargoid']??0);

                //$result = Cabinet::booking($cargoid, 1);
                //print_r($result);

                /*$upd = Cargo::updateAll(['status' => Cargo::STATUS_WORKING], [
                    'id' => $cargoid,
                    'status' => Cargo::STATUS_ACTIVE
                ]);
                if($upd != 1){
                    $data['text'] = $emojiError . ' Заявку уже взяли в работу';
                    Request::editMessageText([
                        'chat_id' => $chatid,
                        'text' => $message->getText() . "\n\n" . $emojiTruck . ' Заявка уже выполняется...',
                        'message_id' => $message->getMessageId()
                    ]);
                    break;
                }*/

                $cargo = Cargo::findOne($cargoid);
                if($cargo->status != Cargo::STATUS_ACTIVE){
                    $data['text'] = $emojiError . ' Заявку уже взяли в работу';
                    Request::editMessageText([
                        'chat_id' => $chatid,
                        'text' => $message->getText() . "\n\n" . $emojiTruck . ' Заявка уже выполняется...',
                        'message_id' => $message->getMessageId()
                    ]);
                    break;
                }

                $keyboard = new InlineKeyboard([
                    [
                        'text' => $emojiSuccess . ' Подтвердить выполнение',
                        'callback_data' => http_build_query([
                            'cmd' => 'CargoJobDone',
                            'cargoid' => $cargoid
                        ])
                    ]
                ],
                [
                    [
                        'text' => $emojiError . ' Отказаться от выполнения',
                        'callback_data' => http_build_query([
                            'cmd' => 'CargoJobRefuse',
                            'cargoid' => $cargoid
                        ])
                    ]
                ]);
                $keyboard->setResizeKeyboard(true);
                $res = Request::sendMessage([
                    'chat_id' => $tlgUserid,
                    'text' => $message->getText() . "\n\n" .
                        $emojiPhone . ' +'.$cargo->createdBy->phone . "\n\n".
                        '<i>Позвоните заказчику, обсудите условия и выполните заказ, после этого подтвердите выполнение</i>',
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => $keyboard
                ]);
                if(!$res->isOk()){
                    $data['text'] = $emojiError . ' Произошла ошибка';
                    Yii::error($res->getErrorCode() . ':' . $res->getDescription(), 'Telegram.Callbackquery');
                }else {
                    Yii::warning("[$tlgUserid] Телефон заказчика $cargoid отправлен в личном сообщении", 'Telegram.Callbackquery');

                    $data['text'] = $emojiSuccess . ' Телефон заказчика отправлен в личном сообщении';

                    Request::editMessageText([
                        'chat_id' => $chatid,
                        'text' => $message->getText() . "\n\n" . $emojiTruck . ' Заявка выполняется...',
                        'message_id' => $message->getMessageId()
                    ]);
                }

                break;

            case 'CargoJobRefuse':
                $cargoid = intval($params['cargoid']??0);
                Yii::warning("[$tlgUserid] Вы отказались от выполнения заявки $cargoid", 'Telegram.Callbackquery');

                /*Cargo::updateAll(['status' => Cargo::STATUS_ACTIVE], [
                    'id' => $cargoid,
                    'status' => Cargo::STATUS_WORKING
                ]);*/

                Request::editMessageText([
                    'chat_id' => $chatid,
                    'text' => $message->getText() . "\n\n" . $emojiError . ' Вы отказались от выполнения заявки',
                    'message_id' => $message->getMessageId()
                ]);

                $data['text'] = $emojiError . ' Вы отказались от выполнения заявки';

                break;

            case 'CargoJobDone':
                $cargoid = intval($params['cargoid']??0);
                Yii::warning("[$cargoid] Заявка выполнена", 'Telegram.Callbackquery');

                /*Cargo::updateAll(['status' => Cargo::STATUS_DONE], [
                    'id' => $cargoid,
                    'status' => Cargo::STATUS_WORKING
                ]);*/

                Request::editMessageText([
                    'chat_id' => $chatid,
                    'text' => $message->getText() . "\n\n" . $emojiSuccess . ' Вы выполнили заявку',
                    'message_id' => $message->getMessageId()
                ]);

                $data['text'] = $emojiSuccess . ' Вы выполнили заявку';

                break;
        }

        return $callback_query->answer(array_merge([
            'show_alert'        => false,
            'cache_time'        => 5
        ], $data));

    }
}
