<?php

namespace frontend\modules\subscribe\controllers;

use common\helpers\TemplateHelper;
use common\models\CargoCategory;
use common\models\LoginSignup;
use common\models\PaymentSystem;
use common\models\Service;
use common\models\ServiceRate;
use Exception;
use frontend\modules\payment\helpers\PaymentHelper;
use frontend\modules\subscribe\models\Subscribe;
use frontend\modules\subscribe\models\SubscribeRules;
use Svezem\Services\PaymentService\Gates\Sberbank\Input\RegisterResponse;
use Svezem\Services\PaymentService\Gates\Sberbank\SberbankGate;
use Svezem\Services\PaymentService\PaymentService;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\AjaxFilter;
use yii\filters\ContentNegotiator;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\widgets\ActiveForm;

/**
 * Default controller for the `subscribe` module
 */
class DefaultController extends Controller
{
    const TMP_RULE_LIST_KEY = 'tmp_rule_list';

    public function behaviors()
    {
        return [
            'nosubdomain' => [
                'class' => 'common\behaviors\NoSubdomain',
                'only' => ['index']
            ],
            [
                'class' => ContentNegotiator::class,
                'only' => ['msg-count', 'addrule', 'rule', 'rules', 'editphone', 'editemail', 'rule-copy'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ]
            ],
            'ajax' => [
                'class' => AjaxFilter::class,
                'only' => ['addrule', 'editphone']
            ]
        ];
    }

    public function actionIndex()
    {
        $subscribe = $this->getSubscribe();

        $loginSignup = new LoginSignup();
        $loginSignup->signupScenario = 'OnlyUserCreate';

        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $subscribe->load(Yii::$app->request->post());
            $loginSignup->load(Yii::$app->request->post());

            //требуется ли форма входа
            if (Yii::$app->user->isGuest) {
                $validate = $subscribe->validate() && $loginSignup->validate() && $loginSignup->loginSignup();
            } else {
                $validate = $subscribe->validate();
            }

            if ($validate) {
                $rules = $this->getRulesList($subscribe);

                //если авторизовались, необходимо заново получить модель подписок
                //возможно у пользователя уже она есть
                $subscribe = $this->getSubscribe();
                $subscribe->load(Yii::$app->request->post());

                //Проверяем что есть правила
                if (empty($rules)) {
                    Yii::$app->response->statusCode = 400;

                    return [
                        'type' => 'msg',
                        'msg' => 'Необходимо создать правила',
                        'csrf' => Yii::$app->getRequest()->getCsrfToken()
                    ];
                }

                $transaction = Yii::$app->db->beginTransaction();

                //if($subscribe->isNewRecord){
                $subscribe->save();
                //}

                foreach ($rules as $rule) {
                    $this->saveRule($rule, $subscribe);
                }

                //Платная подписка. Создаем платеж и перекидываем на
                //форму оплааты
                if ($subscribe->type == Subscribe::TYPE_PAID) {
                    $rate = ServiceRate::findOne(['service_id' => Service::SMS_NOTIFY]);

                    $payType = Yii::$app->request->post('payType', 'card');

                    //Параметры платежа
                    $paymentParams = [
                        [
                            'service_rate_id' => $rate->id,
                            'count' => $subscribe->addMessage,
                            'object_id' => $subscribe->id
                        ]
                    ];

                    if ($payType == 'card') {
                        $payment = PaymentHelper::createPayment(PaymentSystem::SYS_SBERBANK, $paymentParams);
                    } elseif ($payType == 'juridical') {
                        $payment = PaymentHelper::createPayment(PaymentSystem::SYS_JURIDICAL, $paymentParams);
                    } else {
                        throw new BadRequestHttpException();
                    }

                    if ($payment) {
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 400;
                        return [
                            'type' => 'msg',
                            'msg' => 'Не удалось создать платеж',
                            'csrf' => Yii::$app->getRequest()->getCsrfToken()
                        ];
                    }

                    //после создания подписки удаляем все правила из сессии
                    Yii::$app->session->remove(self::TMP_RULE_LIST_KEY);

                    if ($payment->payment_system_id == PaymentSystem::SYS_SBERBANK) {
                        $paymentGate = Yii::$container->get(PaymentService::class, [Yii::$container->get(SberbankGate::class)]);

                        /** @var RegisterResponse $registerResponse */
                        $registerResponse = $paymentGate->registerPayment([
                            'orderNumber' => $payment->id,
                            'amount' => $payment->amount*100, // Цена в копейках
                            'returnUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/sub']),
                            'failUrl' => Yii::$app->urlManager->createAbsoluteUrl(['/sub']),
                            'description' => "[{$payment->id}] Оплата услуг сервиса Svezem.ru"
                        ]);
                        if(!$registerResponse->isOk()){
                            return [
                                'type' => 'msg',
                                'msg' => $registerResponse->getErrorMessage(),
                                'csrf' => Yii::$app->getRequest()->getCsrfToken()
                            ];
                        }

                        return [
                            'redirect' => $registerResponse->getFormUrl()
                        ];

                    } elseif ($payment->payment_system_id == PaymentSystem::SYS_JURIDICAL) {
                        return [
                            'redirect' => Url::toRoute(['/payment/juridical/requisites', 'payment' => $payment->id])
                        ];
                    }
                } else {
                    $transaction->commit();

                    //Бесплатная подписка.
                    //После сохранения подписки показываем сообщение
                    return [
                        'saved' => 'Подписка сохранена',
                        'csrf' => Yii::$app->getRequest()->getCsrfToken()
                    ];
                }

            } else {
                Yii::$app->response->statusCode = 400;
                Yii::$app->response->format = Response::FORMAT_JSON;

                return [
                    'type' => 'params',
                    'params' => array_merge(
                        ActiveForm::validate($subscribe),
                        $loginSignup->ajaxValidate(null, false)
                    ),
                    'csrf' => Yii::$app->getRequest()->getCsrfToken()
                ];
            }
        }

        //цена за сообщение
        //2 - СМС уведомления
        $service = Service::findOne(2);
        $service->count = 1;

        $sub_rule = new SubscribeRules();
        $sub_rule->locationFrom = Yii::$app->request->get('locationFrom', '');
        $sub_rule->locationFromType = Yii::$app->request->get('locationFromType', '');
        $sub_rule->locationTo = Yii::$app->request->get('locationTo', '');
        $sub_rule->locationToType = Yii::$app->request->get('locationToType', '');
        $sub_rule->categoriesId = Yii::$app->request->get('categoriesId', []);
        $sub_rule->validate(['locationFrom', 'locationTo']);

        return $this->render('index', [
            'subscribe' => $subscribe,
            'sub_rule' => $sub_rule,
            'loginSignup' => $loginSignup,
            'priceForMsg' => $service->price,
            'pageTpl' => TemplateHelper::get("subscribe-view")
        ]);
    }

    /**
     * @return array
     */
    public function actionMsgCount()
    {
        $locationFrom = Yii::$app->request->get('locationFrom', null);
        $locationTo = Yii::$app->request->get('locationTo', null);
        $locationFromType = Yii::$app->request->get('locationFromType', null);
        $locationToType = Yii::$app->request->get('locationToType', null);
        $category = Yii::$app->request->get('category', null);

        $cargoCount = SubscribeRules::calcMessageCount($locationFrom, $locationTo, $locationFromType, $locationToType,
            $category);

        return [
            'count' => $cargoCount
        ];
    }

    /**
     * Получние правил
     * @return array
     */
    public function actionRules()
    {
        $models = [];

        $subscribe = $this->getSubscribe();

        /** @var SubscribeRules[] $tmp_rule_list */
        $tmp_rule_list = $this->getRulesList($subscribe);
        foreach ($tmp_rule_list as $model) {
            $models[] = $this->modelToJson($model);
        }

        return $models;
    }

    /**
     * Создание/Редактирование/удаление правила
     * @return array
     */
    public function actionRule()
    {
        $method = Yii::$app->request->method;

        $subscribe = $this->getSubscribe();
        $rule_id = Yii::$app->request->get('id');

        //создание нового правила
        if ($method == 'POST') {
            $rule = new SubscribeRules();

            $data = json_decode(Yii::$app->request->rawBody, 1);
            $rule->locationFrom = $data['locationFrom'];
            $rule->locationTo = $data['locationTo'];
            $rule->locationFromType = $data['locationFromType'];
            $rule->locationToType = $data['locationToType'];
            $rule->categoriesId = $data['categoriesId'];

            if ($this->saveRule($rule, $subscribe)) {
                return $this->modelToJson($rule);
            } else {
                Yii::$app->response->statusCode = 400;
                return $rule->errors;
            }
        }

        //удаление правила
        if ($method == 'DELETE') {
            $rule = $this->getOneRule($rule_id, $subscribe);
            $this->deleteRule($rule);

            return [];
        }

        //редактирование правила
        if ($method == 'PATCH') {
            $data = json_decode(Yii::$app->request->rawBody, 1);

            $rule = $this->getOneRule($rule_id, $subscribe);
            $rule->locationFrom = $data['locationFrom']??null;
            $rule->locationTo = $data['locationTo']??null;
            $rule->locationFromType = $data['locationFromType'];
            $rule->locationToType = $data['locationToType'];
            $rule->categoriesId = $data['categoriesId']??null;

            if ($this->saveRule($rule, $subscribe)) {
                return $this->modelToJson($rule);
            } else {
                Yii::$app->response->statusCode = 400;
                return $rule->errors;
            }
        }
    }

    /**
     * @return Subscribe|null|static
     */
    protected function getSubscribe()
    {
        //получаем существующую подписку или создаем новую
        $subscribe = Subscribe::findOne(['userid' => Yii::$app->user->id]);
        if ( !$subscribe) {
            $subscribe = new Subscribe();

            if ( !Yii::$app->user->isGuest) {
                if ( !$subscribe->phone && Yii::$app->user->identity->phone) {
                    $subscribe->phone = "+".Yii::$app->user->identity->phone;
                    $subscribe->type = Subscribe::TYPE_PAID;
                }
                if ( !$subscribe->email && Yii::$app->user->identity->email) {
                    $subscribe->email = Yii::$app->user->identity->email;
                    $subscribe->type = Subscribe::TYPE_FREE;
                }
            }
        }

        if ( !$subscribe->type) {
            $subscribe->type = Subscribe::TYPE_PAID;
        }

        return $subscribe;
    }

    /**
     * @param $subscribe Subscribe
     * @return SubscribeRules[]
     */
    protected function getRulesList($subscribe)
    {
        if ( !$subscribe->isNewRecord) {
            $list = SubscribeRules::findAll([
                'subscribe_id' => $subscribe->id,
                'status' => SubscribeRules::STATUS_ACTIVE
            ]);
        } else {
            $list = Yii::$app->session->get(self::TMP_RULE_LIST_KEY, []);
        }

        return $list;
    }

    /**
     * @param $id int
     * @param $subscribe Subscribe
     * @return SubscribeRules|null
     */
    protected function getOneRule($id, $subscribe)
    {
        if ( !$subscribe->isNewRecord) {
            return SubscribeRules::findOne([
                'id' => $id,
                'subscribe_id' => $subscribe->id
            ]);
        } else {
            $list = $this->getRulesList($subscribe);

            return isset($list[$id]) ? $list[$id] : null;
        }
    }

    /**
     * @param $rule SubscribeRules
     * @param $subscribe Subscribe
     * @return bool
     */
    protected function saveRule(&$rule, $subscribe)
    {
        if ($subscribe->isNewRecord) {
            if ($rule->validate()) {
                //сохраняем модель правила в сессию

                //данное условие означает, что модель не находится в сессии
                if ( !$rule->id) {
                    $last_tmp_rule_id = Yii::$app->session->get('last_tmp_rule_id', 0);
                    $last_tmp_rule_id++;
                    Yii::$app->session->set('last_tmp_rule_id', $last_tmp_rule_id);
                    $rule->id = 'tmp_'.$last_tmp_rule_id;
                }
                $tmp_rule_list = Yii::$app->session->get(self::TMP_RULE_LIST_KEY);
                $tmp_rule_list[$rule->id] = $rule;
                Yii::$app->session->set(self::TMP_RULE_LIST_KEY, $tmp_rule_list);

                return true;
            } else {
                return false;
            }
        } else {
            if ($rule->isNewRecord) {
                $rule->subscribe_id = $subscribe->id;
            }

            return $rule->save();
        }

    }

    /**
     * @param $rule SubscribeRules
     */
    protected function deleteRule($rule)
    {
        if ( !$rule->isNewRecord) {
            $rule->status = SubscribeRules::STATUS_DELETED;
            $rule->save();
        } else {
            $list = Yii::$app->session->get(self::TMP_RULE_LIST_KEY, []);
            unset($list[$rule->id]);
            Yii::$app->session->set(self::TMP_RULE_LIST_KEY, $list);
        }
    }

    /**
     * @param $model SubscribeRules
     * @return array
     */
    protected function modelToJson($model)
    {
        if ( !empty($model->categoriesId)) {
            $categoriesText = '';
            $categories = CargoCategory::findAll(['id' => $model->categoriesId]);
            foreach ($categories as $category) {
                $categoriesText .= $category->category.', ';

                if (mb_strlen($categoriesText) > 20) {
                    $categoriesText = StringHelper::truncate($categoriesText, 20);
                    break;
                }
            }
            $categoriesText = trim($categoriesText, ', ');
        } else {
            $categoriesText = 'Все категории';
        }

        $selectedFrom = $model->getCityString('From');
        $selectedTo = $model->getCityString('To');

        return [
            'id' => $model->id,
            'cityFrom' => [
                'id' => key($selectedFrom),
                'type' => $model->locationFromType,
                'title' => $model->selectedCity('From'),
                'select' => current($selectedFrom),
                'flag' => $model->flag('From'),
                'countyTitle' => $model->countryTitle('From')
            ],
            'cityTo' => [
                'id' => key($selectedTo),
                'type' => $model->locationToType,
                'title' => $model->selectedCity('To'),
                'select' => current($selectedTo),
                'flag' => $model->flag('To'),
                'countyTitle' => $model->countryTitle('To')
            ],
            'categoriesId' => $model->categoriesId,
            'categoriesText' => $categoriesText,
            'msgCount' => $model->msgCount
        ];
    }

    public function actionEditphone()
    {
        $result = [
            'error' => 0
        ];

        $subscribe = $this->getSubscribe();
        $subscribe->scenario = Subscribe::SCENARIO_EDIT_CONTACT;

        $post = Yii::$app->request->post();

        if ( !$subscribe->isNewRecord) {
            $subscribe->phone = $post['phone'];

            if ( !$subscribe->save()) {
                $result = [
                    'error' => 1,
                    'msg' => $subscribe->errors['phone'][0]
                ];
            }
        }

        return $result;
    }

    public function actionEditemail()
    {
        $result = [
            'error' => 0
        ];

        $subscribe = $this->getSubscribe();
        $subscribe->scenario = Subscribe::SCENARIO_EDIT_CONTACT;

        $post = Yii::$app->request->post();

        if ( !$subscribe->isNewRecord) {
            $subscribe->email = $post['email'];

            if ( !$subscribe->save()) {
                $result = [
                    'error' => 1,
                    'msg' => $subscribe->errors['email'][0]
                ];
            }
        }

        return $result;
    }

    public function actionRuleCopy()
    {
        $data = json_decode(Yii::$app->request->rawBody, 1);

        $copy_id = $data['copy_id'];

        $subscribe = $this->getSubscribe();
        $rule = $this->getOneRule($copy_id, $subscribe);

        if ( !$rule) {
            throw new InvalidArgumentException('Неверно указано правило');
        }

        $clone = new SubscribeRules;
        $clone->attributes = $rule->attributes;
        $clone->locationFrom = $rule->locationFrom;
        $clone->locationFromType = $rule->locationFromType;
        $clone->locationTo = $rule->locationTo;
        $clone->locationToType = $rule->locationToType;
        $clone->categoriesId = $rule->categoriesId;

        if ( !$this->saveRule($clone, $subscribe)) {
            throw new ServerErrorHttpException('Не удалось скопировать правило');
        }

        return $this->modelToJson($clone);
    }
}
