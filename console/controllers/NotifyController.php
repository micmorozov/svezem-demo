<?php

namespace console\controllers;

use common\helpers\Utils;
use common\models\Cargo;
use common\models\Service;
use common\models\Setting;
use common\models\Transport;
use console\helpers\NotifyHelper;
use console\jobs\jobData\NotifyCarrierData;
use Redis;
use Yii;
use yii\console\Controller;
use yii\web\View;

class NotifyController extends Controller
{
    /**
     * Отправка уведомлений о приближении завершения периода оплаты
     * за услуги продвижения транспорта
     * @param int $daysBeforeEnd - количество дней до завершения оплаченного периода
     */
    public function actionTransportPaymentService($daysBeforeEnd = 2){
        $now = time();
        $twoDayLater = $now + 86400*$daysBeforeEnd;

        $notifySendedKey = 'TransportNotifySended:';

        $query = Transport::find()
            ->with('profile')
            ->where(['or',
                ['between', 'top', $now, $twoDayLater],
                ['between', 'show_main_page', $now, $twoDayLater],
                ['between', 'colored', $now, $twoDayLater],
                ['between', 'recommendation', $now, $twoDayLater]
            ])
            ->orderBy(['id' => SORT_ASC])
            ->limit(500);

        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;

        while($transports = $query->all()){
            $query->offset += $query->limit;

            $services = [];
            foreach($transports as $transport){
                //Определяем какие из услуг подходят к концу
                if(Utils::between($transport->top, $now, $twoDayLater)){
                    $services[] = Service::SEARCH;
                }
                if(Utils::between($transport->show_main_page, $now, $twoDayLater)){
                    $services[] = Service::MAIN_PAGE;
                }
                if(Utils::between($transport->colored, $now, $twoDayLater)){
                    $services[] = Service::COLORED;
                }
                if(Utils::between($transport->recommendation, $now, $twoDayLater)){
                    $services[] = Service::RECOMMENDATIONS;
                }

                //Услуги, о которых необходимо известить
                $notifyServices = $services;
                if($sendedServices = $redis->get($notifySendedKey.$transport->id)){
                    $sendedServices = json_decode($sendedServices, true);

                    $notifyServices = array_diff($services, $sendedServices);

                    //Если новых услуг нет, значит уведомление уже было отправлено
                    if( empty($notifyServices) ){
                        continue;
                    }
                }

                //Записываем ИД услуг, по которым будет отправлено уведомление
                $redis->setex($notifySendedKey.$transport->id, 86400*$daysBeforeEnd, json_encode($services) );

                $url = NotifyHelper::transportPaymentUrl($transport->created_by, $transport->id, $notifyServices);

                //Если указан номер
                if($transport->profile->contact_phone){
                    $view = new View();
                    $smsMsg = $view->render("@console/sms/NotifyTransportPaymentService", [
                        'daysBeforeEnd' => $daysBeforeEnd,
                        'transport' => $transport,
                        'url' => Utils::createShortenUrl($url)
                    ]);

                    Yii::$app->sms->smsSend($transport->profile->contact_phone, $smsMsg);
                }

                //Если указан E-Mail
                if($transport->profile->contact_email){
                    $servicesName = $this->getServicesNameById($notifyServices);

                    $mailer = Yii::$app->mailer->compose('@console/mail/NotifyTransportPaymentService', [
                            'daysBeforeEnd' => $daysBeforeEnd,
                            'transport' => $transport,
                            'servicesName' => $servicesName,
                            'url' => $url
                        ])
                        ->setFrom([Yii::$app->params['supportEmail'] => 'Svezem.ru'])
                        ->setTo($transport->profile->contact_email)
                        ->setSubject('Svezem.ru: Период оплаты подходит к концу');

                    $mailer->send();
                }
            }
        }
    }

    /**
     * Отправка уведомлений о завершения периода оплаты
     * за услуги продвижения транспорта
     */
    public function actionTransportPaymentServiceExpired(){
        $now = time();
        $oneDayBefore = $now - 86400;

        $query = Transport::find()
            ->with('profile')
            ->where(['or',
                ['between', 'top', $oneDayBefore, $now],
                ['between', 'show_main_page', $oneDayBefore, $now],
                ['between', 'colored', $oneDayBefore, $now],
                ['between', 'recommendation', $oneDayBefore, $now]
            ])
            ->orderBy(['id' => SORT_ASC])
            ->limit(500);

        while($transports = $query->all()){
            $query->offset += $query->limit;

            $services = [];
            foreach($transports as $transport){
                //Определяем какие из услуг истекли
                if(Utils::between($transport->top, $oneDayBefore, $now)){
                    $services[] = Service::SEARCH;
                }
                if(Utils::between($transport->show_main_page, $oneDayBefore, $now)){
                    $services[] = Service::MAIN_PAGE;
                }
                if(Utils::between($transport->colored, $oneDayBefore, $now)){
                    $services[] = Service::COLORED;
                }
                if(Utils::between($transport->recommendation, $oneDayBefore, $now)){
                    $services[] = Service::RECOMMENDATIONS;
                }

                $url = NotifyHelper::transportPaymentUrl($transport->created_by, $transport->id, $services);

                //Если указан номер
                if($transport->profile->contact_phone){
                    $view = new View();
                    $smsMsg = $view->render("@console/sms/NotifyTransportPaymentServiceExpired", [
                        'url' => Utils::createShortenUrl($url)
                    ]);

                    Yii::$app->sms->smsSend($transport->profile->contact_phone, $smsMsg);
                }

                //Если указан E-Mail
                if($transport->profile->contact_email){
                    $servicesName = $this->getServicesNameById($services);

                    $mailer = Yii::$app->mailer->compose('@console/mail/NotifyTransportPaymentServiceExpired', [
                            'transport' => $transport,
                            'servicesName' => $servicesName,
                            'url' => $url
                        ])
                        ->setFrom([Yii::$app->params['supportEmail'] => 'Svezem.ru'])
                        ->setTo($transport->profile->contact_email)
                        ->setSubject('Svezem.ru: Период оплаты завершен!');

                    $mailer->send();
                }
            }
        }
    }

    protected function getServicesNameById($serviceIds){
        $services = Service::find()->all();

        static $names = [];

        foreach($services as $service){
            $names[$service->id] = $service->name;
        }

        return array_map(function($id) use ($names){
            return $names[$id];
        }, $serviceIds);
    }

    /**
     * Рассылка уведомлений о новых грузах
     */
    public function actionCarrier(){
        // уведомления отправляются о грузах для которых истек интервал для бронирования
        $blockMinutes = Setting::getValueByCode(Setting::CARGO_BOOKING_BLOCK, 30);
        $blockMinutesStart = $blockMinutes+480; // отправляем уведомления о грузах за последние 8 часов

        $startTime = strtotime("-$blockMinutesStart min");
        $finishTime = strtotime("-$blockMinutes min");

        $query = Cargo::find()
            ->where(['between', 'created_at', $startTime, $finishTime])
            ->andWhere(['status' => Cargo::STATUS_ACTIVE])
            ->limit(500);

        $jobNotify = new NotifyCarrierData();

        /** @var Cargo[] $cargos */
        while($cargos = $query->all()){
            $query->offset += $query->limit;

            foreach($cargos as $cargo){
                $jobNotify->cargo_id = $cargo->id;

                Yii::$app->gearman->getDispatcher()->background($jobNotify->getJobName(), $jobNotify);
            }
        }
    }
}
