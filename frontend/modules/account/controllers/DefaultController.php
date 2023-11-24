<?php

namespace frontend\modules\account\controllers;

use common\helpers\Utils;
use common\models\LoginForm;
use common\models\User;
use common\validators\UserLoginValidator;
use frontend\modules\account\actions\SignupTransport;
use frontend\modules\account\models\EmailForm;
use frontend\modules\account\models\PasswordResetRequestForm;
use frontend\modules\account\models\ResetPasswordForm;
use frontend\modules\account\models\SignupForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Account controller
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['signup', 'login', 'request-password-reset'],
                        'allow' => true,
                        'roles' => ['?']
                    ],
                    [
                        'actions' => ['logout', 'set-email'],
                        'allow' => true,
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['signup-transport', 'signup-cargo', 'reset-password']
                    ]
                ],
                //если нет доступа, то вернуть на главную
                'denyCallback' => function (){
                    $this->goHome();
                }
            ],
            'common\behaviors\NoSubdomain'
        ];
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null
            ],
            'signup-transport' => [
                'class' => SignupTransport::class,
                'view' => '@account/views/default/signupTransport.php'
            ]
        ];
    }


    /*public function behaviors(){
        return [
			'common\behaviors\NoSubdomain'
        ];
    }*/

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        /**
         * Выходим из под пользователя, но не очищаем сессию.
         * В ней хранятся данные по выбранному городу
         */
        Yii::$app->user->logout(false);
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    $model->createProfiles($user);

                    // В случае успешного добавления груза добавляем параметр new_user к урлу, что бы метрика поситала это за достижения цели
                    //$retUrl = Utils::addParamToUrl('/cabinet/settings', ['new_user' => 1]);

                    $retUrl = Url::toRoute('/sub/');

                    return $this->redirect($retUrl);
                }
            }
        }
        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionConfirmEmail($token)
    {
        /** @var User $user */
        $user = User::findIdentityByAuthKey($token);
        if ((null === $user)) {
            throw new NotFoundHttpException("Страница не найдена. Проверьте правильность ссылки");
            // @todo maybe hackers here. This mechanism needs to be impoved
        }
        if (($user->status != User::STATUS_PENDING)) {
            Yii::$app->session->setFlash('info', 'Вы уже подтвердили почту ранее, больше подтверждение не требуется');
        } else {
            $user->updateAttributes([
                'status' => User::STATUS_ACTIVE,
                'verification_date' => time()
            ]);
            Yii::$app->user->login($user, 3600*24*30);
            Yii::$app->session->setFlash('success', 'Ваша почта подтверждена');
        }
        return $this->redirect('/cabinet/settings');
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->recoverPassword();

            if ($model->loginType == UserLoginValidator::LOGIN_TYPE_EMAIL) {
                Yii::$app->session->setFlash('success',
                    'На указанный email выслана инструкция для восстановления пароля');
            } elseif ($model->loginType == UserLoginValidator::LOGIN_TYPE_PHONE) {
                Yii::$app->session->setFlash('success', 'На указанный телефон выслан пароль для доступа на сайт');
            }

            return $this->redirect('/account/login');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try{
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e){
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'Новый пароль изменен');
            return $this->redirect('/account/login');
        }
        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    public function actionSetEmail()
    {
        $sessData = Yii::$app->session->get('createSubscribe');

        if ( !$sessData) {
            return $this->goHome();
        }

        $transport_id = $sessData['transport_id'];

        $nextUrl = Utils::addParamToUrl('/payment/transport', ['item_id' => $transport_id]);

        if (Yii::$app->user->identity->email) {
            Yii::$app->gearman->getDispatcher()->background("createSubscribeRule", [
                'transport_id' => $transport_id
            ]);

            return $this->afterEmail($nextUrl);
        }

        $model = new EmailForm();

        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('skip')) {
                return $this->afterEmail($nextUrl);
            }

            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                $user = User::findOne(Yii::$app->user->id);
                $user->email = $model->email;
                if ($user->save()) {
                    Yii::$app->gearman->getDispatcher()->background("createSubscribeRule", [
                        'transport_id' => $transport_id
                    ]);
                }

                return $this->afterEmail($nextUrl);
            }
        }

        return $this->render('setEmail', [
            'model' => $model
        ]);
    }

    /**
     * @param $url
     */
    private function afterEmail($url)
    {
        Yii::$app->session->remove('createSubscribe');
        $this->redirect($url);
    }
}
