<?php

namespace console\jobs;

use Exception;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use Yii;

class SendMail extends JobBase
{
    public function execute(GearmanJob $job = null){
        $workload = $this->getWorkload($job);
        if( !$workload) return;

        $view = $workload['view'] ?? null;
        $body = $workload['body'] ?? null;
        $params = $workload['params'] ?? [];
        $email = $workload['email'] ?? null;
        $subject = $workload['subject'] ?? '';

        if( !$email ){
            Yii::error("Необходимо передать адрес отправки\n".print_r($workload, 1), 'SendMail');
            return false;
        }

        if( !$view && !$body ){
            Yii::error("Необходимо передать view или body\n".print_r($workload, 1), 'SendMail');
            return false;
        }

        $mailer = Yii::$app->mailer->compose($view, $params)
           // ->addHeader('list-unsubscribe', '<https://svezem.ru>')
            ->setFrom([Yii::$app->params['supportEmail'] => 'Svezem.ru'])
            ->setTo($email)
            ->setSubject($subject);

        if( $body ){
            $mailer->setHtmlBody($body);
        }

        try {
            if (!$mailer->send()) {
                Yii::error("Не удалось отправить письмо\n" . print_r($workload, 1), 'SendMail');
            }
        }catch(Exception $e){
            Yii::error("Не удалось отправить письмо\n" . $e->getMessage(), 'SendMail');
        }

    }
}