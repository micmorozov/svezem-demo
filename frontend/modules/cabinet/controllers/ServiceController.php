<?php

namespace frontend\modules\cabinet\controllers;

use common\models\Cargo;
use common\models\Payment;
use common\models\PaymentSystem;
use common\models\Profile;
use common\models\Service;
use common\models\ServiceRate;
use common\models\Transport;
use frontend\modules\cabinet\models\JuridicalForm;
use Yii;
use yii\base\InvalidParamException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class ServiceController extends DefaultController
{

  /**
   * @inheritdoc
   */
  public function behaviors() {
    return [
      'access' => [
        'class' => AccessControl::className(),
//        'only' => ['index', 'top', 'get-ajax-form'],
        'rules' => [
          [
            'actions' => ['index', 'top', 'sms', 'offers', 'get-ajax-form'],
            'allow' => true,
            'roles' => ['@'],
          ],
        ],
      ],
    ];
  }

  public function beforeAction($action) {
    if ($action->id == 'top' || $action->id == 'sms' || $action->id == 'offers'){
      Url::remember(Yii::$app->request->referrer);
    }

    return parent::beforeAction($action);
  }

  public function actionIndex() {
    /*$topPayments = PaymentService::find()
      ->joinWith('cargo')
      ->joinWith('transport')
      ->where([
        'payment.created_by' => Yii::$app->user->identity->id,
        'payment.status' => PaymentService::STATUS_PAID,
      ])
      ->andWhere(['or',
        ['>', 'cargo.top_until', time()],
        ['>', 'transport.top_until', time()]
      ])
      ->orderBy(['payment.created_at' => SORT_DESC])
      ->groupBy('payment.cargo_id, payment.transport_id')
      ->all();*/

	  $topCargo = Cargo::find()
		  ->where([
			  'created_by' => Yii::$app->user->identity->id,
			  'status' => Cargo::STATUS_ACTIVE
		  ])
		  ->andWhere(['and',
			  ['>', 'top_until', time()]
		  ])
		  ->all();

	  $topTransport = transport::find()
		  ->where([
			  'created_by' => Yii::$app->user->identity->id,
			  'status' => Cargo::STATUS_ACTIVE
		  ])
		  ->andWhere(['and',
			  ['>', 'top_until', time()]
		  ])
		  ->all();

    $minPrices = ArrayHelper::map(ServiceRate::find()->joinWith('service')->groupBy('service_id')->select('service_id, code, MIN(price/amount) as min_price')->asArray()->all(), 'code', 'min_price');

    return $this->render('index', [
		//'topPayments' => $topPayments,
		'topCargo' => $topCargo,
		'topTransport' => $topTransport,
		'minPrices' => $minPrices
    ]);
  }

  /**
   * @param $form_id
   * @param $payment_id
   * @param $service_rate_id
   * @param $payment_id
   * @return string
   * @throws NotFoundHttpException
   */
  public function actionGetAjaxForm($form_id, $service_rate_id, $profile_id = null, $cargo_id = null, $transport_id = null)
  {
	  // Ищем платежную систему
	  $payment_system = PaymentSystem::findOne(['code' => $form_id]);
	  if ($payment_system === null) {
		  throw new InvalidParamException;
	  }

	  // Проверяем, что существует услуга
	  $service_rate = ServiceRate::findOne($service_rate_id);
	  if ($service_rate === null) {
		  throw new InvalidParamException;
	  }

	  // Создаем платеж в таблице платежей
	  $payment = new Payment();
	  if ($payment === null) {
		  throw new NotFoundHttpException;
	  }

	  $payment->payment_system_id = $payment_system->id;
	  $payment->service_rate_id = $service_rate_id;
	  $payment->amount = round($service_rate->price / $payment_system->rate, 2);

	  if ($profile_id != null) {
		  $payment->profile_id = $profile_id;
	  }

	  if ($cargo_id != null) {
		  $payment->cargo_id = $cargo_id;
		  $payment->transport_id = null;
	  } else if ($transport_id != null) {
		  $payment->transport_id = $transport_id;
		  $payment->cargo_id = null;
	  }

	  if ($payment->save()) {
		  return $this->renderAjax('@frontend/modules/cabinet/views/service/_payments_forms/_' . $form_id, [
			  'payment' => $payment
		  ]);
	  }else
		  throw new NotFoundHttpException;
  }

  public function actionTop($cargo_id = null, $transport_id = null) {
    $serviceRates = ServiceRate::find()->joinWith('service')->where(['service.code' => 'top'])->all();
    $paymentSystems = PaymentSystem::find()->where(['enabled' => PaymentSystem::STATUS_ENABLED])->orderBy('sort DESC')->all();
    $cargos = Cargo::find()
      ->where(['created_by' => userId(), 'status' => Cargo::STATUS_ACTIVE])
      ->with(['cargoLocations.city.country', 'cargoCategory'])
      ->all();

    $transports = Transport::find()
      ->where(['transport.created_by' => userId(), 'transport.status' => Transport::STATUS_ACTIVE])
      ->with(['cargoCategories', 'citiesFrom', 'citiesTo', 'regionsFrom', 'regionsTo', 'countriesFrom', 'countriesTo', 'transportLocations'])
      ->all();

    if (!count($cargos) && !count($transports)){
      Yii::$app->session->setFlash('info', 'Для закрепления объявления в топ необходимо добавить грузовой транспорт или создать заявку на перевозку груза.');
      return $this->redirect(['/cabinet/service']);
    }
   /* $payment = new PaymentService();

    if(!$payment->save()) {
      throw new ServerErrorHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
    }*/

    return $this->render('top', [
      'serviceRates' => $serviceRates,
      'paymentSystems' => $paymentSystems,
      //'payment_id' => $payment->id,
      //'juridicalForm' => new JuridicalForm(),
      'cargos' => $cargos,
      'transports' => $transports,
      'cargo_id' => $cargo_id,
      'transport_id' => $transport_id
    ]);
  }

  public function actionSms($profile_id = null) {
    $serviceRates = ServiceRate::find()->joinWith('service')->where(['service.code' => 'sms-notify'])->all();
    $paymentSystems = PaymentSystem::find()->where(['enabled' => PaymentSystem::STATUS_ENABLED])->orderBy('sort DESC')->all();
    $profiles = Profile::find()
      ->where(['created_by' => Yii::$app->user->identity->id])
      ->andWhere(['not', ['contact_phone' => '']])
      ->all();
    if (!count($profiles)){
      Yii::$app->session->setFlash('info', 'Для подключения смс уведомлений укажите в профиле контактный телефон.');
      return $this->redirect(['/cabinet/settings']);
    }

    /*$payment = new PaymentService();
    if(!$payment->save()) {
      throw new ServerErrorHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
    }*/

    return $this->render('sms', [
      'serviceRates' => $serviceRates,
      'paymentSystems' => $paymentSystems,
      //'payment_id' => $payment->id,
      //'juridicalForm' => new JuridicalForm(),
      'profiles' => $profiles,
      'profile_id' => $profile_id
    ]);
  }

  public function actionOffers($service_type = null) {
    $serviceRatesInfinite = ServiceRate::find()->joinWith('service')->where(['service.code' => 'infinite-answers'])->all();
    $serviceRatesAdditional = ServiceRate::find()->joinWith('service')->where(['service.code' => 'additional-answers'])->all();
    $paymentSystems = PaymentSystem::find()->where(['enabled' => PaymentSystem::STATUS_ENABLED])->orderBy('sort DESC')->all();
    if (!isset(Yii::$app->user->identity->transporterProfile)) {
      Yii::$app->session->setFlash('info', 'Для покупки откликов необходимо создать профиль перевозчика');
      return $this->redirect(['/cabinet/settings']);
    }
    /*$payment = new PaymentService();
    $payment->profile_id = Yii::$app->user->identity->transporterProfile->id;
    if(!$payment->save()) {
      throw new ServerErrorHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
    }*/

    return $this->render('offers', [
      'serviceRatesInfinite' => $serviceRatesInfinite,
      'serviceRatesAdditional' => $serviceRatesAdditional,
      'paymentSystems' => $paymentSystems,
	  'profile_id' => Yii::$app->user->identity->transporterProfile->id,
      //'payment_id' => $payment->id,
      //'juridicalForm' => new JuridicalForm(),
      'service_type' => $service_type
    ]);
  }

}
