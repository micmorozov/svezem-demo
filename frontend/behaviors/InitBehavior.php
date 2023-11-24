<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 31.03.2017
 * Time: 13:58
 */

namespace frontend\behaviors;

use common\helpers\CodeHelper;
use common\helpers\UserHelper;
use common\models\User;
use frontend\helpers\Load;
use Monolog\Logger;
use Yii;
use yii\base\Application;
use yii\base\Behavior;
use yii\web\Cookie;
use yii\web\View;

class InitBehavior extends Behavior
{
    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'beforeRequest',
        ];
    }

    public function beforeRequest($event)
    {
        $pathInfo = Yii::$app->request->pathInfo;

        $params = Yii::$app->request->queryParams;
        unset($params['refid'], $params['auth_code'], $params['reff_log']);

        //Если хотябы один параметр был удален, то необходимо сделать редирект
        $needRedirect = Yii::$app->request->queryParams != $params;

        if ( !empty($params)) {
            $pathInfo .= "?".http_build_query($params);
        }

        // Обработка параметра refid. Занесение его в куки
        $refid = Yii::$app->request->get('refid', 0);
        if ($refid) {
            Yii::$app->response->cookies->add(new Cookie([
                'name' => 'refid',
                'value' => $refid,
                'expire' => time() + 365*86400 // Ставим куку на год
            ]));
        }

        $auth_code = Yii::$app->request->get('auth_code', 0);
        if ($auth_code) {
            $userid = UserHelper::getUserByAuthCode($auth_code);
            $user = User::findOne($userid);

            if($user) Yii::$app->user->login($user, 3600*24*30);
        }

        //При переходе из письма записываем данные о переходе в лог
       /* $reff_log = Yii::$app->request->get('reff_log', 0);
        if ($reff_log) {
            if ($data = CodeHelper::getReffLogData($reff_log)) {

                /** @var Logger $logger */
                /*$logger = Yii::$container->get(Logger::class);

                $logger->withName('reff-log')
                    ->info(null, array_merge($data, [
                        'url' => Yii::$app->request->hostInfo.'/'.Yii::$app->request->pathInfo,
                        'absoluteUrl' => Yii::$app->request->absoluteUrl,
                        'userAgent' => Yii::$app->request->userAgent,
                        'ip' => Yii::$app->request->userIP
                    ]));
            }
        }*/

        if ($needRedirect) {
            return Yii::$app->response->redirect('/'.rtrim($pathInfo), 301)->send();
        }
    }
}
