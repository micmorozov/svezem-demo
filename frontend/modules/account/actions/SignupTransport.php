<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 29.07.2016
 * Time: 14:55
 *
 * Регистрация и добавление транспорта
 */

namespace frontend\modules\account\actions;

use common\models\LoginSignup;
use common\models\Profile;
use common\models\Transport;
use common\models\User;
use Yii;
use yii\base\Action;
use yii\helpers\Url;
use yii\web\Response;
use yii\widgets\ActiveForm;

class SignupTransport extends Action
{
    // Вьюха для отображения
    public $view = '@account/views/default/signupTransport';

    public function run()
    {
        $transport = new Transport();
        $loginSignup = new LoginSignup();
        $loginSignup->setProfileType(Profile::TYPE_TRANSPORTER_PRIVATE);

        if ($transport->load(Yii::$app->request->post()) && $transport->validate()) {
            $profile = null;

            //Устанавливаем шаблон отправки сообщения регистрации пользователя
            User::$createUserTemplate = ['tpl' => "newTransport", "params" => ['transport' => $transport]];

            //если пользователь авторизован
            if ( !Yii::$app->user->isGuest) {
                //если нет профиля перевозчика,
                //то пытаемся создать
                $profile = Yii::$app->user->identity->createTransporterProfile();
            } //если не авторизован, передаем данные авторизации/регистрации
            elseif ($loginSignup->load(Yii::$app->request->post()) && $loginSignup->validate() && $loginSignup->loginSignup()) {
                $profile = $loginSignup->createProfile([
                    'city_id' => $transport->city_from
                ]);
            }

            //если предыдущими операциями был получен профиль, то создаем транспорт
            if ($profile) {
                $transport->profile_id = $profile->id;
                $transport->save();

                //после создания транспорта необходимо заново получить его
                // чтобы в категории были загружены тип транспорта, способ погрузки
                $transport = Transport::findOne($transport->id);

                // В случае успешного добавления транспорта, перекидываем на страницу с предложением платных услуг
                //$retUrl = Utils::addParamToUrl('/payment/transport', ['item_id' => $transport->id]);

                Yii::$app->session->set('createSubscribe', ['transport_id' => $transport->id]);

                $retUrl = Url::to('/account/set-email');

                //для отображения успеха
                Yii::$app->session->setFlash('Transport', 'created');

                // редирект в личный кабинет после добавления
                return $this->controller->redirect($retUrl);
            }
        }

        if (Yii::$app->request->isAjax) {
            //Если дошли до сюда значит не смогли создать транспорт
            //иначе был бы редирект
            //Но и не первый запрос т.к. ajax
            $transport->load(Yii::$app->request->post());
            $loginSignup->load(Yii::$app->request->post());

            Yii::$app->response->format = Response::FORMAT_JSON;

//            $reCaptcha = false;
//            if( $loginSignup->type == LoginSignup::TYPE_LOGIN  ){
//                $loginSignup->login->needRecaptcha();
//            }

            return array_merge(
                ActiveForm::validate($transport),
                $loginSignup->ajaxValidate(null, false),
                [
                    'reCaptcha' => $loginSignup->login->needRecaptcha()
                ]
            );
        }

        return $this->controller->render($this->view, [
            'model' => $transport,
            'loginSignup' => $loginSignup
        ]);
    }
}