<?php
/**
 * Команда для отправки рассылки, находящейся в очереди
 *
 */

namespace console\controllers;

use common\helpers\UserHelper;
use common\models\Mailing;
use common\models\User;
use Yii;

class SendMailsFromQueueController extends BaseController
{
    protected $actionTTL = 1800;

    public function actionIndex()
    {

        //получаем одну строку из очереди
        $mailing = Mailing::findOne(['finish_at' => null]);
        if (!$mailing) {
            return 0;
        }

        //отправляем следующей партии пользователей
        $lastuserid = (int)$mailing->lastuserid;

        $query = User::find()
            ->where(['and',
                ['>', 'id', $lastuserid],
                ['<>', 'email', ''],
                ['news' => true]
            ]);

        //определяем правило по цели отправки
        switch ($mailing->target) {
            case Mailing::TARGET_ALL:
                break;
        }

        $query->orderBy(['id' => SORT_ASC]);
        $query->limit(10);

        $users = $query->all();

        //если нет пользователей для отправки писем
        if (!$users) {
            $mailing->finish_at = time();
            $mailing->save(false, ['finish_at']);
            return 0;
        }

        //отправляем письма выбранным пользователям
        foreach ($users as $user) {
            Yii::$app->gearman->getDispatcher()->background("sendmail", [
                'email' => $user->email,
                'subject' => 'Новости сервиса',
                'view' => '@console/mail/News',
                'params' => [
                    'mailingid' => $mailing->id,
                    'body' => $mailing->body,
                    'userid' => $user->id
                 ]
            ]);


            $lastuserid = $user->id;
        }

        $mailing->lastuserid = $lastuserid;
        $mailing->save(false, ['lastuserid']);

        return 0;
    }
}

