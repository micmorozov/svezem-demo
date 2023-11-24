<?php
/**
 * Уведомляем перевозчиков о повлении нового груза
 *
 * $workload = [
 *  'cargo_id'          - ID груза
 *  'booking_only'      - Отправка тем, у кого есть права на блокировку. После этого отправка всем остальным
 *  'repeat_by'         - Повторить отправку уведомлений всем кроме ИД указанного в этом параметре. Этот юзер отменил бронь груза
 * ]
 */

namespace console\jobs;

use common\components\vk\VkComponent;
use common\helpers\CodeHelper;
use common\helpers\refflog\ReffLogObject;
use common\helpers\TelegramHelper;
use common\helpers\UserHelper;
use common\helpers\Utils;
use common\helpers\UTMHelper;
use common\models\Cargo;
use common\models\Service;
use console\helpers\NotifyHelper;
use console\jobs\jobData\NotifyCarrierData;
use frontend\modules\subscribe\models\Subscribe;
use frontend\modules\subscribe\models\SubscribeLog;
use frontend\modules\subscribe\models\SubscribeRules;
use GearmanJob;
use Redis;
use VK\Client\VKApiClient;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\View;
use yii\db\Exception;
use Longman\TelegramBot\Exception\TelegramException;
use common\components\telegram\Telegram;
use Longman\TelegramBot\Request;
use common\components\bookingService\Service as BookingService;
use yii\db\Expression;
use yii\di\NotInstantiableException;
use yii\helpers\Url;


class NotifyCarrier extends BaseQueueJob
{
    /**
     * Уведомлять только перевозчиков, имеющих доступ к бронированию грузов
     * @var int
     */
    private $bookingOnly = 0;

    /**
     * Повторная отправка уведомления всем юзерам кроме этого юзера
     * @var null|int
     */
    private $repeatBy = 0;

    public function run($job)
    {
        $res = $this->_run($job);
        Yii::getLogger()->flush();

        return $res;
    }

    /**
     * @param BaseQueueJob $job
     * @return mixed|void
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws TelegramException
     * @throws VKClientException
     * @throws \yii\base\Exception
     */
    private function _run($job)
    {
        // Уведомляем только перевозчиков, имеющих доступ к бронированию грузов
        $this->bookingOnly = $job->booking_only??0;
        $this->repeatBy = $job->repeat_by??0;

        /** @var Cargo $cargo */
        $cargo = Cargo::find()
            ->where([Cargo::tableName().'.id' => (int)$job->cargo_id])
            ->one();

        if( !$cargo){
            Yii::error('Не найден груз '.print_r($job, 1), 'NotifyCarrier');
            return;
        }

        if($cargo->created_at < strtotime("-".Cargo::DAYS_ACTUAL." days")){
            Yii::info("Уведомление отменено. Груз ID: {$cargo->id} создан более 2-х дней назад.", 'NotifyCarrier');
            return;
        }

        //категории
        $categories = array_map(function($cat){
            return $cat->id;
        }, $cargo->realCategories);

        $query = SubscribeRules::find()
            ->joinWith('subscribe')
            ->leftJoin('subscribe_rule_category_assn', 'subscribe_rules.id = subscribe_rule_category_assn.subscribe_rule_id')
            ->leftJoin('subscribe_rule_exdir_assn `exdir`', 'subscribe_rules.id = `exdir`.subscribe_rule_id')
            ->where(['or',
                [SubscribeRules::tableName().'.city_from' => null],
                [SubscribeRules::tableName().'.city_from' => $cargo->city_from]
            ])
            ->andWhere(['or',
                [SubscribeRules::tableName().'.region_from' => null],
                [SubscribeRules::tableName().'.region_from' => $cargo->region_from]
            ])
            ->andWhere(['or',
                [SubscribeRules::tableName().'.city_to' => null],
                [SubscribeRules::tableName().'.city_to' => $cargo->city_to]
            ])
            ->andWhere(['or',
                [SubscribeRules::tableName().'.region_to' => null],
                [SubscribeRules::tableName().'.region_to' => $cargo->region_to]
            ])
            ->andWhere(['or',
                ['and',
                    [Subscribe::tableName().'.type' => Subscribe::TYPE_PAID],
                    ['>', 'remain_msg_count', 0]
                ],
                [Subscribe::tableName().'.type' => Subscribe::TYPE_FREE]
            ])
            /////////////////////////
            // Исключения регионов //
            ->andWhere(['or',
                ['exdir.region_from' => null],
                ['NOT', ['exdir.region_from' => $cargo->region_from]]
            ])
            ->andWhere(['or',
                ['exdir.region_to' => null],
                ['NOT', ['exdir.region_to' => $cargo->region_to]]
            ])
            /////////////////////////
            ->andWhere(['status' => SubscribeRules::STATUS_ACTIVE])
            ->groupBy(Subscribe::tableName().".userid")
            ->limit(100); // Больше 100 уже памяти не хватает

        // Если у груза определена категория, то ищем правила с такой же категорией либо со всеми категориями
        // Если категория не определена, ищем правила только со всеми категориями
        // Если у правила выбраны Все категории, то в subscribe_rule_category_assn нет записей
        if( !empty($categories)){
            $query->andWhere(['or',
                ['category_id' => $categories],
                ['category_id' => null]
            ]);
        } else{
            $query->andWhere(['category_id' => null]);
        }

        ///////////////
        // Определяем направление перевозки груза
        // Если перевозка внутри региона
        $tr_type = [SubscribeRules::TRANSPORTATION_TYPE_ALL_CITY];
        if($cargo->region_from == $cargo->region_to) {
            array_push($tr_type, SubscribeRules::TRANSPORTATION_TYPE_INSIDE_REGION);
            // Для перевозок по городу
            if($cargo->city_from == $cargo->city_to)
                array_push($tr_type, SubscribeRules::TRANSPORTATION_TYPE_INSIDE_CITY);
            // Для перевозок между городами в одном регионе
            else
                array_push($tr_type, SubscribeRules::TRANSPORTATION_TYPE_DIFF_CITY);
        // Если перевозка между регионами
        }else{
            array_push($tr_type, SubscribeRules::TRANSPORTATION_TYPE_BETWEEN_REGION);
        }
        $query->andWhere([SubscribeRules::tableName().'.transportation_type' => $tr_type]);
        ////////////////////

        /** @var SubscribeRules[] $rules */
        $cntCarrier = 0;
        while($rules = $query->all()){
            $query->offset += $query->limit;

            shuffle($rules);

            $cntCarrier += $this->send($rules, $cargo);
        }

        // В этот if заходим только для того, чтобы установить ключ в редисе
        if($this->bookingOnly){
            // делаем пометку для груза, на ее основе показываются или нет контакты отправителя раньше периода бронирования
            Yii::$app->redisTemp->set("cargo:{$job->cargo_id}:booking_notified", $cntCarrier, ['ex' => 86400]);

            // Сразу же отправляем всем остальным
            //if(!$cntCarrier){
                // Если при уведомлении с бронированием небыло отправлено сообщений - сразу же рассылаем уведомления остальным подписчикам
                // Для того, что бы не блокировать грузы, которые некому бронировать
                $this->bookingOnly = 0;
                $query->offset = 0;
                while($rules = $query->all()){
                    $query->offset += $query->limit;

                    shuffle($rules);

                    $this->send($rules, $cargo);
                }

            //}
        }

        // Отправляем информацию по грузу в наши публичные группы и мессенджеры
        // Отправка в паблики должна идти после отправки уведомлений по подпискам
        $this->sendCargoToPublic($cargo);
    }

    /**
     * @param $rules SubscribeRules[]
     * @param $cargo
     * @return int Возвращает количество перевозчиков, кому отправлены уведомления
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws TelegramException
     * @throws \yii\base\Exception
     */
    private function send($rules, $cargo) : int
    {
        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        $redisCargoPhoneKey = "NotifyCargoSubscribe:{$cargo->id}";

        // Если это повторная отправка сообщений, надо удалить ключ в редис, что бы уведомления еще раз ушли
        // Но ИД, который, в этом параметре отправлять не надо, так как это он отменил бронь груза и из за него произошла повторная отправка
        if($this->repeatBy){
            $redis->del($redisCargoPhoneKey);
        }

        // Счетчик перевозчиков, которым отправлено уведомление
        $cntCarrier = 0;
        foreach($rules as $rule){
            $successSending = false;

            //Если для данной подписки уже выполнена отправка,
            //то пропускаем
            if($redis->sIsMember($redisCargoPhoneKey, $rule->subscribe_id)){
                Yii::info("Уведомление уже отправлено. Груз ID: {$cargo->id}, subscribe_id: {$rule->subscribe_id} ", 'NotifyCarrier');
                continue;
            }

            // Если уведомления только для бронирования груза, надо проверить права доступа
            $bookinService = new BookingService($rule->subscribe->userid);
            $cargoBooking = $bookinService->canBooking();
            if($this->bookingOnly && !$cargoBooking) {
                continue;
            }

            // Для конкретных пользователей шлем телефон
            $showPhone = in_array($rule->subscribe->userid, []);
            //////////////////

            //запоминаем что уведомление о грузе было отправлено
            //время жизни 2 суток
            $redis->multi(Redis::PIPELINE)
                ->sAdd($redisCargoPhoneKey, $rule->subscribe_id)
                ->expire($redisCargoPhoneKey, 86400*Cargo::DAYS_ACTUAL)
                ->exec();

            // Тому, из за кого произошла повторная отправка уведомление не отправляем
            if($this->repeatBy == $rule->subscribe->userid) {
                continue;
            }

            //SMS
            //если у подписки указан телефон
            if($rule->subscribe->type == Subscribe::TYPE_PAID && $rule->subscribe->phone){

                $smsMsg = NotifyHelper::subscribeSms($cargo, $rule->subscribe->userid, 134 /* 2 СМС */);

                if(Yii::$app->sms->smsSend($rule->subscribe->phone, $smsMsg, 0)){
                    $successSending = true;

                    $log = new SubscribeLog();
                    $log->userid = $rule->subscribe->userid;
                    $log->recipient = $rule->subscribe->phone;
                    $log->type = SubscribeLog::TYPE_PHONE;
                    $log->text = $smsMsg;
                    $log->rule_id = $rule->id;
                    $log->cargo_id = $cargo->id;

                    if( !$log->save()){
                        Yii::error('Не удалось сохранить лог подписки '.print_r($log->attributes, 1), 'NotifyCarrier');
                    }
                }
            }

            //E-Mail или Telegram
            if($rule->subscribe->type == Subscribe::TYPE_FREE){
                if($rule->subscribe->telegram){

                    if($this->sendToTelegram($cargo, $rule->subscribe->telegram, ($showPhone?'cargoWithPhone.php':'cargoPrivate.php'), $bookinService, $rule->subscribe->userid)){
                        $log = new SubscribeLog();
                        $log->userid = $rule->subscribe->userid;
                        $log->recipient = $rule->subscribe->telegram;
                        $log->type = SubscribeLog::TYPE_TELEGRAM;
                        $log->text = $cargo->description;
                        $log->rule_id = $rule->id;
                        $log->cargo_id = $cargo->id;

                        if( !$log->save()){
                            Yii::error('Не удалось сохранить лог подписки '.print_r($log->attributes, 1), 'NotifyCarrier');
                        }
                    }
                }

                if($rule->subscribe->email){

                    $msg = NotifyHelper::subscribeEmail($cargo, $rule, $this->bookingOnly, ($showPhone?'notifyCarrierWithPhone.php':null));

                    Yii::$app->gearman->getDispatcher()->background("sendmail", [
                        'email' => explode(';',$rule->subscribe->email),
                        'subject' => json_decode("\"\xF0\x9F\x9A\x9A\"") . ' Новый груз по вашему направлению',
                        'body' => $msg
                    ]);

                    $log = new SubscribeLog();
                    $log->userid = $rule->subscribe->userid;
                    $log->recipient = $rule->subscribe->email;
                    $log->type = SubscribeLog::TYPE_EMAIL;
                    $log->text = $cargo->description;
                    $log->rule_id = $rule->id;
                    $log->cargo_id = $cargo->id;

                    if( !$log->save()){
                        Yii::error('Не удалось сохранить лог подписки '.print_r($log->attributes, 1), 'NotifyCarrier');
                    }
                }
            }


            //В случае успешной отправки отнимаем кол-во оставшихся сообщений
            if($successSending){
                $this->decrementMsgCount($rule->subscribe);
            }

            $cntCarrier++;
        }

        return $cntCarrier;
    }

    /**
     * @param $subscribe Subscribe
     * @throws \yii\base\Exception
     * @throws Exception
     */
    protected function decrementMsgCount($subscribe){
        //пропускаем бесплатные подписки
        if($subscribe->type == Subscribe::TYPE_FREE) return;

        //Создаем запрос
        /**
         *   BEGIN;
         *   SELECT remain_msg_count-1 FROM `subscribe` WHERE `id` = 123 FOR UPDATE;
         *   UPDATE `subscribe` SET
         *          'free' = 'IF(`free` = `remain_msg_count`, free - 1, free)',
         *          'remain_msg_count' = `remain_msg_count`-1'
         *   WHERE `id` = 123;
         *   COMMIT;
         */

        $subscribe_id = $subscribe->id;

        $transaction = Yii::$app->db->beginTransaction();

        $selectSql = Subscribe::find()
            ->select(new Expression('remain_msg_count-1 as remain_msg_count'))
            ->where(['id' => $subscribe_id])
            ->createCommand()->getRawSql();

        $selectQuery = $selectSql.' FOR UPDATE;';

        $updateQuery = Yii::$app->db->createCommand()
            ->update(Subscribe::tableName(), [
                'free' => new Expression('IF(`free` = `remain_msg_count`, free - 1, free)'),
                'remain_msg_count' => new Expression('`remain_msg_count`-1')
            ],
                ['and',
                    ['id' => $subscribe_id],
                    ['>', 'remain_msg_count', 0]
                ])->getRawSql();

        $result = Yii::$app->db->createCommand($selectQuery.$updateQuery)->bindValues([])->queryOne();

        $transaction->commit();

        if( !isset($result['remain_msg_count'])){
            Yii::error('Не удалось получить кол-во оставшихся сообщений '.print_r($result, 1), 'NotifyCarrier.decrementMsgCount');
        } else{
            if($subscribe->phone){
                if($result['remain_msg_count'] == 5){
                    $price = Service::getPriceByCount(Service::SMS_NOTIFY, 10);

                    $url = NotifyHelper::subscribeEditUrl($subscribe->userid, 'sms', 'decrement_5', true, 86400 * 3);

                    Yii::$app->sms->smsSend($subscribe->phone,
                        "Осталось 5 уведомлений. Для получения еще 10 отправьте СМС10 на номер 3443 или оплатите на сайте {$url} Цена {$price} руб");
                }

                if($result['remain_msg_count'] == 0){
                    $price = Service::getPriceByCount(Service::SMS_NOTIFY, 10);
                    $url = NotifyHelper::subscribeEditUrl($subscribe->userid, 'sms', 'decrement_0', true, 86400 * 3);

                    Yii::$app->sms->smsSend($subscribe->phone, "Уведомления отключены. Для получения 10 уведомлений отправьте СМС10 на номер 3443 или оплатите с сайта {$url} Цена {$price} руб");
                }
            }
        }
    }

    /**
     * Отправляем информацию по грузу во все наши публичные группы
     *
     * @param Cargo $cargo
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws TelegramException
     * @throws VKClientException
     * @throws \yii\base\Exception
     */
    private function sendCargoToPublic($cargo)
    {
        ///////////////////////////////////////////////////
        // Отправка в наши телеграм каналы
        // Отправляем в канал c возможностью получить номер телефона
        // 1045244 - ЛО
        if( in_array($cargo->region_from, [1045244]) || in_array($cargo->region_to, [1045244]) )
            $this->sendToTelegram($cargo, '@svezem_spb_public');

        // 1053480 - МО
        if( in_array($cargo->region_from, [1053480]) || in_array($cargo->region_to, [1053480]) )
            $this->sendToTelegram($cargo, '@svezem_msk_public');

        $this->sendToTelegram($cargo, TelegramHelper::getCommonChatId());
        ///////////////////////////////////////////////////

        // https://t.me/gruzov
        // Только из-в МО кроме авто, наливных, насыпных
        // 1053480 - МО
        // $cargo->cargo_category_id:
        // 6 - автомобильные перевозки
        // 9 - Перевозка сыпучих грузов
        // 17	Перевозка спецтехники
        // 26	Перевозка тракторов
        // 27	Перевозка экскаваторов
        // 28	Перевозка погрузчиков
        // 29	Перевозка комбайнов
        // 30	Перевозка молока
        // 35	Перевозка зерна
        // 56   Перевозка судов
        // 57   Перевозка мототехники
        // 64	Перевозка нефтепродуктов
        // 71	Перевозка самосвалами и тонарами
        // 76	Наливная перевозка
        if( (in_array($cargo->region_from, [1053480]) || in_array($cargo->region_to, [1053480])) &&
            (!in_array($cargo->cargo_category_id, [6,9,17,26,27,28,29,30,35,56,57,64,71,76])))
            $this->sendToTelegram($cargo, '@gruzov', 'gruzov.php');

        // https://t.me/logistikasng
        // Общался с админом https://t.me/logistikasng
        $this->sendToTelegram($cargo, '@logistikasng', 'gruzov.php');



        //---------------------------------------------------------------------//
        // Наш ВК
        $this->sendVkGroup($cargo, Yii::$app->vk);

        // https://vk.com/public92087841
        // Группа грузоперевозки. Публикуем только МО+ЛО
        // 1053480 - МО
        // 1045244 - ЛО
        // 1500001 - Крым
        /* Администратор сообщил, что отключил публикацию объявлений
        if( in_array($cargo->region_from, [1053480, 1045244, 1500001]) || in_array($cargo->region_to, [1053480, 1045244, 1500001]) )
            $this->sendVkGroup($cargo, Yii::$app->vk92087841);*/

        // https://vk.com/trubach707
        // Группа грузоперевозки. Публикуем только Из Красноярска и в Красноярск
        // 73 - Красноярск
        if( in_array($cargo->city_from, [73]) || in_array($cargo->city_to, [73]) )
            $this->sendVkGroup($cargo, Yii::$app->vk100893666);

        $this->sendVkGroup($cargo, Yii::$app->vk68689413);

        // https://vk.com/public131653003
        // Переписка с Екатерина Голубева https://vk.com/id393265382
        // Группа грузоперевозки. Публикуем только ЛО
        // 1045244 - ЛО
        if( in_array($cargo->region_from, [1045244]) || in_array($cargo->region_to, [1045244]))
            $this->sendVkGroup($cargo, Yii::$app->vk131653003);

        // https://vk.com/gazel53
        // Из/в Великий Новгород
        // 35 - великий Новгород
        if( in_array($cargo->city_from, [35]) || in_array($cargo->city_to, [35]))
            $this->sendVkGroup($cargo, Yii::$app->vk28887960);

    }

    /**
     * Отправляем в телеграм каналы
     * @param Cargo$cargo
     * @param int|null $chatId
     * @param null $userid
     * @param bool $showPhone - Показывать телефон или ссылку на страницу заказа в сообщении
     * @return bool
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws TelegramException
     * @throws \yii\base\Exception
     */
    private function sendToTelegram($cargo, $chatId, string $template=null, BookingService $bookingService = null, int $userid = null)
    {
        if(is_null($template)){
            $template='cargo.php';
        }

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;
        $redisCargoPublicKey = "NotifyCargoTgPublic:{$chatId}:{$cargo->id}";

        /** @var Telegram $t */
        $t = Yii::$container->get(Telegram::class);
        Request::initialize($t);

        $successSend = true;
        try {
            //Если для данного груза уже размещали пост в телеграме,  то повторно не отправляем
            if($redis->sIsMember($redisCargoPublicKey, $chatId)){
                Yii::info("Уведомление в телеграм было отправлено ранее. Груз ID: {$cargo->id}, chat_id: {$chatId} ", 'NotifyCarrier.Telegram');
                return true;
            }

            //запоминаем что уведомление о грузе было отправлено
            // Перенес до отправки так как на длинных сообщениях какой-то сбой
            // и инфа о грузе дублируется в телеграм многократно
            $redis->multi(Redis::PIPELINE)
                ->sAdd($redisCargoPublicKey, $chatId)
                ->expire($redisCargoPublicKey, 86400*Cargo::DAYS_ACTUAL)
                ->exec();

            $utmParams=[
                'utm_source'    => 'telegram',
                'utm_medium'    => $chatId,
                'utm_compaign'  => 'notify_carrier'
            ];
            if($userid){
                // Код авторизации пользователя
                $authCode = UserHelper::createAuthorizeCode($userid, 3 * 86400);

                $utmParams['auth_code'] = $authCode;
            }

            //Т.к страница груза находится в статике, переход делаем на главный домен
            $cargoUrl = 'https://' . Yii::getAlias('@domain') . '/cargo/' . $cargo->id . '/';

            $view = new View();
            $tplMsg = json_decode($view->renderFile("@common/components/telegram/templates/{$template}", [
                'cargo' => $cargo,
                'contactUrl' => UTMHelper::genUTMLink($cargoUrl, $utmParams),
                'subscribeUrl' => UTMHelper::genUTMLink('https://svezem.ru/sub/', $utmParams),
                'bookingService' => $bookingService,
                'utmParams' => $utmParams
            ]), true);

            Request::sendMessage(array_merge($tplMsg, [
                'chat_id' => $chatId,
                'parse_mode' => 'html',
                'disable_web_page_preview' => true
            ]));

        }catch (TelegramException $e){
            Yii::error("Не удалось отправить сообщение в telegram канал {$chatId}" . " Ошибка: " . $e->getMessage(), 'NotifyCarrier.Telegram');
            $successSend = false;
        }

        return $successSend;
    }

    /**
     * Размещение в группу ВК
     * @param Cargo $cargo
     * @return bool
     * @throws VKClientException
     * @throws \yii\base\Exception
     */
    private function sendVkGroup(Cargo $cargo, VkComponent $vkComponent, bool $short_url=false){
        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;
        $redisCargoPublicKey = "NotifyCargoVkPublic:{$vkComponent->group_id}:{$cargo->id}";

        //Если для данного груза уже размещали пост в ВК, то повторно не отправляем
        if($redis->exists($redisCargoPublicKey)){
            Yii::info("Уведомление в ВК было отправлено ранее. Груз ID: {$cargo->id}", 'NotifyCarrier.VK');
            return false;
        }

        /*$reffObj = new ReffLogObject();
        $reffObj->source = 'vk';
        $reffObj->source_type = 'group';
        $reff_log = CodeHelper::createReffLogCode($reffObj);*/

        $utmParams=[
            'utm_source'    => 'vk',
            'utm_medium'    => 'group'.$vkComponent->group_id,
            'utm_compaign'  => 'notify_carrier'
        ];

        //Т.к страница груза находится в статике, переход делаем на главный домен
        //$attachments = UTMHelper::genUTMLink('https://'.Yii::getAlias('@domain').'/cargo/'.$cargo->id.'/', $utmParams);
        $attachments = UTMHelper::genUTMLink($cargo->url, $utmParams);
        /*$attachments = Utils::addParamToUrl('https://'.Yii::getAlias('@domain').'/cargo/'.$cargo->id, [
            'reff_log' => $reff_log
        ]);*/

        $vk = new VKApiClient();
        try{
            $message = NotifyHelper::vkPostMessage($cargo, $utmParams, $short_url);

            $vk->wall()->post($vkComponent->access_token, [
                'owner_id' => $vkComponent->group_id,
                'message' => $message
                // 'attachments' => $attachments
            ]);

        }catch (VKApiException $e){
            Yii::error("Не удалось разместить груз на стену ВК {$vkComponent->group_id}. Ошибка: " . $e->getMessage(), 'NotifyCarrier.VK');
            return false;
        }

        //запоминаем что уведомление о грузе было отправлено
        $redis->setex($redisCargoPublicKey, 86400*Cargo::DAYS_ACTUAL, 1);

        return true;
    }
}
